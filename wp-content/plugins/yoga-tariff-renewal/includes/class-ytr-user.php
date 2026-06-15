<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_User {
	public const META_AUTO_RENEW          = '_ytr_auto_renew';
	public const META_PAYMENT_METHOD_ID   = '_ytr_payment_method_id';
	public const META_TARIFF_PRODUCT_ID   = '_ytr_tariff_product_id';
	public const META_RENEWAL_FAILURES    = '_ytr_renewal_failures';
	public const META_LAST_RENEWAL_TRY    = '_ytr_last_renewal_attempt';

	public static function is_auto_renew_enabled(int $user_id): bool {
		if (get_user_meta($user_id, self::META_AUTO_RENEW, true) !== 'yes') {
			return false;
		}

		if (self::get_payment_method_id($user_id) === '') {
			return false;
		}

		if (class_exists('YTR_LK') && YTR_LK::was_auto_renew_cancelled($user_id)) {
			return false;
		}

		return true;
	}

	public static function has_auto_renew_meta(int $user_id): bool {
		return get_user_meta($user_id, self::META_AUTO_RENEW, true) === 'yes'
			|| self::get_payment_method_id($user_id) !== '';
	}

	public static function enable_auto_renew(int $user_id, int $product_id, string $payment_method_id): void {
		update_user_meta($user_id, self::META_AUTO_RENEW, 'yes');
		update_user_meta($user_id, self::META_TARIFF_PRODUCT_ID, $product_id);
		update_user_meta($user_id, self::META_PAYMENT_METHOD_ID, sanitize_text_field($payment_method_id));
		update_user_meta($user_id, self::META_RENEWAL_FAILURES, 0);

		if (class_exists('YTR_LK')) {
			delete_user_meta($user_id, YTR_LK::META_CANCELLED_AT);
		}
	}

	public static function disable_auto_renew(int $user_id, bool $mark_cancelled = false): void {
		update_user_meta($user_id, self::META_AUTO_RENEW, 'no');
		delete_user_meta($user_id, self::META_PAYMENT_METHOD_ID);

		if ($mark_cancelled && class_exists('YTR_LK')) {
			update_user_meta($user_id, YTR_LK::META_CANCELLED_AT, time());
		}
	}

	public static function get_payment_method_id(int $user_id): string {
		return (string) get_user_meta($user_id, self::META_PAYMENT_METHOD_ID, true);
	}

	public static function get_tariff_product_id(int $user_id): int {
		return (int) get_user_meta($user_id, self::META_TARIFF_PRODUCT_ID, true);
	}

	public static function record_renewal_attempt(int $user_id): void {
		update_user_meta($user_id, self::META_LAST_RENEWAL_TRY, time());
	}

	public static function record_renewal_failure(int $user_id): void {
		$failures = (int) get_user_meta($user_id, self::META_RENEWAL_FAILURES, true);
		update_user_meta($user_id, self::META_RENEWAL_FAILURES, $failures + 1);
	}

	public static function reset_renewal_failures(int $user_id): void {
		update_user_meta($user_id, self::META_RENEWAL_FAILURES, 0);
	}

	public static function get_renewal_failures(int $user_id): int {
		return (int) get_user_meta($user_id, self::META_RENEWAL_FAILURES, true);
	}

	/**
	 * @return int[]
	 */
	public static function get_auto_renew_user_ids(): array {
		global $wpdb;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				self::META_AUTO_RENEW,
				'yes'
			)
		);

		return array_values(array_filter(array_map('intval', $rows)));
	}
}
