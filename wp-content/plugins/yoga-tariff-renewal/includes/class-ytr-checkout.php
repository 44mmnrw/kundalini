<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Checkout {
	public static function init(): void {
		add_action('woocommerce_checkout_create_order', array(__CLASS__, 'store_opt_in_on_order'), 20, 1);
		add_filter('woocommerce_yookassa_create_payment_request', array(__CLASS__, 'maybe_save_payment_method'), 25);
	}

	public static function user_opted_in_to_save(): bool {
		return !empty($_POST['yoga_save_payment_method']);
	}

	/**
	 * Автоплатежи ЮKassa: карта и YooMoney (виджет), не redirect-методы.
	 */
	public static function is_save_payment_supported(): bool {
		$slug = '';
		if (!empty($_POST['yoga_checkout_payment_type'])) {
			$slug = sanitize_key(wp_unslash((string) $_POST['yoga_checkout_payment_type']));
		}

		$map = function_exists('yoga_yookassa_payment_type_map') ? yoga_yookassa_payment_type_map() : array();
		$api_type = (string) ($map[$slug] ?? $slug);

		return in_array($api_type, array('bank_card', 'yoo_money'), true);
	}

	public static function store_opt_in_on_order(WC_Order $order): void {
		if (!self::user_opted_in_to_save() || !self::is_save_payment_supported()) {
			return;
		}

		if (!YTR_Tariff::order_contains_tariff($order)) {
			return;
		}

		$order->update_meta_data('_ytr_auto_renew_opt_in', 'yes');
	}

	/**
	 * @param \YooKassa\Request\Payments\CreatePaymentRequest $payment_request
	 * @return \YooKassa\Request\Payments\CreatePaymentRequest
	 */
	public static function maybe_save_payment_method($payment_request) {
		if (!self::user_opted_in_to_save() || !self::is_save_payment_supported()) {
			return $payment_request;
		}

		if (!method_exists($payment_request, 'setSavePaymentMethod')) {
			return $payment_request;
		}

		$payment_request->setSavePaymentMethod(true);

		return $payment_request;
	}
}
