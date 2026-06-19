<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Checkout {
	public static function init(): void {
		add_action('woocommerce_checkout_process', array(__CLASS__, 'persist_save_opt_in_session'), 5);
		add_action('woocommerce_checkout_process', array(__CLASS__, 'validate_save_requires_phone'), 7);
		add_action('woocommerce_checkout_process', array(__CLASS__, 'map_save_checkbox_to_yookassa'), 6);
		add_action('woocommerce_checkout_create_order', array(__CLASS__, 'store_opt_in_on_order'), 25, 1);
		add_action('woocommerce_checkout_order_created', array(__CLASS__, 'store_opt_in_on_order'), 25, 1);
		add_action('woocommerce_checkout_order_created', array(__CLASS__, 'apply_phone_to_order'), 26, 1);
		add_filter('woocommerce_yookassa_create_payment_request', array(__CLASS__, 'maybe_save_payment_method'), 99);
	}

	public static function persist_save_opt_in_session(): void {
		if (!function_exists('WC') || !WC()->session) {
			return;
		}

		WC()->session->set(
			'ytr_save_payment_opt_in',
			self::customer_wants_save_payment_method() ? 'yes' : 'no'
		);
	}

	/**
	 * Плагин ЮKassa смотрит только на wc-{gateway}-new-payment-method, не на наш чекбокс.
	 */
	public static function map_save_checkbox_to_yookassa(): void {
		if (!self::customer_wants_save_payment_method() || !self::is_save_payment_supported()) {
			return;
		}

		foreach (array('yookassa_widget', 'yookassa_epl') as $gateway_id) {
			$_POST['wc-' . $gateway_id . '-new-payment-method'] = '1';
		}
	}

	/**
	 * Телефон из формы, профиля или billing meta пользователя.
	 */
	public static function resolve_checkout_phone(?int $user_id = null): string {
		if (!empty($_POST['billing_phone'])) {
			return trim((string) wp_unslash($_POST['billing_phone']));
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return '';
		}

		foreach (array('phone', 'billing_phone') as $meta_key) {
			$stored = trim((string) get_user_meta($user_id, $meta_key, true));
			if ($stored !== '') {
				return $stored;
			}
		}

		return '';
	}

	public static function has_valid_checkout_phone(string $phone = ''): bool {
		if ($phone === '') {
			$phone = self::resolve_checkout_phone();
		}

		$digits = preg_replace('/[^\d]/', '', $phone);

		return is_string($digits) && strlen($digits) >= 10;
	}

	public static function resolve_order_phone(WC_Order $order): string {
		$billing = trim((string) $order->get_billing_phone());
		if ($billing !== '' && self::has_valid_checkout_phone($billing)) {
			return $billing;
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			$user_id = get_current_user_id();
		}

		return self::resolve_checkout_phone($user_id > 0 ? $user_id : null);
	}

	public static function ensure_order_billing_phone(WC_Order $order): bool {
		$phone = trim(self::resolve_order_phone($order));
		if ($phone === '' || !self::has_valid_checkout_phone($phone)) {
			return false;
		}

		if ((string) $order->get_billing_country() === '') {
			$order->set_billing_country('RU');
		}

		if (trim((string) $order->get_billing_phone()) !== $phone) {
			$order->set_billing_phone($phone);
		}

		$order->save();

		$user_id = (int) $order->get_customer_id();
		if ($user_id > 0) {
			update_user_meta($user_id, 'phone', $phone);
			update_user_meta($user_id, 'billing_phone', $phone);
		}

		return true;
	}

	public static function order_ready_for_save(WC_Order $order): bool {
		return self::should_save_payment_method($order)
			&& self::has_valid_checkout_phone(self::resolve_order_phone($order));
	}

	public static function validate_save_requires_phone(): void {
		if (!self::user_opted_in_to_save() || !self::is_save_payment_supported()) {
			return;
		}

		if (!function_exists('wc_add_notice')) {
			return;
		}

		if (self::has_valid_checkout_phone()) {
			return;
		}

		wc_add_notice(
			__(
				'Чтобы сохранить карту для автоплатежей, укажите номер телефона в блоке «Ваши данные».',
				'yoga-tariff-renewal'
			),
			'error'
		);
	}

	public static function apply_phone_to_order(WC_Order $order): void {
		if (!YTR_Tariff::order_contains_tariff($order)) {
			return;
		}

		self::ensure_order_billing_phone($order);
	}

	public static function user_opted_in_to_save(): bool {
		return isset($_POST['yoga_save_payment_method'])
			&& (string) wp_unslash($_POST['yoga_save_payment_method']) === '1';
	}

	public static function user_declined_save_payment_method(): bool {
		return isset($_POST['yoga_save_payment_method'])
			&& (string) wp_unslash($_POST['yoga_save_payment_method']) === '0';
	}

	/**
	 * Согласие из POST или сессии checkout (на случай, если поле не попало в AJAX).
	 */
	public static function customer_wants_save_payment_method(): bool {
		if (self::user_opted_in_to_save()) {
			return true;
		}

		if (self::user_declined_save_payment_method()) {
			return false;
		}

		if (function_exists('WC') && WC()->session) {
			$stored = WC()->session->get('ytr_save_payment_opt_in');
			if ($stored === 'yes') {
				return true;
			}
			if ($stored === 'no') {
				return false;
			}
		}

		return self::order_is_tariff_checkout_context();
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
			$stored = sanitize_key((string) $order->get_meta('_yoga_checkout_payment_type'));
			if ($stored !== '') {
				return $stored;
			}
		}

		if (function_exists('WC') && WC()->session) {
			$stored = sanitize_key((string) WC()->session->get('yoga_yookassa_payment_type'));
			if ($stored !== '') {
				return $stored;
			}
		}

		return self::order_is_tariff_checkout_context($order) ? 'bank_card' : '';
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

		$opted_in = false;
		if ($order instanceof WC_Order) {
			$stored = (string) $order->get_meta('_ytr_auto_renew_opt_in');
			if ($stored === 'yes') {
				$opted_in = true;
			} elseif ($stored === 'no') {
				return false;
			} elseif (self::order_is_tariff_checkout($order)) {
				$opted_in = true;
			} else {
				$opted_in = self::customer_wants_save_payment_method();
			}
		} else {
			$opted_in = self::customer_wants_save_payment_method();
		}

		if (!$opted_in) {
			return false;
		}

		return self::is_save_payment_supported($order);
	}

	public static function ensure_order_opt_in_for_payment(WC_Order $order): void {
		if ((string) $order->get_meta('_ytr_auto_renew_opt_in') !== '') {
			return;
		}

		if (!self::order_is_tariff_checkout($order)) {
			return;
		}

		$order->update_meta_data('_ytr_auto_renew_opt_in', 'yes');
		if ((string) $order->get_meta('_yoga_checkout_payment_type') === '') {
			$order->update_meta_data('_yoga_checkout_payment_type', 'bank_card');
		}
		$order->save();
	}

	public static function store_opt_in_on_order(WC_Order $order): void {
		if (!self::order_is_tariff_checkout($order)) {
			return;
		}

		$order->update_meta_data(
			'_ytr_auto_renew_opt_in',
			self::customer_wants_save_payment_method() ? 'yes' : 'no'
		);
		$order->save();
	}

	/**
	 * Тариф в заказе или в корзине на момент checkout (до repair line items).
	 */
	public static function order_is_tariff_checkout(WC_Order $order): bool {
		if (YTR_Tariff::order_contains_tariff($order)) {
			return true;
		}

		if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
			return false;
		}

		foreach (WC()->cart->get_cart() as $cart_item) {
			$product_id = (int) ($cart_item['variation_id'] ?: $cart_item['product_id']);
			if (YTR_Tariff::is_tariff_product($product_id)) {
				return true;
			}
		}

		return false;
	}

	private static function order_is_tariff_checkout_context(?WC_Order $order = null): bool {
		if ($order instanceof WC_Order && self::order_is_tariff_checkout($order)) {
			return true;
		}

		$order = self::resolve_checkout_order();
		if ($order instanceof WC_Order && self::order_is_tariff_checkout($order)) {
			return true;
		}

		if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
			return false;
		}

		foreach (WC()->cart->get_cart() as $cart_item) {
			$product_id = (int) (($cart_item['variation_id'] ?? 0) ?: ($cart_item['product_id'] ?? 0));
			if ($product_id > 0 && YTR_Tariff::is_tariff_product($product_id)) {
				return true;
			}
		}

		return false;
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

		$order = self::resolve_checkout_order();
		if (!$order instanceof WC_Order) {
			return $payment_request;
		}

		self::ensure_order_opt_in_for_payment($order);
		self::ensure_order_billing_phone($order);

		if (!self::order_ready_for_save($order)) {
			return $payment_request;
		}

		if (method_exists($payment_request, 'setSavePaymentMethod')) {
			$payment_request->setSavePaymentMethod(true);
		}

		self::set_merchant_customer_id_on($payment_request, $order);

		return $payment_request;
	}

	/**
	 * @param object $builder
	 */
	public static function apply_merchant_customer_id_to_builder($builder, WC_Order $order): void {
		if (!self::order_ready_for_save($order)) {
			return;
		}

		self::set_merchant_customer_id_on($builder, $order);
	}

	/**
	 * Без merchant_customer_id ЮKassa не сохраняет карту для автоплатежей.
	 *
	 * @param object $target
	 */
	private static function set_merchant_customer_id_on($target, WC_Order $order): void {
		if (!method_exists($target, 'setMerchantCustomerId')) {
			return;
		}

		if (method_exists($target, 'hasMerchantCustomerId') && $target->hasMerchantCustomerId()) {
			if (!self::should_save_payment_method($order)) {
				return;
			}
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			$user_id = get_current_user_id();
		}

		$email = trim((string) $order->get_billing_email());
		if ($email === '' && $user_id > 0) {
			$user = get_userdata($user_id);
			if ($user instanceof WP_User) {
				$email = trim((string) $user->user_email);
			}
		}

		$phone = preg_replace('/[^\d]/', '', self::resolve_order_phone($order));

		if ($email !== '' && $phone !== '') {
			$target->setMerchantCustomerId(md5($email . ':' . $phone . ':' . $user_id));
		}
	}
}
