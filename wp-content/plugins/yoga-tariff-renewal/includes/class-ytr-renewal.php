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

		if (!self::is_enabled() || !YTR_YooKassa::is_configured()) {
			return $stats;
		}

		foreach (YTR_User::get_auto_renew_user_ids() as $user_id) {
			if (!self::user_needs_renewal($user_id)) {
				++$stats['skipped'];
				continue;
			}

			if (YTR_Orders::has_recent_renewal_attempt($user_id)) {
				++$stats['skipped'];
				continue;
			}

			$result = self::renew_user($user_id);
			++$stats['processed'];

			if ($result['success']) {
				++$stats['succeeded'];
			} else {
				++$stats['failed'];
			}
		}

		return $stats;
	}

	public static function user_needs_renewal(int $user_id): bool {
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

	/**
	 * @return array{success:bool, message:string, order_id:int}
	 */
	public static function renew_user(int $user_id): array {
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

		YTR_User::record_renewal_failure($user_id);
		$order->update_status('failed', $charge['message']);

		return array(
			'success'  => false,
			'message'  => $charge['message'],
			'order_id' => $order->get_id(),
		);
	}
}
