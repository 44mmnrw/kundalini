<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Checkout {
	public static function init(): void {
		add_action('woocommerce_checkout_create_order', array(__CLASS__, 'store_opt_in_on_order'), 20, 1);
		add_filter('woocommerce_yookassa_create_payment_request', array(__CLASS__, 'maybe_save_payment_method'), 99);
	}

	public static function user_opted_in_to_save(): bool {
		return !empty($_POST['yoga_save_payment_method']);
	}

	public static function resolve_checkout_order(): ?WC_Order {
		if (function_exists('yoga_yookassa_get_checkout_order')) {
			$order = yoga_yookassa_get_checkout_order();
			if ($order instanceof WC_Order) {
				return $order;
			}
		}

		if (!function_exists('WC') || !WC()->session) {
			return null;
		}

		$order_id = absint(WC()->session->get('order_awaiting_payment'));
		if ($order_id <= 0) {
			return null;
		}

		$order = wc_get_order($order_id);

		return $order instanceof WC_Order ? $order : null;
	}

	public static function resolve_payment_type_slug(?WC_Order $order = null): string {
		if (!empty($_POST['yoga_checkout_payment_type'])) {
			return sanitize_key(wp_unslash((string) $_POST['yoga_checkout_payment_type']));
		}

		if (!$order instanceof WC_Order) {
			$order = self::resolve_checkout_order();
		}

		if ($order instanceof WC_Order) {
			return sanitize_key((string) $order->get_meta('_yoga_checkout_payment_type'));
		}

		return '';
	}

	public static function payment_type_supports_save(string $slug): bool {
		$map      = function_exists('yoga_yookassa_payment_type_map') ? yoga_yookassa_payment_type_map() : array();
		$api_type = (string) ($map[$slug] ?? $slug);

		return in_array($api_type, array('bank_card', 'yoo_money'), true);
	}

	/**
	 * Автоплатежи ЮKassa: карта и YooMoney (виджет), не redirect-методы.
	 */
	public static function is_save_payment_supported(?WC_Order $order = null): bool {
		$slug = self::resolve_payment_type_slug($order);

		return $slug !== '' && self::payment_type_supports_save($slug);
	}

	public static function should_save_payment_method(?WC_Order $order = null): bool {
		if (!$order instanceof WC_Order) {
			$order = self::resolve_checkout_order();
		}

		if (!self::is_save_payment_supported($order)) {
			return false;
		}

		if (self::user_opted_in_to_save()) {
			return true;
		}

		return $order instanceof WC_Order
			&& $order->get_meta('_ytr_auto_renew_opt_in') === 'yes';
	}

	public static function store_opt_in_on_order(WC_Order $order): void {
		if (!self::should_save_payment_method($order)) {
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
		if (class_exists('YTR_Stub') && YTR_Stub::is_enabled()) {
			return $payment_request;
		}

		if (!self::should_save_payment_method()) {
			return $payment_request;
		}

		if (!method_exists($payment_request, 'setSavePaymentMethod')) {
			return $payment_request;
		}

		$payment_request->setSavePaymentMethod(true);

		return $payment_request;
	}
}
