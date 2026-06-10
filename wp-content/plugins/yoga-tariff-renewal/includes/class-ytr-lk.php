<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_LK {
	public const META_CANCELLED_AT = '_ytr_auto_renew_cancelled_at';

	public static function init(): void {
		add_action('wp_ajax_ytr_cancel_auto_renew', array(__CLASS__, 'ajax_cancel_auto_renew'));
	}

	public static function cancel_auto_renew(int $user_id): bool {
		if ($user_id <= 0 || !YTR_User::is_auto_renew_enabled($user_id)) {
			return false;
		}

		YTR_User::disable_auto_renew($user_id);
		update_user_meta($user_id, self::META_CANCELLED_AT, time());

		return true;
	}

	public static function is_auto_renew_active_for_user(int $user_id): bool {
		return $user_id > 0 && YTR_User::is_auto_renew_enabled($user_id);
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
		if (!self::cancel_auto_renew($user_id)) {
			wp_send_json_error(array('message' => 'Автопродление уже отключено или не было включено'), 400);
		}

		$tariff = function_exists('get_current_user_tariff') ? get_current_user_tariff($user_id) : false;
		$end    = is_array($tariff) && !empty($tariff['access_end_date'])
			? (string) $tariff['access_end_date']
			: '';

		wp_send_json_success(
			array(
				'message' => $end !== ''
					? sprintf('Автопродление отключено. Доступ сохранится до %s.', $end)
					: 'Автопродление отключено. Доступ сохранится до конца оплаченного периода.',
			)
		);
	}
}
