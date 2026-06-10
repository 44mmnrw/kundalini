<?php
/**
 * Расширение шлюза ЮKassa: детальные ошибки API и корректный createPayment.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_yookassa_format_api_error')) {
	function yoga_yookassa_format_api_error($error): string {
		if ($error instanceof WP_Error) {
			return yoga_yookassa_translate_api_error_message(trim((string) $error->get_error_message()));
		}

		if ($error instanceof Exception) {
			$message = trim($error->getMessage());
			if (method_exists($error, 'getResponseBody')) {
				$body = $error->getResponseBody();
				if (is_array($body)) {
					$parts = array();
					if (!empty($body['description'])) {
						$parts[] = (string) $body['description'];
					}
					if (!empty($body['parameter'])) {
						$parts[] = 'Параметр: ' . (string) $body['parameter'];
					}
					if (!empty($body['code'])) {
						$parts[] = 'Код: ' . (string) $body['code'];
					}
					if ($parts !== array()) {
						return yoga_yookassa_translate_api_error_message(implode('. ', $parts));
					}
				}
			}

			return yoga_yookassa_translate_api_error_message($message);
		}

		return '';
	}
}

if (!function_exists('yoga_yookassa_translate_api_error_message')) {
	function yoga_yookassa_translate_api_error_message(string $message): string {
		if ($message === '') {
			return '';
		}

		if (stripos($message, 'Payment method is not available') !== false) {
			return __('Этот способ оплаты не подключён в ЮKassa. Выберите другой на странице оплаты или подключите его в личном кабинете ЮKassa.', 'yoga');
		}

		return $message;
	}
}

if (!function_exists('yoga_yookassa_create_payment_with_body')) {
	/**
	 * Создание платежа по готовому JSON (для alfa_pay — тип ещё не в SDK плагина).
	 *
	 * @see https://yookassa.ru/developers/payment-acceptance/integration-scenarios/manual-integration/other/alfa-pay
	 *
	 * @return \YooKassa\Request\Payments\CreatePaymentResponse|WP_Error
	 */
	function yoga_yookassa_create_payment_with_body(array $serialized_data) {
		$shop_id = (string) get_option('yookassa_shop_id', '');
		$secret  = (string) get_option('yookassa_secret_key', '');

		if ($shop_id === '' || $secret === '') {
			return new WP_Error('yoga_yookassa_config', __('Не заданы ключи ЮKassa.', 'yoga'));
		}

		if (class_exists('YooKassaLogger')) {
			YooKassaLogger::info('Create payment request (alfa_pay): ' . wp_json_encode($serialized_data));
			YooKassaLogger::sendHeka(array('payment.request.init'));
		}

		$idempotence_key = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('yoga_', true);

		$response = wp_remote_post(
			'https://api.yookassa.ru/v3/payments',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization'   => 'Basic ' . base64_encode($shop_id . ':' . $secret, true),
					'Idempotence-Key' => $idempotence_key,
					'Content-Type'    => 'application/json',
				),
				'body' => wp_json_encode($serialized_data),
			)
		);

		if (is_wp_error($response)) {
			yoga_yookassa_store_api_error($response);

			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = json_decode((string) wp_remote_retrieve_body($response), true);

		if ($code < 200 || $code >= 300 || !is_array($body)) {
			$message = is_array($body) ? (string) ($body['description'] ?? 'API error') : 'API error';
			if (is_array($body) && !empty($body['parameter'])) {
				$message .= '. Параметр: ' . (string) $body['parameter'];
			}
			if (is_array($body) && !empty($body['code'])) {
				$message .= '. Код: ' . (string) $body['code'];
			}

			yoga_yookassa_store_api_error(new WP_Error('yookassa_api', $message));

			return new WP_Error('yookassa_api', yoga_yookassa_translate_api_error_message($message));
		}

		if (!class_exists('YooKassa\Request\Payments\CreatePaymentResponse')) {
			return new WP_Error('yoga_sdk', 'YooKassa SDK missing');
		}

		if (class_exists('YooKassaLogger')) {
			YooKassaLogger::info('Create payment response (alfa_pay): ' . wp_json_encode($body));
			YooKassaLogger::sendHeka(array('payment.request.success'));
		}

		return new \YooKassa\Request\Payments\CreatePaymentResponse($body);
	}
}

if (!function_exists('yoga_yookassa_store_api_error')) {
	function yoga_yookassa_store_api_error($error): void {
		$message = yoga_yookassa_format_api_error($error);
		if ($message === '' || !function_exists('WC') || !WC()->session) {
			return;
		}

		WC()->session->set('yoga_yookassa_api_error', yoga_yookassa_translate_api_error_message($message));
	}
}

if (!class_exists('Yoga_YooKassa_Gateway_EPL') && class_exists('YooKassaGatewayEPL')) {
	class Yoga_YooKassa_Gateway_EPL extends YooKassaGatewayEPL {
		/**
		 * T-Pay / СБП / SberPay / Alfa Pay: самостоятельная интеграция (redirect + payment_method_data).
		 *
		 * @see https://yookassa.ru/developers/payment-acceptance/integration-scenarios/manual-integration/other/tinkoff-bank#create-payment
		 * @see https://yookassa.ru/developers/payment-acceptance/integration-scenarios/manual-integration/other/sbp
		 * @see https://yookassa.ru/developers/payment-acceptance/integration-scenarios/manual-integration/other/alfa-pay
		 *
		 * @param WC_Order $order
		 */
		private function yoga_prepare_manual_payment(WC_Order $order): void {
			$GLOBALS['yoga_yookassa_checkout_order'] = $order;

			if (!function_exists('yoga_get_selected_yookassa_payment_type_for_api')) {
				return;
			}

			$type = yoga_get_selected_yookassa_payment_type_for_api();
			if ($type === '') {
				return;
			}

			$this->paymentMethod = $type;

			if (
				function_exists('yoga_yookassa_redirect_confirmation_types')
				&& in_array($type, yoga_yookassa_redirect_confirmation_types(), true)
			) {
				if (class_exists('YooKassa\Model\ConfirmationType')) {
					$this->confirmationType = \YooKassa\Model\ConfirmationType::REDIRECT;
				}

				// СБП не поддерживает save_payment_method (см. документацию ЮKassa).
				$this->savePaymentMethod = false;
			}
		}

		/**
		 * СБП: обязательно capture=true; подписка/холд ломают создание платежа.
		 *
		 * @param WC_Order $order
		 * @return \YooKassa\Request\Payments\CreatePaymentRequestBuilder
		 */
		protected function getBuilder($order) {
			$type = function_exists('yoga_get_selected_yookassa_payment_type_for_api')
				? yoga_get_selected_yookassa_payment_type_for_api()
				: '';

			$saved_subscribe      = $this->subscribe;
			$saved_payment_method = $this->paymentMethod;
			if (in_array($type, array('sbp', 'alfa_pay'), true)) {
				$this->subscribe = false;
			}
			// SDK плагина не знает alfa_pay — не передаём тип в factory при сборке билдера.
			if ($type === 'alfa_pay') {
				$this->paymentMethod = '';
			}

			$builder = parent::getBuilder($order);
			$this->paymentMethod = $saved_payment_method;

			if (in_array($type, array('sbp', 'alfa_pay'), true) && method_exists($builder, 'setCapture')) {
				$builder->setCapture(true);
			}

			$this->subscribe = $saved_subscribe;

			return $builder;
		}

		/**
		 * @param WC_Order $order
		 * @return mixed|WP_Error|\YooKassa\Request\Payments\CreatePaymentResponse
		 */
		public function createPayment($order) {
			if ($order instanceof WC_Order) {
				$this->yoga_prepare_manual_payment($order);
			}

			try {
				$builder        = $this->getBuilder($order);
				$paymentRequest = $builder->build();
				$paymentRequest = apply_filters('woocommerce_yookassa_create_payment_request', $paymentRequest);

				if (class_exists('YooKassaHandler') && YooKassaHandler::isReceiptEnabled()) {
					$receipt = $paymentRequest->getReceipt();
					if ($receipt instanceof \YooKassa\Model\Receipt) {
						$receipt->normalize($paymentRequest->getAmount());
					}
				}

				$api_type = function_exists('yoga_get_selected_yookassa_payment_type_for_api')
					? yoga_get_selected_yookassa_payment_type_for_api()
					: '';

				$serializer     = new \YooKassa\Request\Payments\CreatePaymentRequestSerializer();
				$serializedData = $serializer->serialize($paymentRequest);

				if ($api_type === 'alfa_pay') {
					$serializedData['payment_method_data'] = array('type' => 'alfa_pay');
					$response                                = yoga_yookassa_create_payment_with_body($serializedData);
				} else {
					if (class_exists('YooKassaLogger')) {
						YooKassaLogger::info('Create payment request: ' . json_encode($serializedData));
						YooKassaLogger::sendHeka(array('payment.request.init'));
					}

					$response = $this->getApiClient()->createPayment($paymentRequest);
				}
				if (class_exists('YooKassaLogger')) {
					YooKassaLogger::info('Create payment response: ' . json_encode($response->toArray()));
					YooKassaLogger::sendHeka(array('payment.request.success'));
				}

				return $response;
			} catch (\YooKassa\Common\Exceptions\ApiException $e) {
				yoga_yookassa_store_api_error($e);
				if (class_exists('YooKassaLogger')) {
					YooKassaLogger::error('Api error: ' . $e->getMessage());
				}

				return new WP_Error($e->getCode(), yoga_yookassa_format_api_error($e));
			} catch (Exception $e) {
				yoga_yookassa_store_api_error($e);
				if (class_exists('YooKassaLogger')) {
					YooKassaLogger::error('Create payment response error: ' . json_encode($e));
				}

				return new WP_Error($e->getCode(), $e->getMessage());
			}
		}

		/**
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment($order_id) {
			$order = wc_get_order($order_id);
			if ($order instanceof WC_Order) {
				$this->yoga_prepare_manual_payment($order);
			}

			$result = parent::process_payment($order_id);

			if (($result['result'] ?? '') !== 'failure' || !function_exists('WC') || !WC()->session) {
				return $result;
			}

			$api_error = trim((string) WC()->session->get('yoga_yookassa_api_error'));
			if ($api_error === '' || !function_exists('wc_get_notices')) {
				return $result;
			}

			$notices = wc_get_notices('error');
			if ($notices === array()) {
				return $result;
			}

			$base_notice = __('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты', 'yookassa');
			$full_notice = $base_notice . ' ' . $api_error;

			wc_clear_notices();
			wc_add_notice($full_notice, 'error');
			WC()->session->__unset('yoga_yookassa_api_error');

			return $result;
		}
	}
}
