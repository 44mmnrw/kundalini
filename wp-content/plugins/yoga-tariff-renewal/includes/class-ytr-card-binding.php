<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Card_Binding {
	public const ORDER_META = '_ytr_card_binding';

	public static function init(): void {
		add_action('template_redirect', array(__CLASS__, 'handle_return_redirect'), 3);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'), 30);
	}

	public static function get_bind_amount(): float {
		$amount = (float) get_option('ytr_card_bind_amount', 1);

		return max(1.0, round($amount, 2));
	}

	public static function get_lk_url(array $args = array()): string {
		$base = function_exists('yoga_get_lk_page_url') ? yoga_get_lk_page_url() : home_url('/my-account/');
		if ($base === '') {
			$base = home_url('/my-account/');
		}

		return $args !== array() ? add_query_arg($args, $base) : $base;
	}

	/**
	 * @return array{success:bool,redirect_url:string,order_id:int,message:string}
	 */
	public static function start_for_user(int $user_id): array {
		if ($user_id <= 0) {
			return self::fail(__('Необходима авторизация', 'yoga-tariff-renewal'));
		}

		if (!class_exists('YTR_YooKassa') || !YTR_YooKassa::is_configured()) {
			return self::fail(__('ЮKassa не настроена', 'yoga-tariff-renewal'));
		}

		self::cancel_stale_pending_orders($user_id);

		$order = self::create_binding_order($user_id);
		if (is_wp_error($order)) {
			return self::fail($order->get_error_message());
		}

		$result = YTR_YooKassa::create_card_binding_payment($order);
		if (empty($result['success'])) {
			$order->update_status('failed', (string) ($result['message'] ?? ''));
			return self::fail((string) ($result['message'] ?? __('Не удалось создать платёж', 'yoga-tariff-renewal')));
		}

		$redirect = (string) ($result['confirmation_url'] ?? '');
		if ($redirect === '') {
			return self::fail(__('ЮKassa не вернула ссылку на оплату', 'yoga-tariff-renewal'));
		}

		return array(
			'success'       => true,
			'redirect_url'  => $redirect,
			'order_id'      => $order->get_id(),
			'message'       => '',
		);
	}

	/**
	 * @return WC_Order|WP_Error
	 */
	public static function create_binding_order(int $user_id) {
		$user = get_user_by('id', $user_id);
		if (!$user instanceof WP_User) {
			return new WP_Error('ytr_user', __('Пользователь не найден', 'yoga-tariff-renewal'));
		}

		$order = wc_create_order(
			array(
				'customer_id' => $user_id,
				'status'      => 'pending',
			)
		);

		if (is_wp_error($order)) {
			return $order;
		}

		$amount = self::get_bind_amount();
		$fee    = new WC_Order_Item_Fee();
		$fee->set_name(__('Привязка карты для автопродления', 'yoga-tariff-renewal'));
		$fee->set_total($amount);
		$fee->set_tax_status('none');
		$order->add_item($fee);

		$billing_email = (string) get_user_meta($user_id, 'billing_email', true);
		if ($billing_email === '') {
			$billing_email = $user->user_email;
		}

		$order->set_billing_email($billing_email);
		$order->set_billing_first_name((string) get_user_meta($user_id, 'billing_first_name', true));
		$order->set_billing_last_name((string) get_user_meta($user_id, 'billing_last_name', true));
		$order->set_billing_phone((string) get_user_meta($user_id, 'billing_phone', true));
		$order->set_payment_method('yookassa_epl');
		$order->set_payment_method_title(__('ЮKassa (привязка карты)', 'yoga-tariff-renewal'));

		$order->update_meta_data(self::ORDER_META, 'yes');
		$order->update_meta_data('_ytr_auto_renew_opt_in', 'yes');
		$order->update_meta_data('_yoga_checkout_payment_type', 'bank_card');
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	public static function is_binding_order(WC_Order $order): bool {
		return $order->get_meta(self::ORDER_META) === 'yes';
	}

	public static function complete_binding(WC_Order $order): bool {
		if (!self::is_binding_order($order)) {
			return false;
		}

		if ($order->get_meta('_ytr_card_binding_done') === 'yes') {
			return true;
		}

		if (!$order->is_paid()) {
			if (function_exists('yoga_yookassa_sync_order_payment_status')) {
				yoga_yookassa_sync_order_payment_status($order);
			}
		}

		if (!$order->is_paid()) {
			return false;
		}

		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return false;
		}

		if (class_exists('YTR_Saved_Cards')) {
			YTR_Saved_Cards::sync_from_order($order);
		}

		$payment_method_id = class_exists('YTR_YooKassa')
			? YTR_YooKassa::resolve_payment_method_id_for_order($order)
			: '';

		if ($payment_method_id === '') {
			$order->add_order_note(__('Привязка карты: способ оплаты не сохранён в ЮKassa.', 'yoga-tariff-renewal'));
			return false;
		}

		$product_id = 0;
		if (class_exists('YTR_Tariff')) {
			$tariff = YTR_Tariff::get_active_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
			}
		}

		if ($product_id > 0 && class_exists('YTR_User')) {
			YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);
			$order->add_order_note(__('Карта привязана. Автопродление тарифа включено.', 'yoga-tariff-renewal'));
		} else {
			$order->add_order_note(__('Карта привязана для будущих автоплатежей.', 'yoga-tariff-renewal'));
		}

		$order->update_meta_data('_ytr_card_binding_done', 'yes');
		$order->save();

		return true;
	}

	public static function handle_return_redirect(): void {
		if (!function_exists('yoga_yookassa_is_return_url_request') || !yoga_yookassa_is_return_url_request()) {
			return;
		}

		if (!function_exists('yoga_yookassa_get_order_by_key') || !function_exists('yoga_yookassa_get_return_order_key')) {
			return;
		}

		$order = yoga_yookassa_get_order_by_key(yoga_yookassa_get_return_order_key());
		if (!$order instanceof WC_Order || !self::is_binding_order($order)) {
			return;
		}

		if ((int) $order->get_customer_id() !== get_current_user_id()) {
			return;
		}

		self::complete_binding($order);

		$status = $order->is_paid() && $order->get_meta('_ytr_card_binding_done') === 'yes'
			? 'success'
			: 'failed';

		wp_safe_redirect(self::get_lk_url(array('ytr_card' => $status)));
		exit;
	}

	public static function enqueue_assets(): void {
		$is_lk = is_page_template('templates-page/lk.php')
			|| is_page('my-account')
			|| (function_exists('is_account_page') && is_account_page());

		if (!$is_lk) {
			return;
		}

		wp_enqueue_script(
			'ytr-lk-card-binding',
			YTR_PLUGIN_URL . 'assets/js/ytr-lk-card-binding.js',
			array('jquery'),
			YTR_VERSION,
			true
		);

		wp_localize_script(
			'ytr-lk-card-binding',
			'ytrCardBinding',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('yoga_ajax_nonce'),
				'amount'  => self::get_bind_amount(),
			)
		);
	}

	private static function cancel_stale_pending_orders(int $user_id): void {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array('pending', 'failed'),
				'limit'       => 5,
				'meta_key'    => self::ORDER_META,
				'meta_value'  => 'yes',
			)
		);

		foreach ($orders as $order) {
			if ($order instanceof WC_Order) {
				$order->update_status('cancelled', __('Отменён: новая попытка привязки карты.', 'yoga-tariff-renewal'));
			}
		}
	}

	/**
	 * @return array{success:bool,redirect_url:string,order_id:int,message:string}
	 */
	private static function fail(string $message): array {
		return array(
			'success'      => false,
			'redirect_url' => '',
			'order_id'     => 0,
			'message'      => $message,
		);
	}
}
