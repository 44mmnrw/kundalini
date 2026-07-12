<?php

if (!defined('ABSPATH')) {
	exit;
}

const YOGA_EMAIL_CODE_TTL = 10 * MINUTE_IN_SECONDS;
const YOGA_EMAIL_CODE_RESEND_DELAY = 60;
const YOGA_EMAIL_CODE_MAX_ATTEMPTS = 5;

add_action('wp_ajax_yoga_send_email_verification_code', 'yoga_send_email_verification_code_ajax');
add_action('wp_ajax_yoga_verify_email_code', 'yoga_verify_email_code_ajax');

function yoga_is_user_email_verified($user_id) {
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return false;
	}

	$verified_email = sanitize_email((string) get_user_meta($user->ID, 'yoga_verified_email', true));
	return $verified_email !== '' && strcasecmp($verified_email, $user->user_email) === 0;
}

function yoga_clear_email_verification_code($user_id) {
	delete_user_meta($user_id, 'yoga_email_code_hash');
	delete_user_meta($user_id, 'yoga_email_code_expires');
	delete_user_meta($user_id, 'yoga_email_code_attempts');
}

function yoga_send_email_verification_code($user_id, $force = false) {
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return new WP_Error('invalid_email', 'Укажите корректный e-mail.');
	}
	if (yoga_is_user_email_verified($user->ID)) {
		return new WP_Error('already_verified', 'E-mail уже подтверждён.');
	}

	$last_sent = (int) get_user_meta($user->ID, 'yoga_email_code_sent_at', true);
	$remaining = YOGA_EMAIL_CODE_RESEND_DELAY - (time() - $last_sent);
	if (!$force && $last_sent > 0 && $remaining > 0) {
		return new WP_Error('rate_limited', sprintf('Повторная отправка будет доступна через %d сек.', $remaining), array('retry_after' => $remaining));
	}

	$code = (string) random_int(100000, 999999);
	update_user_meta($user->ID, 'yoga_email_code_hash', wp_hash_password($code));
	update_user_meta($user->ID, 'yoga_email_code_expires', time() + YOGA_EMAIL_CODE_TTL);
	update_user_meta($user->ID, 'yoga_email_code_attempts', 0);
	update_user_meta($user->ID, 'yoga_email_code_sent_at', time());

	$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
	$subject = sprintf('Код подтверждения e-mail — %s', $site_name);
	$message = sprintf(
		"Ваш код подтверждения: %s\n\nКод действует 10 минут. Если вы не запрашивали подтверждение, просто проигнорируйте письмо.\n\n— %s",
		$code,
		$site_name
	);
	$sent = wp_mail($user->user_email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
	if (!$sent) {
		yoga_clear_email_verification_code($user->ID);
		delete_user_meta($user->ID, 'yoga_email_code_sent_at');
		return new WP_Error('mail_failed', 'Не удалось отправить письмо. Попробуйте ещё раз позже.');
	}

	return array('retry_after' => YOGA_EMAIL_CODE_RESEND_DELAY, 'email' => $user->user_email);
}

function yoga_email_verification_require_user() {
	if (!is_user_logged_in()) {
		wp_send_json_error(array('code' => 'not_logged_in', 'message' => 'Необходимо войти в аккаунт.'), 401);
	}
	check_ajax_referer('yoga_email_verification', 'nonce');
}

function yoga_send_email_verification_code_ajax() {
	yoga_email_verification_require_user();
	$result = yoga_send_email_verification_code(get_current_user_id());
	if (is_wp_error($result)) {
		$data = array('code' => $result->get_error_code(), 'message' => $result->get_error_message());
		$error_data = $result->get_error_data();
		if (is_array($error_data)) {
			$data = array_merge($data, $error_data);
		}
		wp_send_json_error($data, $result->get_error_code() === 'rate_limited' ? 429 : 422);
	}
	wp_send_json_success(array('message' => 'Код отправлен на ваш e-mail.', 'retry_after' => $result['retry_after']));
}

function yoga_verify_email_code_ajax() {
	yoga_email_verification_require_user();
	$user_id = get_current_user_id();
	$code = isset($_POST['code']) ? preg_replace('/\D+/', '', wp_unslash($_POST['code'])) : '';
	if (strlen($code) !== 6) {
		wp_send_json_error(array('code' => 'invalid_code', 'message' => 'Введите 6 цифр из письма.'), 422);
	}

	$hash = (string) get_user_meta($user_id, 'yoga_email_code_hash', true);
	$expires = (int) get_user_meta($user_id, 'yoga_email_code_expires', true);
	$attempts = (int) get_user_meta($user_id, 'yoga_email_code_attempts', true);
	if ($hash === '' || $expires < time()) {
		yoga_clear_email_verification_code($user_id);
		wp_send_json_error(array('code' => 'code_expired', 'message' => 'Срок действия кода истёк. Запросите новый.'), 422);
	}
	if ($attempts >= YOGA_EMAIL_CODE_MAX_ATTEMPTS) {
		wp_send_json_error(array('code' => 'too_many_attempts', 'message' => 'Слишком много попыток. Запросите новый код.'), 429);
	}
	if (!wp_check_password($code, $hash)) {
		$attempts++;
		update_user_meta($user_id, 'yoga_email_code_attempts', $attempts);
		wp_send_json_error(array('code' => 'invalid_code', 'message' => sprintf('Неверный код. Осталось попыток: %d.', max(0, YOGA_EMAIL_CODE_MAX_ATTEMPTS - $attempts))), 422);
	}

	$user = get_user_by('id', $user_id);
	update_user_meta($user_id, 'yoga_verified_email', sanitize_email($user->user_email));
	update_user_meta($user_id, 'yoga_email_verified_at', current_time('mysql', true));
	yoga_clear_email_verification_code($user_id);
	delete_user_meta($user_id, 'yoga_email_code_sent_at');
	wp_send_json_success(array('message' => 'E-mail успешно подтверждён.'));
}
