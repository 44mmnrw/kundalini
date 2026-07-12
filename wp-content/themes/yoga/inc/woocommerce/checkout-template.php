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
	if (function_exists('yoga_is_order_received_request') && yoga_is_order_received_request()) {
		return $template;
	}
	if (!function_exists('is_checkout') || !is_checkout()) {
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
		if ($key === 'billing_phone' && is_user_logged_in()) {
			$user_phone = trim((string) get_user_meta(get_current_user_id(), 'phone', true));
			if ($user_phone === '') {
				$user_phone = trim((string) get_user_meta(get_current_user_id(), 'billing_phone', true));
			}
			if ($user_phone !== '') {
				if (empty($data[$key])) {
					$data[$key] = $user_phone;
				}
				continue;
			}
		}

		if (empty($data[$key])) {
			$data[$key] = $value;
		}
	}

	if (empty($data['billing_country'])) {
		$data['billing_country'] = 'RU';
	}

	return $data;
}

add_filter('pre_option_woocommerce_enable_guest_checkout', 'yoga_disable_guest_checkout');
function yoga_disable_guest_checkout($value) {
	return 'no';
}

if (!function_exists('yoga_checkout_allows_guest_payment')) {
	function yoga_checkout_allows_guest_payment(): bool {
		return false;
	}
}

if (!function_exists('yoga_get_checkout_login_url')) {
	function yoga_get_checkout_login_url(): string {
		$url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');

		return add_query_arg('open_login', 'checkout', $url);
	}
}

add_action('template_redirect', 'yoga_require_login_for_checkout', 6);
function yoga_require_login_for_checkout(): void {
	if (is_user_logged_in()) {
		return;
	}

	if (function_exists('yoga_is_order_received_request') && yoga_is_order_received_request()) {
		return;
	}

	if (!function_exists('yoga_is_theme_checkout_context') || !yoga_is_theme_checkout_context()) {
		return;
	}

	if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
		return;
	}

	if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
		return;
	}

	if (isset($_GET['open_login']) && sanitize_key(wp_unslash((string) $_GET['open_login'])) === 'checkout') {
		return;
	}

	wp_safe_redirect(yoga_get_checkout_login_url());
	exit;
}

add_action('woocommerce_checkout_process', 'yoga_validate_checkout_user_logged_in', 0);
function yoga_validate_checkout_user_logged_in(): void {
	if (is_user_logged_in()) {
		return;
	}

	if (!function_exists('wc_add_notice')) {
		return;
	}

	wc_add_notice(__('Для оплаты необходимо войти или зарегистрироваться.', 'yoga'), 'error');
}

add_action('wp_ajax_yoga_apply_checkout_coupon', 'yoga_apply_checkout_coupon');
add_action('wp_ajax_nopriv_yoga_apply_checkout_coupon', 'yoga_apply_checkout_coupon');
function yoga_apply_checkout_coupon(): void {
	$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['nonce'])) : '';
	if (!wp_verify_nonce($nonce, 'yoga-apply-coupon')) {
		wp_send_json_error(array('message' => __('Не удалось проверить запрос. Обновите страницу.', 'yoga')), 403);
	}

	if (!function_exists('WC') || !WC()->cart || !function_exists('wc_coupons_enabled') || !wc_coupons_enabled()) {
		wp_send_json_error(array('message' => __('Купоны сейчас недоступны.', 'yoga')), 400);
	}

	$coupon_code = isset($_POST['coupon_code']) ? wc_format_coupon_code(wp_unslash((string) $_POST['coupon_code'])) : '';
	if ($coupon_code === '') {
		wp_send_json_error(array('message' => __('Введите промокод.', 'yoga')), 400);
	}

	wc_clear_notices();
	$applied = WC()->cart->apply_coupon($coupon_code);
	if (!$applied) {
		$errors = wc_get_notices('error');
		$message = !empty($errors[0]['notice'])
			? wp_strip_all_tags((string) $errors[0]['notice'])
			: __('Не удалось применить промокод.', 'yoga');
		wc_clear_notices();
		wp_send_json_error(array('message' => $message), 400);
	}

	WC()->cart->calculate_totals();
	WC()->cart->set_session();
	if (WC()->session) {
		WC()->session->set_customer_session_cookie(true);
		WC()->session->save_data();
	}
	wc_clear_notices();

	$discount = (float) WC()->cart->get_discount_total();
	$total = (float) WC()->cart->get_total('edit');
	$formatted_discount = yoga_format_cart_price_display($discount);
	$formatted_total = yoga_format_cart_price_display($total);

	wp_send_json_success(array(
		'message' => __('Промокод применён.', 'yoga'),
		'discount' => $formatted_discount,
		'total' => $formatted_total,
		'pay_label' => sprintf(__('оплатить %s', 'yoga'), $formatted_total),
	));
}

add_action('woocommerce_checkout_process', 'yoga_restore_checkout_coupon_from_post', 2);
function yoga_restore_checkout_coupon_from_post(): void {
	if (!function_exists('WC') || !WC()->cart || !function_exists('wc_coupons_enabled') || !wc_coupons_enabled()) {
		return;
	}

	$coupon_code = isset($_POST['coupon_code']) ? wc_format_coupon_code(wp_unslash((string) $_POST['coupon_code'])) : '';
	if ($coupon_code === '' || WC()->cart->has_discount($coupon_code)) {
		return;
	}

	if (WC()->cart->apply_coupon($coupon_code)) {
		WC()->cart->calculate_totals();
		WC()->cart->set_session();
		if (WC()->session) {
			WC()->session->save_data();
		}
	}
}
