<?php
/**
 * Связка кастомного блока «Способ оплаты» с ЮKassa (redirect / умный платёж).
 *
 * WooCommerce → Настройки ЮKassa: сценарий «Умный платёж» (yookassa_epl).
 * Способы должны быть включены в личном кабинете ЮKassa.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_yookassa_payment_type_map')) {
	/**
	 * ID из yoga_get_checkout_payment_methods() → type в API ЮKassa.
	 *
	 * @return array<string, string>
	 */
	function yoga_yookassa_payment_type_map(): array {
		return array(
			'sbp'          => 'sbp',
			'bank_card'    => 'bank_card',
			'sberpay'      => 'sberbank',
			'tinkoff_bank' => 'tinkoff_bank',
			'yoo_money'    => 'yoo_money',
			// Отдельного type в API нет — на странице ЮKassa выбор внутри карт/Mir Pay.
			'yandex_pay'   => 'bank_card',
		);
	}
}

if (!function_exists('yoga_get_selected_yookassa_payment_type')) {
	function yoga_get_selected_yookassa_payment_type(): string {
		if (empty($_POST['yoga_checkout_payment_type'])) {
			return '';
		}

		$selected = sanitize_key(wp_unslash($_POST['yoga_checkout_payment_type']));
		$map = yoga_yookassa_payment_type_map();

		return $map[$selected] ?? '';
	}
}

if (!function_exists('yoga_yookassa_apply_payment_type_to_request')) {
	/**
	 * @param \YooKassa\Request\Payments\CreatePaymentRequest $paymentRequest
	 */
	function yoga_yookassa_apply_payment_type_to_request($paymentRequest) {
		$type = yoga_get_selected_yookassa_payment_type();
		if ($type === '' || !method_exists($paymentRequest, 'setPaymentMethodData')) {
			return $paymentRequest;
		}

		$paymentRequest->setPaymentMethodData($type);

		return $paymentRequest;
	}
}

add_filter('woocommerce_yookassa_create_payment_request', 'yoga_yookassa_apply_payment_type_to_request', 20);

if (!function_exists('yoga_yookassa_preferred_gateway_id')) {
	function yoga_yookassa_preferred_gateway_id(): string {
		if (get_option('yookassa_pay_mode') === '0') {
			return 'yookassa_widget';
		}

		return 'yookassa_epl';
	}
}

add_filter('woocommerce_available_payment_gateways', 'yoga_yookassa_auto_select_gateway', 20);
function yoga_yookassa_auto_select_gateway(array $gateways): array {
	if (!function_exists('yoga_is_theme_checkout_context') || !yoga_is_theme_checkout_context()) {
		return $gateways;
	}

	$preferred = yoga_yookassa_preferred_gateway_id();
	if (!isset($gateways[$preferred])) {
		return $gateways;
	}

	return array(
		$preferred => $gateways[$preferred],
	);
}

add_action('wp_enqueue_scripts', 'yoga_enqueue_yookassa_checkout_bridge', 25);
function yoga_enqueue_yookassa_checkout_bridge(): void {
	if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
		return;
	}

	$theme_dir = get_template_directory();
	$script_path = $theme_dir . '/assets/js/checkout-yookassa.js';
	if (!is_readable($script_path)) {
		return;
	}

	wp_enqueue_script(
		'yoga-checkout-yookassa',
		get_template_directory_uri() . '/assets/js/checkout-yookassa.js',
		array('jquery', 'yoga-checkout-payment'),
		(string) filemtime($script_path),
		true
	);

	wp_localize_script(
		'yoga-checkout-yookassa',
		'yogaYooKassa',
		array(
			'gatewayId' => yoga_yookassa_preferred_gateway_id(),
			'typeMap'   => yoga_yookassa_payment_type_map(),
		)
	);
}
