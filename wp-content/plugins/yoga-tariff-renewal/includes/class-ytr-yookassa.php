<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_YooKassa {
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
	 * @return array{success:bool, payment_id:string, status:string, message:string}
	 */
	public static function charge_renewal(WC_Order $order, string $payment_method_id): array {
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
			$response        = YooKassaClientFactory::getYooKassaClient()->createPayment($payment_request);
		} catch (Exception $e) {
			return array(
				'success'     => false,
				'payment_id'  => '',
				'status'      => 'error',
				'message'     => $e->getMessage(),
			);
		}

		$payment_id = method_exists($response, 'getId') ? (string) $response->getId() : '';
		$status     = method_exists($response, 'getStatus') ? (string) $response->getStatus() : '';

		if ($payment_id !== '') {
			$order->set_transaction_id($payment_id);
			$order->save();
		}

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
}
