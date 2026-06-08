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
			return trim((string) $error->get_error_message());
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
						return implode('. ', $parts);
					}
				}
			}

			return $message;
		}

		return '';
	}
}

if (!function_exists('yoga_yookassa_store_api_error')) {
	function yoga_yookassa_store_api_error($error): void {
		$message = yoga_yookassa_format_api_error($error);
		if ($message === '' || !function_exists('WC') || !WC()->session) {
			return;
		}

		WC()->session->set('yoga_yookassa_api_error', $message);
	}
}

if (!class_exists('Yoga_YooKassa_Gateway_EPL') && class_exists('YooKassaGatewayEPL')) {
	class Yoga_YooKassa_Gateway_EPL extends YooKassaGatewayEPL {
		/**
		 * @param WC_Order $order
		 * @return mixed|WP_Error|\YooKassa\Request\Payments\CreatePaymentResponse
		 */
		public function createPayment($order) {
			$builder        = $this->getBuilder($order);
			$paymentRequest = $builder->build();
			$paymentRequest = apply_filters('woocommerce_yookassa_create_payment_request', $paymentRequest);

			if (class_exists('YooKassaHandler') && YooKassaHandler::isReceiptEnabled()) {
				$receipt = $paymentRequest->getReceipt();
				if ($receipt instanceof \YooKassa\Model\Receipt) {
					$receipt->normalize($paymentRequest->getAmount());
				}
			}

			$serializer     = new \YooKassa\Request\Payments\CreatePaymentRequestSerializer();
			$serializedData = $serializer->serialize($paymentRequest);
			if (class_exists('YooKassaLogger')) {
				YooKassaLogger::info('Create payment request: ' . json_encode($serializedData));
				YooKassaLogger::sendHeka(array('payment.request.init'));
			}

			try {
				$response = $this->getApiClient()->createPayment($paymentRequest);
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
			$result = parent::process_payment($order_id);

			if (($result['result'] ?? '') === 'failure' && function_exists('wc_add_notice') && function_exists('WC') && WC()->session) {
				$api_error = (string) WC()->session->get('yoga_yookassa_api_error');
				if ($api_error !== '') {
					wc_add_notice(
						__('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты', 'yookassa') . ' ' . esc_html($api_error),
						'error'
					);
					WC()->session->__unset('yoga_yookassa_api_error');
				}
			}

			return $result;
		}
	}
}
