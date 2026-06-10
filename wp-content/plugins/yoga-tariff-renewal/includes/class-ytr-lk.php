<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_LK {
	public const META_CANCELLED_AT = '_ytr_auto_renew_cancelled_at';

	public static function init(): void {
		add_action('wp_ajax_ytr_cancel_auto_renew', array(__CLASS__, 'ajax_cancel_auto_renew'));
		add_action('wp_ajax_ytr_bind_card_start', array(__CLASS__, 'ajax_bind_card_start'));
		add_action('wp_ajax_add_payment_method', array(__CLASS__, 'ajax_bind_card_start'), 5);
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
