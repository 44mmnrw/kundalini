<?php
/**
 * Страница успешной оплаты (/payment-success/) и редирект после ЮKassa.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_order_received_id_from_request')) {
	/**
	 * ID заказа из endpoint order-received (fallback по URL, если WC ещё не выставил query var).
	 */
	function yoga_get_order_received_id_from_request(): int {
		global $wp;

		if (isset($wp->query_vars['order-received'])) {
			$order_id = absint($wp->query_vars['order-received']);
			if ($order_id > 0) {
				return $order_id;
			}
		}

		$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
		if ($uri !== '' && preg_match('#/order-received/(\d+)#', $uri, $matches)) {
			return absint($matches[1]);
		}

		return 0;
	}
}

if (!function_exists('yoga_is_order_received_request')) {
	function yoga_is_order_received_request(): bool {
		if (function_exists('is_order_received_page') && is_order_received_page()) {
			return true;
		}

		return yoga_get_order_received_id_from_request() > 0;
	}
}

if (!function_exists('yoga_is_payment_success_screen')) {
	function yoga_is_payment_success_screen(): bool {
		if (is_page_template('templates-page/payment-success.php')) {
			return true;
		}

		return yoga_is_order_received_request();
	}
}

if (!function_exists('yoga_get_payment_success_page_url')) {
	function yoga_get_payment_success_page_url(): string {
		$pages = get_pages(array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => 'templates-page/payment-success.php',
			'number'     => 1,
			'post_status' => 'publish',
		));
		if (!empty($pages)) {
			return get_permalink($pages[0]->ID);
		}
		return home_url('/payment-success/');
	}
}

if (!function_exists('yoga_get_payment_success_order_from_request')) {
	/**
	 * @return WC_Order|null
	 */
	function yoga_get_payment_success_order_from_request() {
		if (!function_exists('wc_get_order')) {
			return null;
		}

		if (yoga_is_order_received_request()) {
			$order_id = yoga_get_order_received_id_from_request();
			if ($order_id <= 0) {
				return null;
			}
			$order = wc_get_order($order_id);
			if (!$order instanceof WC_Order) {
				return null;
			}
			$key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
			if ($key === '' || !hash_equals($order->get_order_key(), $key)) {
				return null;
			}
			return $order;
		}

		$order_id = isset($_GET['order']) ? absint($_GET['order']) : 0;
		$key      = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
		if ($order_id <= 0 || $key === '') {
			return null;
		}
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order || !hash_equals($order->get_order_key(), $key)) {
			return null;
		}

		return $order;
	}
}

if (!function_exists('yoga_get_payment_success_order_from_session')) {
	/**
	 * @return WC_Order|null
	 */
	function yoga_get_payment_success_order_from_session() {
		if (!function_exists('WC') || !WC()->session) {
			return null;
		}
		$order_id = absint(WC()->session->get('yoga_last_paid_order_id'));
		if ($order_id <= 0) {
			return null;
		}
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			return null;
		}
		$allowed = array('pending', 'processing', 'completed', 'on-hold');
		if (!in_array($order->get_status(), $allowed, true)) {
			return null;
		}
		return $order;
	}
}

if (!function_exists('yoga_store_payment_success_order_in_session')) {
	function yoga_store_payment_success_order_in_session(int $order_id): void {
		if ($order_id <= 0 || !function_exists('WC') || !WC()->session) {
			return;
		}
		WC()->session->set('yoga_last_paid_order_id', $order_id);
	}
}

add_action('woocommerce_checkout_order_processed', 'yoga_store_payment_success_order_on_checkout', 20, 1);
function yoga_store_payment_success_order_on_checkout($order_id): void {
	yoga_store_payment_success_order_in_session((int) $order_id);
}

add_action('woocommerce_payment_complete', 'yoga_store_payment_success_order_on_payment', 20, 1);
function yoga_store_payment_success_order_on_payment($order_id): void {
	yoga_store_payment_success_order_in_session((int) $order_id);
}

if (!function_exists('yoga_get_payment_success_order')) {
	/**
	 * @return WC_Order|null
	 */
	function yoga_get_payment_success_order() {
		$order = yoga_get_payment_success_order_from_request();
		if ($order instanceof WC_Order) {
			return $order;
		}

		$order = yoga_get_payment_success_order_from_session();
		if ($order instanceof WC_Order) {
			return $order;
		}

		if (!is_user_logged_in() || !function_exists('wc_get_orders')) {
			return null;
		}

		$orders = wc_get_orders(array(
			'customer_id' => get_current_user_id(),
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'status'      => array('pending', 'processing', 'completed', 'on-hold'),
			'date_created' => '>' . (time() - 3 * HOUR_IN_SECONDS),
		));

		if (empty($orders) || !$orders[0] instanceof WC_Order) {
			return null;
		}

		return $orders[0];
	}
}

if (!function_exists('yoga_get_order_tariff_name')) {
	function yoga_get_order_tariff_name(WC_Order $order): string {
		foreach ($order->get_items() as $item) {
			$name = $item->get_name();
			if ($name !== '') {
				return $name;
			}
		}
		return __('тариф', 'yoga');
	}
}

if (!function_exists('yoga_get_order_subscription_end_timestamp')) {
	function yoga_get_order_subscription_end_timestamp(WC_Order $order): int {
		if (function_exists('wcs_get_subscriptions_for_order')) {
			$subscriptions = wcs_get_subscriptions_for_order($order->get_id(), array('order_type' => 'any'));
			foreach ($subscriptions as $subscription) {
				if (!is_object($subscription) || !method_exists($subscription, 'get_date')) {
					continue;
				}
				$end = $subscription->get_date('end');
				if ($end) {
					$ts = strtotime($end);
					if ($ts) {
						return $ts;
					}
				}
			}
		}

		$start = $order->get_date_completed();
		if (!$start) {
			$start = $order->get_date_created();
		}
		$base_ts = $start ? $start->getTimestamp() : time();

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if ($product_id <= 0 || !function_exists('get_field')) {
				continue;
			}
			$period = (string) get_field('price_period', $product_id);
			if ($period === 'year') {
				return (int) strtotime('+1 year', $base_ts);
			}
			if ($period === 'month') {
				return (int) strtotime('+1 month', $base_ts);
			}
		}

		return (int) strtotime('+1 year', $base_ts);
	}
}

if (!function_exists('yoga_format_subscription_end_label')) {
	function yoga_format_subscription_end_label(int $timestamp): string {
		if ($timestamp <= 0) {
			return '';
		}
		$date = wp_date('j F Y', $timestamp);
		return sprintf(
			/* translators: %s: formatted date, e.g. 15 сентября 2027 */
			__('До %s года', 'yoga'),
			$date
		);
	}
}

if (!function_exists('yoga_get_payment_success_support_email')) {
	function yoga_get_payment_success_support_email(): string {
		$email = '';
		if (function_exists('get_field')) {
			$email = trim((string) get_field('contacts_email', 'option'));
		}
		if ($email === '' || !is_email($email)) {
			$email = 'yuoga@mail.ru';
		}
		return $email;
	}
}

if (!function_exists('yoga_get_payment_success_context')) {
	/**
	 * @return array{tariff_name:string,subscription_end:string,home_url:string,support_email:string,has_order:bool}
	 */
	function yoga_get_payment_success_context(): array {
		$order = yoga_get_payment_success_order();
		$tariff_name = '';
		$subscription_end = '';

		if ($order instanceof WC_Order) {
			$tariff_name = yoga_get_order_tariff_name($order);
			$subscription_end = yoga_format_subscription_end_label(
				yoga_get_order_subscription_end_timestamp($order)
			);
		}

		return array(
			'tariff_name'       => $tariff_name,
			'subscription_end'  => $subscription_end,
			'home_url'          => home_url('/'),
			'support_email'     => yoga_get_payment_success_support_email(),
			'has_order'         => $order instanceof WC_Order,
		);
	}
}

if (!function_exists('yoga_ensure_payment_success_page')) {
	function yoga_ensure_payment_success_page(): int {
		$existing = get_pages(array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => 'templates-page/payment-success.php',
			'number'     => 1,
			'post_status' => array('publish', 'draft', 'private'),
		));
		if (!empty($existing)) {
			$page_id = (int) $existing[0]->ID;
			if (get_post_status($page_id) !== 'publish') {
				wp_update_post(array(
					'ID'          => $page_id,
					'post_status' => 'publish',
				));
			}
			return $page_id;
		}

		$by_path = get_page_by_path('payment-success');
		if ($by_path instanceof WP_Post) {
			update_post_meta($by_path->ID, '_wp_page_template', 'templates-page/payment-success.php');
			if (get_post_status($by_path->ID) !== 'publish') {
				wp_update_post(array(
					'ID'          => $by_path->ID,
					'post_status' => 'publish',
				));
			}
			return (int) $by_path->ID;
		}

		$page_id = wp_insert_post(array(
			'post_title'   => __('Оплата прошла успешно', 'yoga'),
			'post_name'    => 'payment-success',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		), true);

		if (is_wp_error($page_id)) {
			return 0;
		}

		update_post_meta($page_id, '_wp_page_template', 'templates-page/payment-success.php');

		return (int) $page_id;
	}
}

if (!function_exists('yoga_configure_yookassa_success_redirect')) {
	function yoga_configure_yookassa_success_redirect(): void {
		if (!function_exists('get_option')) {
			return;
		}

		$page_id = yoga_ensure_payment_success_page();
		if ($page_id <= 0) {
			return;
		}

		$current = get_option('yookassa_success');
		$allowed_builtin = array('wc_success', 'wc_checkout', 'wc_payment', false, '');

		if (in_array($current, $allowed_builtin, true)) {
			update_option('yookassa_success', (string) $page_id);
		}
	}
}
add_action('init', 'yoga_configure_yookassa_success_redirect', 20);

add_action('template_redirect', 'yoga_redirect_order_received_to_payment_success', 5);
function yoga_redirect_order_received_to_payment_success(): void {
	$order_id = yoga_get_order_received_id_from_request();
	if ($order_id <= 0 || !function_exists('wc_get_order')) {
		return;
	}

	$key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
	if ($key === '') {
		return;
	}

	$order = wc_get_order($order_id);
	if (!$order instanceof WC_Order || !hash_equals($order->get_order_key(), $key)) {
		return;
	}

	$target = add_query_arg(
		array(
			'order' => $order->get_id(),
			'key'   => $order->get_order_key(),
		),
		yoga_get_payment_success_page_url()
	);

	wp_safe_redirect($target);
	exit;
}

add_filter('template_include', 'yoga_payment_success_page_template', 98);
function yoga_payment_success_page_template(string $template): string {
	if (!yoga_is_payment_success_screen()) {
		return $template;
	}

	$custom = get_template_directory() . '/payment-success.php';
	if (is_readable($custom)) {
		return $custom;
	}

	return $template;
}
