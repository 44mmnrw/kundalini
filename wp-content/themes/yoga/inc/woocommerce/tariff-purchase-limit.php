<?php
/** Limits on repeated purchases of a tariff, shared by all its variations. */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_tariff_purchase_limit_product(int $product_id) {
	$product = wc_get_product($product_id);
	if (!$product) {
		return null;
	}
	if ($product->get_parent_id() > 0) {
		$product = wc_get_product((int) $product->get_parent_id());
	}
	return $product && yoga_product_is_tariff((int) $product->get_id()) ? $product : null;
}

function yoga_tariff_purchase_limit_months(int $product_id): int {
	$product = yoga_tariff_purchase_limit_product($product_id);
	return $product ? max(0, min(120, (int) $product->get_meta('_yoga_repurchase_months'))) : 0;
}

/** Calendar months, preserving local time and clamping the day at month end. */
function yoga_tariff_next_purchase_at(int $paid_at, int $months): int {
	$paid = (new DateTimeImmutable('@' . $paid_at))->setTimezone(wp_timezone());
	$month = $paid->modify('first day of this month')->modify('+' . $months . ' months');
	return $month->setDate((int) $month->format('Y'), (int) $month->format('n'), min((int) $paid->format('j'), (int) $month->format('t')))->getTimestamp();
}

/** Use the WooCommerce query API for both legacy order storage and HPOS. */
function yoga_tariff_purchase_history(int $user_id, bool $refresh = false): array {
	static $history = array();
	if ($refresh || !isset($history[$user_id])) {
		$cutoff = (new DateTimeImmutable('now', wp_timezone()))->modify('-121 months')->getTimestamp();
		$history[$user_id] = wc_get_orders(array(
			'customer_id' => $user_id,
			'status' => wc_get_is_paid_statuses(),
			'date_paid' => '>' . $cutoff,
			'limit' => -1,
			'return' => 'objects',
		));
	}
	return $history[$user_id];
}

/** Guests may select a tariff before login; checkout/payment require an account. */
function yoga_tariff_purchase_limit_error(int $product_id, int $user_id, int $exclude_order_id = 0, bool $refresh = false): ?WP_Error {
	$product = yoga_tariff_purchase_limit_product($product_id);
	$months = $product ? yoga_tariff_purchase_limit_months((int) $product->get_id()) : 0;
	if (!$months || $user_id <= 0) {
		return null;
	}
	$next_at = 0;
	foreach (yoga_tariff_purchase_history($user_id, $refresh) as $order) {
		if ((int) $order->get_id() === $exclude_order_id || $order->get_meta('_ytr_card_binding') === 'yes') {
			continue;
		}
		$paid = $order->get_date_paid();
		if (!$paid) {
			continue;
		}
		foreach ($order->get_items() as $item) {
			if ((int) $item->get_product_id() === (int) $product->get_id()) {
				$next_at = max($next_at, yoga_tariff_next_purchase_at($paid->getTimestamp(), $months));
				break;
			}
		}
	}
	if ($next_at <= time()) {
		return null;
	}
	return new WP_Error('yoga_tariff_purchase_limit', sprintf(
		__('Повторная покупка тарифа «%1$s» будет доступна %2$s.', 'yoga'),
		wp_strip_all_tags((string) $product->get_name()),
		wp_date('d.m.Y H:i', $next_at)
	), array('next_purchase_at' => $next_at));
}

function yoga_tariff_order_purchase_limit_error($order): ?WP_Error {
	if (!$order instanceof WC_Order || $order->get_meta('_ytr_card_binding') === 'yes') {
		return null;
	}
	$user_id = (int) $order->get_customer_id();
	foreach ($order->get_items() as $item) {
		$product_id = (int) $item->get_product_id();
		if (yoga_tariff_purchase_limit_months($product_id) <= 0) {
			continue;
		}
		if ($user_id <= 0) {
			return new WP_Error('yoga_tariff_purchase_login', __('Для покупки этого тарифа войдите в аккаунт.', 'yoga'));
		}
		$error = yoga_tariff_purchase_limit_error($product_id, $user_id, (int) $order->get_id(), true);
		if ($error) {
			return $error;
		}
	}
	return null;
}

function yoga_render_tariff_purchase_limit_field(): void {
	global $product_object;
	if ($product_object && $product_object->get_id() && !yoga_product_is_tariff((int) $product_object->get_id())) {
		return;
	}
	woocommerce_wp_text_input(array(
		'id' => '_yoga_repurchase_months',
		'label' => __('Повторная покупка через, месяцев', 'yoga'),
		'type' => 'number',
		'value' => $product_object ? (int) $product_object->get_meta('_yoga_repurchase_months') : 0,
		'custom_attributes' => array('min' => '0', 'max' => '120', 'step' => '1'),
		'description' => __('0: без ограничений. Например, 6: один раз в полгода с последней успешной оплаты. Ограничение действует для одного аккаунта, всех периодов этого тарифа и автопродлений. Неоплаченные, отменённые и полностью возвращённые заказы не учитываются.', 'yoga'),
	));
}
add_action('woocommerce_product_options_general_product_data', 'yoga_render_tariff_purchase_limit_field');

function yoga_save_tariff_purchase_limit_field($product): void {
	if (!isset($_POST['_yoga_repurchase_months'])) {
		return;
	}
	$value = trim((string) wp_unslash($_POST['_yoga_repurchase_months']));
	$months = $value === '' ? 0 : filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 120)));
	if ($months === false) {
		WC_Admin_Meta_Boxes::add_error(__('Интервал повторной покупки должен быть целым числом от 0 до 120 месяцев.', 'yoga'));
		return;
	}
	$product->update_meta_data('_yoga_repurchase_months', $months);
}
add_action('woocommerce_admin_process_product_object', 'yoga_save_tariff_purchase_limit_field');

function yoga_tariff_limit_purchasable($purchasable, $product): bool {
	return $purchasable && !yoga_tariff_purchase_limit_error((int) $product->get_id(), get_current_user_id());
}
add_filter('woocommerce_is_purchasable', 'yoga_tariff_limit_purchasable', 20, 2);
add_filter('woocommerce_variation_is_purchasable', 'yoga_tariff_limit_purchasable', 20, 2);

function yoga_tariff_limit_purchase_message($message, $product): string {
	$error = yoga_tariff_purchase_limit_error((int) $product->get_id(), get_current_user_id());
	return $error ? $error->get_error_message() : $message;
}
add_filter('woocommerce_cart_product_cannot_be_purchased_message', 'yoga_tariff_limit_purchase_message', 20, 2);

function yoga_validate_tariff_purchase_cart(): void {
	if (!function_exists('WC') || !WC()->cart) {
		return;
	}
	$user_id = get_current_user_id();
	foreach (WC()->cart->get_cart() as $item) {
		$product_id = (int) ($item['variation_id'] ?? 0) ?: (int) ($item['product_id'] ?? 0);
		if (yoga_tariff_purchase_limit_months($product_id) <= 0) {
			continue;
		}
		$error = $user_id > 0 ? yoga_tariff_purchase_limit_error($product_id, $user_id, 0, true) : new WP_Error('yoga_tariff_purchase_login', __('Для покупки этого тарифа войдите в аккаунт.', 'yoga'));
		if ($error && !wc_has_notice($error->get_error_message(), 'error')) {
			wc_add_notice($error->get_error_message(), 'error');
		}
	}
}
add_action('woocommerce_check_cart_items', 'yoga_validate_tariff_purchase_cart');
add_action('woocommerce_checkout_process', 'yoga_validate_tariff_purchase_cart', 20);

function yoga_validate_tariff_order_payment($order): void {
	$error = yoga_tariff_order_purchase_limit_error($order);
	if ($error) {
		throw new Exception($error->get_error_message());
	}
}
add_action('woocommerce_before_pay_action', 'yoga_validate_tariff_order_payment');
