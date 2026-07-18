<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Безопасное хранение способов оплаты для ЛК.
 * На сайте — только маска (last4, бренд, срок) и ID токена ЮKassa, без PAN/CVC.
 */
final class YTR_Saved_Cards {
	public const META_KEY = '_ytr_saved_cards';
	public const REMOVAL_SNAPSHOT_META = '_ytr_removed_card_snapshot';
	public const REMOVED_CARDS_META = '_ytr_removed_cards';
	public const SYNC_PAUSED_AT_META = '_ytr_cards_sync_paused_at';

	public static function init(): void {
		add_action('woocommerce_payment_complete', array(__CLASS__, 'sync_from_order_id'), 25, 1);
		add_action('woocommerce_order_status_completed', array(__CLASS__, 'sync_from_order_id'), 25, 1);
		add_action('woocommerce_order_status_processing', array(__CLASS__, 'sync_from_order_id'), 25, 1);
		add_action('template_redirect', array(__CLASS__, 'maybe_sync_cards_on_lk'), 5);
	}

	public static function maybe_sync_cards_on_lk(): void {
		if (!is_user_logged_in()) {
			return;
		}

		$is_lk = is_page_template('templates-page/lk.php')
			|| (function_exists('is_account_page') && is_account_page())
			|| is_page('my-account');

		if (!$is_lk) {
			return;
		}

		$user_id = get_current_user_id();

		self::sync_cards_for_user($user_id, true);

		if (class_exists('YTR_LK')) {
			YTR_LK::maybe_sync_auto_renew_from_latest_order($user_id);
		}

		self::sync_renewal_state($user_id);

		if (class_exists('YTR_LK')) {
			YTR_LK::maybe_backfill_auto_renew($user_id);
		}
	}

	/**
	 * Сбрасывает автопродление, если карты нет, а в meta остались флаги списаний.
	 */
	public static function sync_renewal_state(int $user_id): void {
		if ($user_id <= 0 || !class_exists('YTR_User')) {
			return;
		}

		if (!YTR_User::has_auto_renew_meta($user_id)) {
			return;
		}

		foreach (self::get_cards($user_id) as $card) {
			if (!is_array($card) || empty($card['recurring'])) {
				continue;
			}

			if ((string) ($card['payment_method_id'] ?? '') !== '') {
				return;
			}
		}

		// Сиротские флаги без карты — сбрасываем, но не помечаем как отмену пользователем.
		if (self::has_recent_save_payment_candidate($user_id)) {
			return;
		}

		YTR_User::disable_auto_renew($user_id, false);
	}

	/**
	 * Подтягивает карты из оплаченных заказов ЮKassa в meta для ЛК.
	 *
	 * @param bool $opt_in_only Только _ytr_auto_renew_opt_in=yes или привязка карты.
	 */
	private static function has_recent_save_payment_candidate(int $user_id): bool {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 5,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array('completed', 'processing', 'pending', 'on-hold'),
			)
		);

		$threshold = time() - (2 * HOUR_IN_SECONDS);

		foreach ($orders as $order) {
			if (!$order instanceof WC_Order || !YTR_Tariff::order_contains_tariff($order)) {
				continue;
			}

			$created = $order->get_date_created();
			if (!$created || $created->getTimestamp() < $threshold) {
				continue;
			}

			if ((string) $order->get_meta('_ytr_auto_renew_opt_in') === 'no') {
				continue;
			}

			if (self::sync_from_order($order)) {
				return true;
			}

			return true;
		}

		return false;
	}

	public static function sync_cards_for_user(int $user_id, bool $opt_in_only = true): void {
		if ($user_id <= 0) {
			return;
		}

		if (self::is_historical_sync_paused($user_id) && self::get_cards($user_id) === array()) {
			return;
		}

		$query_args = array(
			'customer_id' => $user_id,
			'limit'       => 20,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'status'      => array('completed', 'processing'),
		);

		if ($opt_in_only) {
			$query_args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_ytr_auto_renew_opt_in',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'key'     => '_ytr_auto_renew_opt_in',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => YTR_Card_Binding::ORDER_META,
					'value' => 'yes',
				),
			);
		}

		$orders = wc_get_orders($query_args);

		foreach ($orders as $order) {
			if ($order instanceof WC_Order) {
				self::sync_from_order($order);
			}
		}
	}

	public static function sync_from_order_id(int $order_id): void {
		$order = wc_get_order($order_id);
		if ($order instanceof WC_Order) {
			self::sync_from_order($order);
		}
	}

	public static function sync_from_order(WC_Order $order): bool {
		$user_id = (int) $order->get_customer_id();
		if ($user_id <= 0) {
			return false;
		}

		if (self::is_historical_sync_paused($user_id) && !self::order_can_resume_card_sync($order)) {
			return false;
		}

		if (!self::is_yookassa_order($order)) {
			return false;
		}

		if (!self::order_allows_card_sync($order)) {
			return false;
		}

		$payment_id = (string) $order->get_transaction_id();
		if ($payment_id === '' || !class_exists('YooKassaClientFactory')) {
			return false;
		}

		try {
			$payment = YooKassaClientFactory::getYooKassaClient()->getPaymentInfo($payment_id);
		} catch (Exception $e) {
			return false;
		}

		$card_data = self::build_card_from_payment($payment, $order);
		if ($card_data === null) {
			if ($order->get_meta('_ytr_auto_renew_opt_in') === 'yes') {
				$method = $payment->getPaymentMethod();
				$saved  = $method && method_exists($method, 'getSaved') && $method->getSaved();
				if (!$saved) {
					$order->add_order_note(
						__(
							'Карта не сохранена в ЮKassa (save_payment_method). Проверьте телефон на checkout и что в магазине подключены автоплатежи.',
							'yoga-tariff-renewal'
						)
					);
				}
			}
			return false;
		}

		if (self::should_skip_order_sync($user_id, $order, $card_data)) {
			return false;
		}

		self::upsert_card($user_id, $card_data);

		if ($order->get_meta('_ytr_auto_renew_opt_in') === '' && !empty($card_data['recurring'])) {
			$order->update_meta_data('_ytr_auto_renew_opt_in', 'yes');
			$order->save();
		}

		self::maybe_enable_auto_renew_from_order($order, $user_id, $card_data);

		return true;
	}

	/**
	 * Карта сохранена в ЮKassa — включаем автопродление, если пользователь не отказался явно.
	 *
	 * @param array<string, mixed> $card_data
	 */
	private static function maybe_enable_auto_renew_from_order(WC_Order $order, int $user_id, array $card_data): void {
		if (
			$user_id <= 0
			|| !class_exists('YTR_User')
			|| empty($card_data['recurring'])
			|| (string) ($card_data['payment_method_id'] ?? '') === ''
		) {
			return;
		}

		if ($order->get_meta('_ytr_auto_renew_opt_in') === 'no') {
			return;
		}

		if (class_exists('YTR_LK') && YTR_LK::was_auto_renew_cancelled($user_id)) {
			$cancelled_at = (int) get_user_meta($user_id, YTR_LK::META_CANCELLED_AT, true);
			$created_at   = $order->get_date_created();

			// После ручной отмены не включаем автопродление от старых заказов.
			if ($cancelled_at <= 0 || !$created_at || $created_at->getTimestamp() <= $cancelled_at) {
				return;
			}
		}

		$product_id = YTR_Tariff::get_tariff_product_id_from_order($order);
		if ($product_id <= 0) {
			$tariff = YTR_Tariff::get_active_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
			}
		}

		if ($product_id <= 0) {
			return;
		}

		YTR_User::enable_auto_renew(
			$user_id,
			$product_id,
			(string) $card_data['payment_method_id']
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_cards(int $user_id): array {
		if ($user_id <= 0) {
			return array();
		}

		$cards = get_user_meta($user_id, self::META_KEY, true);
		if (!is_array($cards)) {
			return array();
		}

		$cards = array_values(array_filter($cards, 'is_array'));

		return $cards;
	}

	public static function user_has_card_from_order(int $user_id, int $order_id): bool {
		if ($user_id <= 0 || $order_id <= 0) {
			return false;
		}

		foreach (self::get_cards($user_id) as $card) {
			if (!is_array($card)) {
				continue;
			}

			if ((int) ($card['order_id'] ?? 0) === $order_id) {
				return true;
			}
		}

		return false;
	}

	public static function resolve_payment_method_id_for_order(int $user_id, int $order_id): string {
		if ($user_id <= 0 || $order_id <= 0) {
			return '';
		}

		foreach (self::get_cards($user_id) as $card) {
			if (!is_array($card) || (int) ($card['order_id'] ?? 0) !== $order_id) {
				continue;
			}

			$payment_method_id = (string) ($card['payment_method_id'] ?? '');
			if ($payment_method_id !== '') {
				return $payment_method_id;
			}

			$card_id = (string) ($card['id'] ?? '');
			if ($card_id !== '' && !str_starts_with($card_id, 'pay_')) {
				return $card_id;
			}
		}

		return '';
	}

	/**
	 * Формат для шаблона ЛК (get_user_saved_cards).
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_cards_for_lk(int $user_id): array {
		if ($user_id <= 0) {
			return array();
		}

		self::prune_cards_from_declined_orders($user_id);

		if (self::get_cards($user_id) === array() && !self::is_historical_sync_paused($user_id)) {
			self::sync_cards_for_user($user_id, true);
		}

		if (class_exists('YTR_LK')) {
			YTR_LK::maybe_backfill_auto_renew($user_id);
		}

		$auto_method = class_exists('YTR_User') ? YTR_User::get_payment_method_id($user_id) : '';
		$result      = array();

		foreach (self::get_cards($user_id) as $card) {
			$card_id = (string) ($card['id'] ?? '');
			if ($card_id === '') {
				continue;
			}

			$card_pm = (string) ($card['payment_method_id'] ?? '');
			$is_auto = $auto_method !== ''
				&& ($card_pm === $auto_method || $card_id === $auto_method);

			$result[] = array(
				'id'        => $card_id,
				'brand'     => self::format_brand_for_display((string) ($card['brand'] ?? __('Карта', 'yoga-tariff-renewal'))),
				'last4'     => (string) ($card['last4'] ?? '****'),
				'type'      => (string) ($card['type'] ?? 'default'),
				'exp_month' => (string) ($card['exp_month'] ?? ''),
				'exp_year'  => (string) ($card['exp_year'] ?? ''),
				'recurring' => !empty($card['recurring']) ? '1' : '0',
				'is_auto'   => $is_auto ? '1' : '0',
			);
		}

		return $result;
	}

	public static function remove_card(int $user_id, string $card_id): bool {
		$card_id = sanitize_text_field($card_id);
		if ($user_id <= 0 || $card_id === '') {
			return false;
		}

		$cards   = self::get_cards($user_id);
		$removed = null;
		$next    = array();

		foreach ($cards as $card) {
			if (!is_array($card) || !self::card_matches_identifier($card, $card_id)) {
				$next[] = $card;
				continue;
			}
			$removed = $card;
		}

		if ($removed === null) {
			return false;
		}

		update_user_meta($user_id, self::META_KEY, $next);

		self::pause_historical_sync($user_id);
		self::append_removed_card($user_id, $removed);

		$auto_payment_method_id = class_exists('YTR_User')
			? YTR_User::get_payment_method_id($user_id)
			: '';
		$removed_payment_method_id = (string) ($removed['payment_method_id'] ?? '');
		$removed_id = (string) ($removed['id'] ?? '');

		if (
			class_exists('YTR_User')
			&& $auto_payment_method_id !== ''
			&& ($auto_payment_method_id === $removed_payment_method_id || $auto_payment_method_id === $removed_id)
		) {
			YTR_User::disable_auto_renew($user_id, true);
		}

		return true;
	}

	/**
	 * Разрешает синхронизацию карт после новой привязки или оплаты с сохранением карты.
	 */
	public static function clear_sync_pause(int $user_id): void {
		if ($user_id <= 0) {
			return;
		}

		delete_user_meta($user_id, self::SYNC_PAUSED_AT_META);
		delete_user_meta($user_id, self::REMOVAL_SNAPSHOT_META);
		delete_user_meta($user_id, self::REMOVED_CARDS_META);
	}

	/**
	 * @param array<string, mixed> $card_data
	 */
	public static function upsert_card_for_user(int $user_id, array $card_data): void {
		self::upsert_card($user_id, $card_data);
	}

	/**
	 * @param array<string, mixed> $card_data
	 */
	private static function upsert_card(int $user_id, array $card_data): void {
		$order_id = (int) ($card_data['order_id'] ?? 0);
		if ($order_id > 0) {
			$order = wc_get_order($order_id);
			if ($order instanceof WC_Order && self::should_skip_order_sync($user_id, $order, $card_data)) {
				return;
			}
		}

		$cards = self::get_cards($user_id);
		$incoming_payment_method_id = (string) ($card_data['payment_method_id'] ?? '');
		$updated = false;

		foreach ($cards as $index => $existing) {
			if (!is_array($existing)) {
				continue;
			}

			$existing_payment_method_id = (string) ($existing['payment_method_id'] ?? '');
			$is_same_payment_method = $incoming_payment_method_id !== ''
				&& $incoming_payment_method_id === $existing_payment_method_id;

			if (!$is_same_payment_method && !self::cards_match_identity($existing, $card_data)) {
				continue;
			}

			$cards[$index] = self::merge_card_records($existing, $card_data);
			$updated = true;
			break;
		}

		if (!$updated) {
			$cards[] = $card_data;
		}

		update_user_meta($user_id, self::META_KEY, array_values($cards));

		if (self::order_can_resume_card_sync(wc_get_order((int) ($card_data['order_id'] ?? 0)))) {
			self::clear_sync_pause($user_id);
		}
	}

	/**
	 * @param array<string, mixed> $existing
	 * @param array<string, mixed> $incoming
	 * @return array<string, mixed>
	 */
	private static function merge_card_records(array $existing, array $incoming): array {
		$merged = array_merge($existing, $incoming);

		$existing_pm = (string) ($existing['payment_method_id'] ?? '');
		$incoming_pm = (string) ($incoming['payment_method_id'] ?? '');
		if ($incoming_pm !== '') {
			$merged['payment_method_id'] = $incoming_pm;
			$merged['id']                = $incoming_pm;
		} elseif ($existing_pm !== '') {
			$merged['payment_method_id'] = $existing_pm;
			$merged['id']                = $existing_pm;
		}

		if (!empty($incoming['recurring'])) {
			$merged['recurring'] = true;
		}

		return $merged;
	}

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	private static function cards_match_identity(array $a, array $b): bool {
		$last4_a = (string) ($a['last4'] ?? '');
		$last4_b = (string) ($b['last4'] ?? '');
		if ($last4_a === '' || $last4_b === '' || $last4_a !== $last4_b) {
			return false;
		}

		$type_a = strtolower((string) ($a['type'] ?? ''));
		$type_b = strtolower((string) ($b['type'] ?? ''));
		if ($type_a !== '' && $type_b !== '' && $type_a === $type_b) {
			return true;
		}

		return strtolower((string) ($a['brand'] ?? '')) === strtolower((string) ($b['brand'] ?? ''));
	}

	/**
	 * @param array<string, mixed> $card
	 */
	private static function card_matches_identifier(array $card, string $card_id): bool {
		if ($card_id === '') {
			return false;
		}

		if ((string) ($card['id'] ?? '') === $card_id) {
			return true;
		}

		$payment_method_id = (string) ($card['payment_method_id'] ?? '');

		return $payment_method_id !== '' && $payment_method_id === $card_id;
	}

	private static function pause_historical_sync(int $user_id): void {
		update_user_meta($user_id, self::SYNC_PAUSED_AT_META, time());
	}

	public static function is_historical_sync_paused(int $user_id): bool {
		return $user_id > 0 && (int) get_user_meta($user_id, self::SYNC_PAUSED_AT_META, true) > 0;
	}

	private static function order_can_resume_card_sync(?WC_Order $order): bool {
		if (!$order instanceof WC_Order) {
			return false;
		}

		if (class_exists('YTR_Card_Binding') && YTR_Card_Binding::is_binding_order($order)) {
			return true;
		}

		if ($order->get_meta('_ytr_auto_renew_opt_in') !== 'yes') {
			return false;
		}

		$user_id = (int) $order->get_customer_id();
		$paused_at = (int) get_user_meta($user_id, self::SYNC_PAUSED_AT_META, true);
		if ($paused_at <= 0) {
			return true;
		}

		$created = $order->get_date_created();

		return $created && $created->getTimestamp() > $paused_at;
	}

	/**
	 * @param array<string, mixed> $card
	 */
	private static function append_removed_card(int $user_id, array $card): void {
		$entry = array(
			'order_id'          => (int) ($card['order_id'] ?? 0),
			'payment_method_id' => (string) ($card['payment_method_id'] ?? ''),
			'id'                => (string) ($card['id'] ?? ''),
			'last4'             => (string) ($card['last4'] ?? ''),
			'type'              => (string) ($card['type'] ?? ''),
			'brand'             => (string) ($card['brand'] ?? ''),
			'removed_at'        => time(),
		);

		update_user_meta($user_id, self::REMOVAL_SNAPSHOT_META, $entry);

		$blocklist = get_user_meta($user_id, self::REMOVED_CARDS_META, true);
		if (!is_array($blocklist)) {
			$blocklist = array();
		}

		foreach ($blocklist as $blocked) {
			if (!is_array($blocked)) {
				continue;
			}
			if (self::cards_match_identity($blocked, $entry)) {
				return;
			}
			if (
				$entry['payment_method_id'] !== ''
				&& (string) ($blocked['payment_method_id'] ?? '') === $entry['payment_method_id']
			) {
				return;
			}
			if ($entry['id'] !== '' && (string) ($blocked['id'] ?? '') === $entry['id']) {
				return;
			}
		}

		$blocklist[] = $entry;
		update_user_meta($user_id, self::REMOVED_CARDS_META, $blocklist);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_removed_cards_blocklist(int $user_id): array {
		$blocklist = get_user_meta($user_id, self::REMOVED_CARDS_META, true);
		if (is_array($blocklist) && $blocklist !== array()) {
			return array_values(array_filter($blocklist, 'is_array'));
		}

		$snapshot = self::get_user_removal_snapshot($user_id);

		return $snapshot !== null ? array($snapshot) : array();
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function get_user_removal_snapshot(int $user_id): ?array {
		if ($user_id <= 0) {
			return null;
		}

		$snapshot = get_user_meta($user_id, self::REMOVAL_SNAPSHOT_META, true);

		return is_array($snapshot) ? $snapshot : null;
	}

	/**
	 * @param array<string, mixed> $card_data
	 */
	private static function should_skip_order_sync(int $user_id, WC_Order $order, array $card_data): bool {
		if (self::is_historical_sync_paused($user_id) && !self::order_can_resume_card_sync($order)) {
			return true;
		}

		if (
			$order->get_meta('_ytr_auto_renew_opt_in') === 'yes'
			&& $order->get_meta('_ytr_renewal') !== 'yes'
			&& self::order_can_resume_card_sync($order)
		) {
			return false;
		}

		foreach (self::get_removed_cards_blocklist($user_id) as $blocked) {
			if (self::card_entry_matches_order_sync($blocked, $order, $card_data)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $blocked
	 * @param array<string, mixed> $card_data
	 */
	private static function card_entry_matches_order_sync(array $blocked, WC_Order $order, array $card_data): bool {
		$order_id = (int) $order->get_id();
		if ((int) ($blocked['order_id'] ?? 0) > 0 && (int) ($blocked['order_id'] ?? 0) === $order_id) {
			return true;
		}

		if (self::cards_match_identity($blocked, $card_data)) {
			return true;
		}

		$blocked_pm = (string) ($blocked['payment_method_id'] ?? '');
		$card_pm    = (string) ($card_data['payment_method_id'] ?? '');
		if ($blocked_pm !== '' && $card_pm !== '' && $blocked_pm === $card_pm) {
			return true;
		}

		$blocked_id = (string) ($blocked['id'] ?? '');
		$card_id    = (string) ($card_data['id'] ?? '');
		if ($blocked_id !== '' && $card_id !== '' && $blocked_id === $card_id) {
			return true;
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $incoming
	 * @param array<string, mixed> $existing
	 */
	private static function is_newer_card(array $incoming, array $existing): bool {
		$incoming_order = (int) ($incoming['order_id'] ?? 0);
		$existing_order = (int) ($existing['order_id'] ?? 0);
		if ($incoming_order !== $existing_order) {
			return $incoming_order > $existing_order;
		}

		return (int) ($incoming['saved_at'] ?? 0) >= (int) ($existing['saved_at'] ?? 0);
	}

	/**
	 * @param array<int, array<string, mixed>> $cards
	 * @return array<string, mixed>
	 */
	private static function pick_primary_card(array $cards): array {
		usort(
			$cards,
			static function (array $a, array $b): int {
				$order_cmp = (int) ($b['order_id'] ?? 0) <=> (int) ($a['order_id'] ?? 0);
				if ($order_cmp !== 0) {
					return $order_cmp;
				}

				return (int) ($b['saved_at'] ?? 0) <=> (int) ($a['saved_at'] ?? 0);
			}
		);

		return $cards[0];
	}

	private static function is_yookassa_order(WC_Order $order): bool {
		$method = (string) $order->get_payment_method();
		return str_contains($method, 'yookassa');
	}

	/**
	 * Убирает из ЛК карты, ошибочно подтянутые с заказов без согласия (opt-in = no).
	 */
	public static function prune_cards_from_declined_orders(int $user_id): void {
		if ($user_id <= 0) {
			return;
		}

		$cards = self::get_cards($user_id);
		if ($cards === array()) {
			return;
		}

		$next = array();

		foreach ($cards as $card) {
			if (!is_array($card)) {
				continue;
			}

			$order_id = (int) ($card['order_id'] ?? 0);
			if ($order_id <= 0) {
				$next[] = $card;
				continue;
			}

			$order = wc_get_order($order_id);
			if (!$order instanceof WC_Order) {
				$next[] = $card;
				continue;
			}

			if (class_exists('YTR_Card_Binding') && YTR_Card_Binding::is_binding_order($order)) {
				$next[] = $card;
				continue;
			}

			if ($order->get_meta('_ytr_auto_renew_opt_in') === 'yes') {
				$next[] = $card;
				continue;
			}

			// Пустая meta + карта реально saved в ЮKassa — оставляем (meta могла не записаться).
			if ($order->get_meta('_ytr_auto_renew_opt_in') === '' && !empty($card['recurring'])) {
				$next[] = $card;
			}
		}

		if (count($next) !== count($cards)) {
			update_user_meta($user_id, self::META_KEY, $next);
		}
	}

	private static function order_allows_card_sync(WC_Order $order): bool {
		if (class_exists('YTR_Card_Binding') && YTR_Card_Binding::is_binding_order($order)) {
			return true;
		}

		$opt_in = (string) $order->get_meta('_ytr_auto_renew_opt_in');
		if ($opt_in === 'no') {
			return false;
		}
		if ($opt_in === 'yes') {
			return true;
		}

		// Meta пустая, но ЮKassa сохранила карту — показываем в ЛК (build_card проверит saved=true).
		return YTR_Tariff::order_contains_tariff($order);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function build_card_from_payment($payment, WC_Order $order): ?array {
		if (!is_object($payment) || !method_exists($payment, 'getPaymentMethod')) {
			return null;
		}

		$method = $payment->getPaymentMethod();
		if (!$method || !method_exists($method, 'getType')) {
			return null;
		}

		$method_type = (string) $method->getType();
		if ($method_type === 'bank_card') {
			return self::build_bank_card_from_payment($method, $payment, $order);
		}

		if ($method_type === 'yoo_money') {
			return self::build_yoo_money_from_payment($method, $payment, $order);
		}

		return null;
	}

	/**
	 * @param object $method
	 * @param object $payment
	 * @return array<string, mixed>|null
	 */
	private static function build_bank_card_from_payment($method, $payment, WC_Order $order): ?array {
		if (!method_exists($method, 'getCard')) {
			return null;
		}

		$card = $method->getCard();
		if (!$card) {
			return null;
		}

		$last4 = method_exists($card, 'getLast4') ? (string) $card->getLast4() : '';
		if ($last4 === '') {
			return null;
		}

		$brand = method_exists($card, 'getCardType') ? (string) $card->getCardType() : 'Card';
		$exp_m = method_exists($card, 'getExpiryMonth') ? (string) $card->getExpiryMonth() : '';
		$exp_y = method_exists($card, 'getExpiryYear') ? (string) $card->getExpiryYear() : '';

		return self::finalize_saved_method_entry($method, $payment, $order, array(
			'brand'     => self::format_brand_for_display(self::normalize_brand($brand)),
			'last4'     => $last4,
			'type'      => self::icon_slug_from_brand($brand),
			'exp_month' => $exp_m,
			'exp_year'  => $exp_y,
		));
	}

	/**
	 * @param object $method
	 * @param object $payment
	 * @return array<string, mixed>|null
	 */
	private static function build_yoo_money_from_payment($method, $payment, WC_Order $order): ?array {
		$account = method_exists($method, 'getAccountNumber') ? (string) $method->getAccountNumber() : '';
		if ($account === '') {
			return null;
		}

		$digits = preg_replace('/\D+/', '', $account);
		$last4  = strlen($digits) >= 4 ? substr($digits, -4) : $digits;
		if ($last4 === '') {
			return null;
		}

		return self::finalize_saved_method_entry($method, $payment, $order, array(
			'brand'     => 'YooMoney',
			'last4'     => $last4,
			'type'      => 'yoo_money',
			'exp_month' => '',
			'exp_year'  => '',
		));
	}

	/**
	 * @param object $method
	 * @param object $payment
	 * @param array<string, string> $display
	 * @return array<string, mixed>|null
	 */
	private static function finalize_saved_method_entry($method, $payment, WC_Order $order, array $display): ?array {
		$saved      = method_exists($method, 'getSaved') && $method->getSaved();
		$is_binding = class_exists('YTR_Card_Binding') && YTR_Card_Binding::is_binding_order($order);

		if (!$saved && !$is_binding) {
			return null;
		}

		$method_id  = ($saved && method_exists($method, 'getId')) ? (string) $method->getId() : '';
		$payment_id = method_exists($payment, 'getId') ? (string) $payment->getId() : '';

		return array(
			'id'                => $method_id !== '' ? $method_id : ('pay_' . $payment_id),
			'payment_method_id' => $method_id,
			'brand'             => (string) ($display['brand'] ?? __('Способ оплаты', 'yoga-tariff-renewal')),
			'last4'             => (string) ($display['last4'] ?? ''),
			'type'              => (string) ($display['type'] ?? 'default'),
			'exp_month'         => (string) ($display['exp_month'] ?? ''),
			'exp_year'          => (string) ($display['exp_year'] ?? ''),
			'recurring'         => $saved,
			'order_id'          => $order->get_id(),
			'saved_at'          => time(),
		);
	}

	private static function normalize_brand(string $brand): string {
		$map = array(
			'mastercard' => 'Mastercard',
			'visa'       => 'Visa',
			'mir'        => 'Мир',
			'maestro'    => 'Maestro',
		);
		$key = strtolower($brand);

		return $map[$key] ?? $brand;
	}

	public static function format_brand_for_display(string $brand): string {
		$key = strtolower(trim($brand));

		if ($key === 'mir') {
			return 'Мир';
		}

		return $brand;
	}

	private static function icon_slug_from_brand(string $brand): string {
		$key = strtolower($brand);
		if (str_contains($key, 'master')) {
			return 'mastercard';
		}
		if (str_contains($key, 'visa')) {
			return 'visa';
		}
		if (str_contains($key, 'mir')) {
			return 'mir';
		}
		if (str_contains($key, 'maestro')) {
			return 'maestro';
		}
		if (str_contains($key, 'yoo') || str_contains($key, 'money')) {
			return 'yoo_money';
		}

		return 'default';
	}
}
