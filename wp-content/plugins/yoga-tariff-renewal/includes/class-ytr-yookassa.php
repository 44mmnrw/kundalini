<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_YooKassa {
	public static function humanize_api_error(string $message): string {
		$lower = strtolower($message);

		if (
			str_contains($lower, 'recurring')
			|| str_contains($lower, 'forbidden')
			|| str_contains($lower, "can't make recurring")
			|| str_contains($lower, 'repeat payments')
		) {
			return __(
				'Автоплатежи не подключены в магазине ЮKassa. Обратитесь к менеджеру ЮKassa через личный кабинет (чат поддержки) с запросом: «Подключить автоплатежи и сохранение способа оплаты (save_payment_method)». Без этой опции привязка карты и автопродление недоступны.',
				'yoga-tariff-renewal'
			);
		}

		return $message;
	}

	public static function is_configured(): bool {
		if (get_option('yookassa_access_token', '') !== '') {
			return true;
		}

		return get_option('yookassa_shop_id', '') !== ''
			&& get_option('yookassa_shop_password', '') !== '';
	}

	public static function resolve_payment_method_id_for_order(WC_Order $order): string {
		$payment_id = (string) $order->get_transaction_id();
		if ($payment_id === '' || !class_exists('YooKassaClientFactory')) {
			return self::resolve_payment_method_id_from_tokens((int) $order->get_customer_id());
		}

		try {
			$payment = YooKassaClientFactory::getYooKassaClient()->getPaymentInfo($payment_id);
		} catch (Exception $e) {
			return self::resolve_payment_method_id_from_tokens((int) $order->get_customer_id());
		}

		$method = $payment->getPaymentMethod();
		if ($method && method_exists($method, 'getId') && method_exists($method, 'getSaved') && $method->getSaved()) {
			return (string) $method->getId();
		}

		return self::resolve_payment_method_id_from_tokens((int) $order->get_customer_id());
	}

	public static function resolve_payment_method_id_from_tokens(int $user_id): string {
		if ($user_id <= 0 || !class_exists('WC_Payment_Tokens')) {
			return '';
		}

		foreach (array('yookassa_widget', 'yookassa_epl') as $gateway_id) {
			$tokens = WC_Payment_Tokens::get_customer_tokens($user_id, $gateway_id);
			foreach ($tokens as $token) {
				if (is_object($token) && method_exists($token, 'get_token')) {
					$token_value = (string) $token->get_token();
					if ($token_value !== '') {
						return $token_value;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Платёж для привязки карты в ЛК (redirect + save_payment_method).
	 *
	 * @return array{success:bool,payment_id:string,status:string,message:string,confirmation_url:string}
	 */
	public static function create_card_binding_payment(WC_Order $order): array {
		$empty = array(
			'success'            => false,
			'payment_id'         => '',
			'status'             => 'error',
			'message'            => '',
			'confirmation_url'   => '',
		);

		if (!self::is_configured()) {
			$empty['message'] = __('ЮKassa не настроена', 'yoga-tariff-renewal');
			return $empty;
		}

		if (!class_exists('YooKassa\Request\Payments\CreatePaymentRequest') || !class_exists('YooKassaClientFactory')) {
			$empty['message'] = __('SDK ЮKassa не найден', 'yoga-tariff-renewal');
			return $empty;
		}

		$return_url = function_exists('yoga_yookassa_get_return_url_for_order')
			? yoga_yookassa_get_return_url_for_order($order)
			: $order->get_checkout_order_received_url();

		try {
			if (class_exists('YTR_Checkout')) {
				YTR_Checkout::ensure_order_billing_phone($order);
			}

			$total = (float) $order->get_total();
			$builder = \YooKassa\Request\Payments\CreatePaymentRequest::builder()
				->setAmount(number_format($total, 2, '.', ''))
				->setCapture(true)
				->setSavePaymentMethod(true)
				->setDescription(
					sprintf(
						/* translators: %d: order id */
						__('Привязка карты (заказ #%d)', 'yoga-tariff-renewal'),
						$order->get_id()
					)
				)
				->setMetadata(self::build_binding_metadata($order))
				->setConfirmation(
					array(
						'type'       => 'redirect',
						'return_url' => $return_url,
					)
				);

			if (class_exists('YooKassa\Model\PaymentData\PaymentDataFactory')) {
				$factory = new \YooKassa\Model\PaymentData\PaymentDataFactory();
				$builder->setPaymentMethodData($factory->factory('bank_card'));
			}

			if (class_exists('YooKassaHandler')) {
				YooKassaHandler::setReceiptIfNeeded($builder, $order);
			}

			$payment_request = $builder->build();
			if (class_exists('YTR_Checkout')) {
				YTR_Checkout::apply_merchant_customer_id_to_builder($payment_request, $order);
			}

			$response        = YooKassaClientFactory::getYooKassaClient()->createPayment($payment_request);
		} catch (Exception $e) {
			$empty['message'] = self::humanize_api_error($e->getMessage());
			return $empty;
		}

		$payment_id = method_exists($response, 'getId') ? (string) $response->getId() : '';
		$status     = method_exists($response, 'getStatus') ? (string) $response->getStatus() : '';
		$confirm_url = '';

		if (method_exists($response, 'getConfirmation')) {
			$confirmation = $response->getConfirmation();
			if ($confirmation && method_exists($confirmation, 'getConfirmationUrl')) {
				$confirm_url = (string) $confirmation->getConfirmationUrl();
			}
		}

		if ($payment_id !== '') {
			$order->set_transaction_id($payment_id);
			$order->save();
		}

		if ($confirm_url === '') {
			$empty['message'] = __('ЮKassa не вернула ссылку для ввода карты', 'yoga-tariff-renewal');
			return $empty;
		}

		return array(
			'success'           => true,
			'payment_id'        => $payment_id,
			'status'            => $status,
			'message'           => '',
			'confirmation_url'  => $confirm_url,
		);
	}

	/**
	 * @return array{success:bool, payment_id:string, status:string, message:string}
	 */
	public static function charge_renewal(WC_Order $order, string $payment_method_id): array {
		if (
			class_exists('YTR_Stub')
			&& YTR_Stub::is_enabled()
			&& YTR_Stub::is_stub_payment_method($payment_method_id)
		) {
			return YTR_Stub::stub_charge_renewal($order);
		}

		if (!self::is_configured()) {
			return array(
				'success'     => false,
				'payment_id'  => '',
				'status'      => 'error',
				'message'     => 'ЮKassa не настроена',
			);
		}

		if ($payment_method_id === '') {
			return array(
				'success'     => false,
				'payment_id'  => '',
				'status'      => 'error',
				'message'     => 'Нет сохранённого способа оплаты',
			);
		}

		if (!class_exists('YooKassa\Request\Payments\CreatePaymentRequest') || !class_exists('YooKassaClientFactory')) {
			return array(
				'success'     => false,
				'payment_id'  => '',
				'status'      => 'error',
				'message'     => 'SDK ЮKassa не найден',
			);
		}

		try {
			$total = (float) $order->get_total();
			$builder = \YooKassa\Request\Payments\CreatePaymentRequest::builder()
				->setAmount(number_format($total, 2, '.', ''))
				->setCapture(true)
				->setPaymentMethodId($payment_method_id)
				->setDescription(self::build_description($order))
				->setMetadata(self::build_metadata($order));

			if (class_exists('YooKassaHandler')) {
				YooKassaHandler::setReceiptIfNeeded($builder, $order);
			}

			$payment_request = $builder->build();
			$idempotence_key = class_exists('YTR_Orders') ? YTR_Orders::get_renewal_idempotence_key($order) : 'ytr-renewal-order-' . $order->get_id();
			$response        = YooKassaClientFactory::getYooKassaClient()->createPayment($payment_request, $idempotence_key);
		} catch (Exception $e) {
			return array(
				'success'     => false,
				'payment_id'  => '',
				'status'      => 'error',
				'message'     => self::humanize_api_error($e->getMessage()),
			);
		}

		$payment_id = method_exists($response, 'getId') ? (string) $response->getId() : '';
		$status     = method_exists($response, 'getStatus') ? (string) $response->getStatus() : '';

		if ($payment_id !== '') {
			$order->set_transaction_id($payment_id);
		}
		$order->update_meta_data('_ytr_yookassa_payment_status', $status);
		$order->save();

		if ($status === 'succeeded') {
			$order->payment_complete($payment_id);
			if ($order->has_status('processing')) {
				$order->update_status('completed', __('Автопродление тарифа.', 'yoga-tariff-renewal'));
			}

			return array(
				'success'     => true,
				'payment_id'  => $payment_id,
				'status'      => $status,
				'message'     => 'Оплачено',
			);
		}

		if (in_array($status, array('pending', 'waiting_for_capture'), true)) {
			$order->update_status(
				'pending',
				__('Автопродление: платёж не завершён (ожидает подтверждения ЮKassa).', 'yoga-tariff-renewal')
			);

			return array(
				'success'     => false,
				'payment_id'  => $payment_id,
				'status'      => $status,
				'message'     => 'Платёж не завершён: ' . $status,
			);
		}

		return array(
			'success'     => false,
			'payment_id'  => $payment_id,
			'status'      => $status,
			'message'     => 'Платёж не прошёл: ' . $status,
		);
	}

	public static function sync_order_payment_status(WC_Order $order): string {
		$payment_id = (string) $order->get_transaction_id();
		if ($payment_id === '' || !class_exists('YooKassaClientFactory')) {
			return '';
		}

		try {
			$payment = YooKassaClientFactory::getYooKassaClient()->getPaymentInfo($payment_id);
		} catch (Exception $e) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: api error */
					__('Автопродление: не удалось проверить статус платежа в ЮKassa: %s', 'yoga-tariff-renewal'),
					self::humanize_api_error($e->getMessage())
				)
			);
			return '';
		}

		$status = method_exists($payment, 'getStatus') ? (string) $payment->getStatus() : '';
		if ($status === '') {
			return '';
		}

		$order->update_meta_data('_ytr_yookassa_payment_status', $status);

		if ($status === 'succeeded') {
			$order->payment_complete($payment_id);
			if ($order->has_status('processing')) {
				$order->update_status('completed', __('Автопродление тарифа.', 'yoga-tariff-renewal'));
			} else {
				$order->save();
			}

			return $status;
		}

		if ($status === 'canceled' || $status === 'cancelled') {
			$order->update_status(
				'failed',
				__('Автопродление: ЮKassa вернула финальный статус отмены платежа.', 'yoga-tariff-renewal')
			);
			$user_id = (int) $order->get_customer_id();
			if ($user_id > 0 && class_exists('YTR_User')) {
				YTR_User::record_renewal_failure($user_id);
			}
			if (class_exists('YTR_Notifications')) {
				YTR_Notifications::send_renewal_failure($order, __('ЮKassa вернула финальный статус отмены платежа.', 'yoga-tariff-renewal'));
			}
			return $status;
		}

		if ($status === 'waiting_for_capture' && !$order->has_status('on-hold')) {
			$order->update_status(
				'on-hold',
				__('Автопродление: платеж ожидает подтверждения ЮKassa.', 'yoga-tariff-renewal')
			);
			return $status;
		}

		$order->save();
		return $status;
	}

	private static function build_description(WC_Order $order): string {
		$product_id = YTR_Tariff::get_tariff_product_id_from_order($order);
		$name       = $product_id > 0 ? get_the_title($product_id) : __('Тариф', 'yoga-tariff-renewal');

		return sprintf(
			/* translators: 1: product name, 2: order id */
			__('Автопродление: %1$s (заказ #%2$d)', 'yoga-tariff-renewal'),
			$name,
			$order->get_id()
		);
	}

	/**
	 * @return array<string, string|int>
	 */
	private static function build_metadata(WC_Order $order): array {
		$metadata = array(
			'order_id'    => (string) $order->get_id(),
			'wp_user_id'  => (int) $order->get_customer_id(),
			'ytr_renewal' => '1',
			'cms_name'    => 'yoga_tariff_renewal',
		);

		if (defined('YOOKASSA_VERSION')) {
			$metadata['module_version'] = YOOKASSA_VERSION;
		}

		return $metadata;
	}

	/**
	 * @return array<string, string|int>
	 */
	private static function build_binding_metadata(WC_Order $order): array {
		return array(
			'order_id'          => (string) $order->get_id(),
			'wp_user_id'        => (int) $order->get_customer_id(),
			'ytr_card_binding'  => '1',
			'cms_name'          => 'yoga_tariff_renewal',
		);
	}
}
