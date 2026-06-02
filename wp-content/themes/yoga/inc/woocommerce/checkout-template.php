<?php
/**
 * /checkout/ — оформление подписки. Страницы корзины нет в UX.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('template_redirect', 'yoga_redirect_cart_to_checkout', 5);
function yoga_redirect_cart_to_checkout(): void {
	if (!function_exists('is_cart') || !is_cart() || !function_exists('wc_get_checkout_url')) {
		return;
	}
	if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
		return;
	}
	wp_safe_redirect(wc_get_checkout_url());
	exit;
}

add_filter('woocommerce_get_cart_url', 'yoga_cart_url_is_checkout');
function yoga_cart_url_is_checkout(string $url): string {
	if (function_exists('wc_get_checkout_url')) {
		return wc_get_checkout_url();
	}
	return $url;
}

add_filter('template_include', 'yoga_checkout_page_template', 99);
function yoga_checkout_page_template(string $template): string {
	if (!function_exists('is_checkout') || !is_checkout()) {
		return $template;
	}
	if (function_exists('is_order_received_page') && is_order_received_page()) {
		return $template;
	}
	if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
		return $template;
	}

	$checkout_template = get_template_directory() . '/checkout.php';
	if (is_readable($checkout_template)) {
		return $checkout_template;
	}

	return $template;
}

/**
 * Checkout тарифов: только имя, фамилия, email (без адреса доставки).
 */
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

add_filter('woocommerce_checkout_fields', 'yoga_checkout_minimal_billing_fields', 20);
function yoga_checkout_minimal_billing_fields(array $fields): array {
	$remove_billing = array(
		'billing_company',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_phone',
	);

	foreach ($remove_billing as $key) {
		unset($fields['billing'][$key]);
	}

	if (isset($fields['shipping'])) {
		foreach (array_keys($fields['shipping']) as $key) {
			unset($fields['shipping'][$key]);
		}
	}

	unset($fields['order']['order_comments']);

	return $fields;
}

add_filter('woocommerce_get_country_locale', 'yoga_checkout_optional_address_locale', 20);
function yoga_checkout_optional_address_locale(array $locale): array {
	$optional_address = array(
		'address_1' => array('required' => false, 'hidden' => true),
		'address_2' => array('required' => false, 'hidden' => true),
		'city'      => array('required' => false, 'hidden' => true),
		'state'     => array('required' => false, 'hidden' => true),
		'postcode'  => array('required' => false, 'hidden' => true),
	);

	foreach (array_keys($locale) as $country_code) {
		foreach ($optional_address as $field => $args) {
			$locale[$country_code][$field] = array_merge(
				$locale[$country_code][$field] ?? array(),
				$args
			);
		}
	}

	return $locale;
}

add_filter('woocommerce_checkout_posted_data', 'yoga_checkout_billing_placeholders', 20);
function yoga_checkout_billing_placeholders(array $data): array {
	$placeholders = array(
		'billing_address_1' => '—',
		'billing_address_2' => '',
		'billing_city'      => '—',
		'billing_state'     => '',
		'billing_postcode'  => '000000',
		'billing_phone'     => '',
	);

	foreach ($placeholders as $key => $value) {
		if (empty($data[$key])) {
			$data[$key] = $value;
		}
	}

	if (empty($data['billing_country'])) {
		$data['billing_country'] = 'RU';
	}

	return $data;
}
