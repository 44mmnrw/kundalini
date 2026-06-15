<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_LK {
	public const META_CANCELLED_AT = '_ytr_auto_renew_cancelled_at';

	public static function init(): void {
		add_action('wp_ajax_ytr_cancel_auto_renew', array(__CLASS__, 'ajax_cancel_auto_renew'));
		add_action('wp_ajax_ytr_remove_payment_method', array(__CLASS__, 'ajax_remove_payment_method'));
		add_action('wp_ajax_remove_payment_method', array(__CLASS__, 'ajax_remove_payment_method'), 5);
		add_action('wp_ajax_ytr_bind_card_start', array(__CLASS__, 'ajax_bind_card_start'));
		add_action('wp_ajax_add_payment_method', array(__CLASS__, 'ajax_bind_card_start'), 5);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'), 35);
	}

	public static function enqueue_assets(): void {
		$is_lk = is_page_template('templates-page/lk.php')
			|| is_page('my-account')
			|| (function_exists('is_account_page') && is_account_page());

		if (!$is_lk) {
			return;
		}

		$js_path = YTR_PLUGIN_DIR . 'assets/js/ytr-lk-modals.js';
		$js_ver  = file_exists($js_path) ? (string) filemtime($js_path) : YTR_VERSION;

		wp_enqueue_script(
			'ytr-lk-modals',
			YTR_PLUGIN_URL . 'assets/js/ytr-lk-modals.js',
			array('jquery'),
			$js_ver,
			true
		);

		wp_localize_script(
			'ytr-lk-modals',
			'ytrLkModals',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('yoga_ajax_nonce'),
			)
		);
	}

	public static function was_auto_renew_cancelled(int $user_id): bool {
		if ($user_id <= 0) {
			return false;
		}

		return (string) get_user_meta($user_id, self::META_CANCELLED_AT, true) !== '';
	}

	/**
	 * Включает автопродление по последнему оплаченному тарифу с галочкой сохранения карты.
	 */
	public static function maybe_sync_auto_renew_from_latest_order(int $user_id): void {
		if (
			$user_id <= 0
			|| !class_exists('YTR_User')
			|| !class_exists('YTR_Tariff')
			|| !class_exists('YTR_YooKassa')
		) {
			return;
		}

		if (self::was_auto_renew_cancelled($user_id)) {
			return;
		}

		if (YTR_User::is_auto_renew_enabled($user_id)) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 10,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array('processing', 'completed'),
			)
		);

		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}

			if (!YTR_Tariff::order_contains_tariff($order)) {
				continue;
			}

			if ($order->get_meta('_ytr_renewal') === 'yes') {
				continue;
			}

			if ($order->get_meta('_ytr_auto_renew_opt_in') !== 'yes') {
				continue;
			}

			$product_id = YTR_Tariff::get_tariff_product_id_from_order($order);
			if ($product_id <= 0) {
				continue;
			}

			$payment_method_id = YTR_YooKassa::resolve_payment_method_id_for_order($order);
			if ($payment_method_id === '') {
				continue;
			}

			YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);

			if (class_exists('YTR_Saved_Cards')) {
				YTR_Saved_Cards::clear_sync_pause($user_id);
				YTR_Saved_Cards::sync_from_order($order);
			}

			return;
		}
	}

	/** @deprecated Use maybe_sync_auto_renew_from_latest_order() */
	public static function maybe_reactivate_auto_renew_after_repurchase(int $user_id): void {
		self::maybe_sync_auto_renew_from_latest_order($user_id);
	}

	/**
	 * Текст статуса автопродления для блока в настройках подписки.
	 */
	public static function get_auto_renew_status_text(int $user_id, string $access_end_date): string {
		if ($user_id <= 0 || $access_end_date === '') {
			return '';
		}

		if (self::user_has_renewable_payment_setup($user_id)) {
			return '';
		}

		if (self::was_auto_renew_cancelled($user_id)) {
			return sprintf(
				/* translators: %s: access end date */
				__(
					'Доступ сохранится до %s. Тариф не продлится автоматически, списаний не будет.',
					'yoga-tariff-renewal'
				),
				$access_end_date
			);
		}

		if (class_exists('YTR_User') && !YTR_User::is_auto_renew_enabled($user_id)) {
			return sprintf(
				/* translators: %s: access end date */
				__(
					'Доступ сохранится до %s. Чтобы включить автопродление, оплатите тариф с галочкой сохранения способа оплаты.',
					'yoga-tariff-renewal'
				),
				$access_end_date
			);
		}

		return '';
	}

	public static function cancel_auto_renew(int $user_id): bool {
		$result = self::cancel_subscription($user_id);

		return !empty($result['success']);
	}

	/**
	 * Отмена автопродления: отключает списания, но сохраняет карту в ЛК.
	 * Доступ к тарифу сохраняется до конца оплаченного периода.
	 *
	 * @return array{success:bool,message:string,access_end:string,card_removed:bool}
	 */
	public static function cancel_subscription(int $user_id): array {
		$fail = static function (string $message): array {
			return array(
				'success'      => false,
				'message'      => $message,
				'access_end'   => '',
				'card_removed' => false,
			);
		};

		if ($user_id <= 0) {
			return $fail(__('Необходима авторизация', 'yoga-tariff-renewal'));
		}

		if (
			!self::user_has_renewable_payment_setup($user_id)
			&& !YTR_User::has_auto_renew_meta($user_id)
		) {
			return $fail(__('Автопродление уже отключено', 'yoga-tariff-renewal'));
		}

		$access_end = '';
		if (function_exists('get_current_user_tariff')) {
			$tariff = get_current_user_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['access_end_date'])) {
				$access_end = (string) $tariff['access_end_date'];
			}
		}
		if ($access_end === '' && function_exists('get_user_active_subscription')) {
			$subscription = get_user_active_subscription();
			if (is_array($subscription) && !empty($subscription['end_date'])) {
				$timestamp = strtotime((string) $subscription['end_date']);
				$access_end = $timestamp ? date('d.m.Y', $timestamp) : (string) $subscription['end_date'];
			}
		}

		YTR_User::disable_auto_renew($user_id, true);

		$message = $access_end !== ''
			? sprintf(
				/* translators: %s: access end date */
				__(
					'Автопродление отключено. Доступ сохранится до %s. Тариф не продлится автоматически.',
					'yoga-tariff-renewal'
				),
				$access_end
			)
			: __(
				'Автопродление отключено. Доступ сохранится до конца оплаченного периода. Тариф не продлится автоматически.',
				'yoga-tariff-renewal'
			);

		return array(
			'success'      => true,
			'message'      => $message,
			'access_end'   => $access_end,
			'card_removed' => false,
		);
	}

	private static function remove_auto_renew_saved_method(int $user_id, string $payment_method_id): bool {
		if ($user_id <= 0 || $payment_method_id === '' || !class_exists('YTR_Saved_Cards')) {
			return false;
		}

		foreach (YTR_Saved_Cards::get_cards($user_id) as $card) {
			if (!is_array($card)) {
				continue;
			}

			$card_id = (string) ($card['id'] ?? '');
			$card_pm = (string) ($card['payment_method_id'] ?? '');

			if ($card_id === '' || ($card_pm !== $payment_method_id && $card_id !== $payment_method_id)) {
				continue;
			}

			return YTR_Saved_Cards::remove_card($user_id, $card_id);
		}

		return false;
	}

	private static function remove_all_recurring_saved_methods(int $user_id): bool {
		if ($user_id <= 0 || !class_exists('YTR_Saved_Cards')) {
			return false;
		}

		$removed = false;

		foreach (YTR_Saved_Cards::get_cards($user_id) as $card) {
			if (!is_array($card) || empty($card['recurring'])) {
				continue;
			}

			$card_id = (string) ($card['id'] ?? '');
			if ($card_id === '') {
				continue;
			}

			if (YTR_Saved_Cards::remove_card($user_id, $card_id)) {
				$removed = true;
			}
		}

		return $removed;
	}

	private static function remove_all_saved_cards(int $user_id): bool {
		if ($user_id <= 0 || !class_exists('YTR_Saved_Cards')) {
			return false;
		}

		$removed = false;

		foreach (YTR_Saved_Cards::get_cards($user_id) as $card) {
			if (!is_array($card)) {
				continue;
			}

			$card_id = (string) ($card['id'] ?? '');
			if ($card_id === '') {
				continue;
			}

			if (YTR_Saved_Cards::remove_card($user_id, $card_id)) {
				$removed = true;
			}
		}

		return $removed;
	}

	public static function is_auto_renew_active_for_user(int $user_id): bool {
		return $user_id > 0 && self::user_has_renewable_payment_setup($user_id);
	}

	/**
	 * Есть сохранённый в ЮKassa способ оплаты для автопродления (meta или карта в ЛК).
	 */
	public static function user_has_renewable_payment_setup(int $user_id): bool {
		if ($user_id <= 0) {
			return false;
		}

		if (self::was_auto_renew_cancelled($user_id)) {
			return false;
		}

		return YTR_User::is_auto_renew_enabled($user_id);
	}

	/**
	 * Восстанавливает флаг автопродления, если в ЛК уже есть recurring-карта от ЮKassa.
	 */
	public static function maybe_backfill_auto_renew(int $user_id): void {
		if ($user_id <= 0 || YTR_User::is_auto_renew_enabled($user_id) || !class_exists('YTR_Saved_Cards')) {
			return;
		}

		if (self::was_auto_renew_cancelled($user_id)) {
			return;
		}

		$product_id = 0;
		if (function_exists('get_current_user_tariff')) {
			$tariff = get_current_user_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
			}
		}

		if ($product_id <= 0 && class_exists('YTR_Tariff')) {
			$tariff = YTR_Tariff::get_active_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
			}
		}

		if ($product_id <= 0) {
			return;
		}

		foreach (YTR_Saved_Cards::get_cards($user_id) as $card) {
			if (!is_array($card) || empty($card['recurring'])) {
				continue;
			}

			$payment_method_id = (string) ($card['payment_method_id'] ?? '');
			if ($payment_method_id === '') {
				continue;
			}

			YTR_User::enable_auto_renew($user_id, $product_id, $payment_method_id);
			return;
		}
	}

	public static function ajax_cancel_auto_renew(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(array('message' => 'Необходима авторизация'), 401);
		}

		if (
			!isset($_POST['security'])
			|| !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'yoga_ajax_nonce')
		) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'), 403);
		}

		$user_id = get_current_user_id();
		$result  = self::cancel_subscription($user_id);

		if (empty($result['success'])) {
			wp_send_json_error(
				array(
					'message' => (string) ($result['message'] ?? __('Не удалось отменить автопродление', 'yoga-tariff-renewal')),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'message'      => (string) $result['message'],
				'access_end'   => (string) $result['access_end'],
				'card_removed' => !empty($result['card_removed']),
			)
		);
	}

	public static function ajax_remove_payment_method(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(array('message' => __('Необходима авторизация', 'yoga-tariff-renewal')), 401);
		}

		if (
			!isset($_POST['security'])
			|| (
				!wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'yoga_ajax_nonce')
				&& !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'remove_payment_method')
			)
		) {
			wp_send_json_error(array('message' => __('Ошибка безопасности', 'yoga-tariff-renewal')), 403);
		}

		if (!class_exists('YTR_Saved_Cards')) {
			wp_send_json_error(array('message' => __('Модуль карт недоступен', 'yoga-tariff-renewal')), 500);
		}

		$user_id = get_current_user_id();
		$card_id = sanitize_text_field(wp_unslash((string) ($_POST['card_id'] ?? '')));
		$had_auto_renew = class_exists('YTR_User') && YTR_User::is_auto_renew_enabled($user_id);

		if (!YTR_Saved_Cards::remove_card($user_id, $card_id)) {
			wp_send_json_error(array('message' => __('Карта не найдена', 'yoga-tariff-renewal')), 404);
		}

		$message = __('Карта удалена', 'yoga-tariff-renewal');
		if ($had_auto_renew && class_exists('YTR_User') && !YTR_User::is_auto_renew_enabled($user_id)) {
			$message = __('Карта удалена. Автопродление отключено. Доступ сохранится до конца оплаченного периода.', 'yoga-tariff-renewal');
		}

		wp_send_json_success(array('message' => $message));
	}

	public static function ajax_bind_card_start(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(array('message' => __('Необходима авторизация', 'yoga-tariff-renewal')), 401);
		}

		if (
			!isset($_POST['security'])
			|| !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'yoga_ajax_nonce')
		) {
			wp_send_json_error(array('message' => __('Ошибка безопасности', 'yoga-tariff-renewal')), 403);
		}

		if (!class_exists('YTR_Card_Binding')) {
			wp_send_json_error(array('message' => __('Модуль привязки карты недоступен', 'yoga-tariff-renewal')), 500);
		}

		$result = YTR_Card_Binding::start_for_user(get_current_user_id());
		if (empty($result['success'])) {
			wp_send_json_error(
				array(
					'message' => (string) ($result['message'] ?? __('Не удалось начать привязку карты', 'yoga-tariff-renewal')),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'redirect_url' => (string) $result['redirect_url'],
				'order_id'     => (int) $result['order_id'],
				'message'      => sprintf(
					/* translators: %s: amount in rubles */
					__('Перенаправляем на страницу ЮKassa для привязки карты (%s ₽).', 'yoga-tariff-renewal'),
					number_format(YTR_Card_Binding::get_bind_amount(), 0, '', ' ')
				),
			)
		);
	}
}
