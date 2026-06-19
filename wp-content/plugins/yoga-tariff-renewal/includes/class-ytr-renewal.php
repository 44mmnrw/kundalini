<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Renewal {
	public static function get_days_before(): int {
		return max(0, (int) get_option('ytr_days_before', 1));
	}

	public static function get_max_retry_days(): int {
		return max(1, (int) get_option('ytr_max_retry_days', 7));
	}

	public static function get_max_retry_attempts(): int {
		return max(1, (int) get_option('ytr_max_retry_attempts', 7));
	}

	public static function get_retry_interval_minutes(): int {
		return max(1, (int) get_option('ytr_retry_interval_minutes', DAY_IN_SECONDS / MINUTE_IN_SECONDS));
	}

	public static function is_enabled(): bool {
		return get_option('ytr_enabled', 'yes') === 'yes';
	}

	/**
	 * @return array{processed:int, succeeded:int, failed:int, skipped:int}
	 */
	public static function process_due_renewals(): array {
		$stats = array(
			'processed' => 0,
			'succeeded' => 0,
			'failed'    => 0,
			'skipped'   => 0,
		);

		if (!self::is_enabled()) {
			return $stats;
		}

		$stub_mode = class_exists('YTR_Stub') && YTR_Stub::is_enabled();
		if (!$stub_mode && !YTR_YooKassa::is_configured()) {
			return $stats;
		}

		if (!self::acquire_lock('process')) {
			return $stats;
		}

		try {
		foreach (YTR_User::get_auto_renew_user_ids() as $user_id) {
			if (class_exists('YTR_Saved_Cards')) {
				YTR_Saved_Cards::sync_renewal_state($user_id);
			}

			if (!self::user_needs_renewal($user_id)) {
				++$stats['skipped'];
				continue;
			}

			if (YTR_Orders::has_recent_renewal_attempt($user_id)) {
				++$stats['skipped'];
				continue;
			}

			if (self::user_retry_limit_reached($user_id) || self::user_retry_interval_active($user_id)) {
				++$stats['skipped'];
				continue;
			}

			$result = self::renew_user($user_id);
			++$stats['processed'];

			if ($result['success']) {
				++$stats['succeeded'];
			} elseif (!empty($result['pending'])) {
				++$stats['skipped'];
			} else {
				++$stats['failed'];
			}
		}

		return $stats;
		} finally {
			self::release_lock('process');
		}
	}

	private static function acquire_lock(string $name, int $ttl = 900): bool {
		$option = 'ytr_renewal_lock_' . sanitize_key($name);
		$now    = time();

		if (add_option($option, (string) $now, '', false)) {
			return true;
		}

		$created_at = (int) get_option($option, 0);
		if ($created_at > 0 && ($now - $created_at) > $ttl) {
			delete_option($option);
			return add_option($option, (string) $now, '', false);
		}

		return false;
	}

	private static function release_lock(string $name): void {
		delete_option('ytr_renewal_lock_' . sanitize_key($name));
	}

	public static function user_needs_renewal(int $user_id): bool {
		if (class_exists('YTR_LK') && YTR_LK::was_auto_renew_cancelled($user_id)) {
			return false;
		}

		if (!YTR_User::is_auto_renew_enabled($user_id)) {
			return false;
		}

		if (YTR_User::get_payment_method_id($user_id) === '') {
			return false;
		}

		$access_end = YTR_Tariff::get_access_end_timestamp($user_id);
		$now        = current_time('timestamp');

		if ($access_end <= 0) {
			return YTR_User::get_tariff_product_id($user_id) > 0;
		}

		$days_before   = self::get_days_before();
		$max_retry     = self::get_max_retry_days();
		$renewal_start = $access_end - ($days_before * DAY_IN_SECONDS);
		$renewal_end   = $access_end + ($max_retry * DAY_IN_SECONDS);

		return $now >= $renewal_start && $now <= $renewal_end;
	}

	private static function user_retry_limit_reached(int $user_id): bool {
		return YTR_User::get_renewal_failures($user_id) >= self::get_max_retry_attempts();
	}

	private static function user_retry_interval_active(int $user_id): bool {
		if (YTR_User::get_renewal_failures($user_id) <= 0) {
			return false;
		}

		$last_attempt = YTR_User::get_last_renewal_attempt($user_id);
		if ($last_attempt <= 0) {
			return false;
		}

		$retry_after = $last_attempt + (self::get_retry_interval_minutes() * MINUTE_IN_SECONDS);

		return time() < $retry_after;
	}

	/**
	 * @return array{success:bool, message:string, order_id:int}
	 */
	public static function renew_user(int $user_id): array {
		if (class_exists('YTR_LK') && YTR_LK::was_auto_renew_cancelled($user_id)) {
			YTR_User::disable_auto_renew($user_id);
			return array(
				'success'  => false,
				'message'  => __('Автопродление отключено пользователем', 'yoga-tariff-renewal'),
				'order_id' => 0,
			);
		}

		if (!self::user_needs_renewal($user_id)) {
			return array(
				'success'  => false,
				'message'  => __('Автопродление не активно', 'yoga-tariff-renewal'),
				'order_id' => 0,
			);
		}

		$lock_name = 'user_' . $user_id;
		if (!self::acquire_lock($lock_name)) {
			return array(
				'success'  => false,
				'message'  => __('Автопродление уже выполняется', 'yoga-tariff-renewal'),
				'order_id' => 0,
			);
		}

		try {
		YTR_User::record_renewal_attempt($user_id);

		$product_id = YTR_User::get_tariff_product_id($user_id);
		if ($product_id <= 0) {
			$tariff = YTR_Tariff::get_active_tariff($user_id);
			if (is_array($tariff) && !empty($tariff['product_id'])) {
				$product_id = (int) $tariff['product_id'];
				update_user_meta($user_id, YTR_User::META_TARIFF_PRODUCT_ID, $product_id);
			}
		}

		if ($product_id <= 0) {
			YTR_User::record_renewal_failure($user_id);
			return array(
				'success'  => false,
				'message'  => 'Не найден товар тарифа',
				'order_id' => 0,
			);
		}

		$payment_method_id = YTR_User::get_payment_method_id($user_id);
		$order             = YTR_Orders::create_renewal_order($user_id, $product_id);

		if (is_wp_error($order)) {
			YTR_User::record_renewal_failure($user_id);
			return array(
				'success'  => false,
				'message'  => $order->get_error_message(),
				'order_id' => 0,
			);
		}

		$charge = YTR_YooKassa::charge_renewal($order, $payment_method_id);

		if ($charge['success']) {
			YTR_User::reset_renewal_failures($user_id);
			$order->add_order_note(
				sprintf(
					__('Автопродление: платёж %1$s (%2$s)', 'yoga-tariff-renewal'),
					$charge['payment_id'],
					$charge['status']
				)
			);

			return array(
				'success'  => true,
				'message'  => $charge['message'],
				'order_id' => $order->get_id(),
			);
		}

		if (in_array((string) $charge['status'], array('pending', 'waiting_for_capture'), true)) {
			$order->add_order_note(__('Автопродление: ожидаем финальный статус платежа ЮKassa, новая попытка списания не запускается.', 'yoga-tariff-renewal'));

			return array(
				'success'  => false,
				'message'  => $charge['message'],
				'order_id' => $order->get_id(),
				'pending'  => true,
			);
		}

		YTR_User::record_renewal_failure($user_id);
		$order->update_status('failed', $charge['message']);
		if (class_exists('YTR_Notifications')) {
			YTR_Notifications::send_renewal_failure($order, $charge['message']);
		}

		return array(
			'success'  => false,
			'message'  => $charge['message'],
			'order_id' => $order->get_id(),
		);
		} finally {
			self::release_lock($lock_name);
		}
	}
}
