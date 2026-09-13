<?php
/**
 * AJAX-обработчики: email verification.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

const YOGA_EMAIL_LINK_RESEND_DELAY = 60;
const YOGA_EMAIL_LINK_TTL = DAY_IN_SECONDS;

add_action('wp_ajax_yoga_send_email_verification_link', 'yoga_send_email_verification_link_ajax');
add_action('wp_ajax_yoga_send_email_verification_code', 'yoga_send_email_verification_link_ajax');
add_action('template_redirect', 'yoga_handle_email_verification_link');

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

function yoga_email_verification_link_hash($token) {
	return hash_hmac('sha256', (string) $token, wp_salt('auth'));
}

function yoga_clear_email_verification_link($user_id) {
	delete_user_meta($user_id, 'yoga_email_link_hash');
	delete_user_meta($user_id, 'yoga_email_link_expires');
	delete_user_meta($user_id, 'yoga_email_link_address');
}

function yoga_email_verification_get_lk_url() {
	$lk_url = function_exists('yoga_get_lk_page_url') ? (string) yoga_get_lk_page_url() : '';
	return $lk_url !== '' ? $lk_url : home_url('/my-account/');
}

/**
 * Send the welcome message once after the first successful email verification.
 */
function yoga_send_email_verification_success($user_id) {
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return false;
	}
	if ((string) get_user_meta($user->ID, 'yoga_email_verification_success_sent_at', true) !== '') {
		return true;
	}

	$lock_key = 'yoga_email_verification_success_sending';
	$lock_acquired = add_user_meta($user->ID, $lock_key, time(), true);
	if (!$lock_acquired) {
		$lock_time = (int) get_user_meta($user->ID, $lock_key, true);
		if ($lock_time > 0 && time() - $lock_time < 5 * MINUTE_IN_SECONDS) {
			return false;
		}
		delete_user_meta($user->ID, $lock_key);
		$lock_acquired = add_user_meta($user->ID, $lock_key, time(), true);
		if (!$lock_acquired) {
			return false;
		}
	}

	$user_name = trim((string) $user->display_name);
	if ($user_name === '') {
		$user_name = $user->user_email;
	}
	$action_url = yoga_email_verification_get_lk_url();

	$sent = function_exists('yoga_mail_send')
		? yoga_mail_send('email-verification-success', array(
			'to' => $user->user_email,
			'data' => array(
				'user_name' => $user_name,
				'user_email' => $user->user_email,
				'action_url' => $action_url,
			),
		))
		: wp_mail(
			$user->user_email,
			'Добро пожаловать в Кундалини Класс',
			"Сат Нам, {$user_name}!\n\nМы очень рады, что вы с нами. Всё готово к практике — вот с чего удобнее начать.\n\n1. Поставьте аватар в профиле, чтобы быть ярче в комментариях.\n2. Выберите часовой пояс в настройках.\n3. Настройте нужные уведомления и напоминания.\n4. Выберите нужную подписку, чтобы иметь доступ ко всем практикам на платформе.\n5. Начните практиковать!\n\nА главное — выберите садхану: практику, которую вы выполняете каждый день подряд.\n\nПерейти в ЛК: {$action_url}",
			array('Content-Type: text/plain; charset=UTF-8')
		);
	if ($sent) {
		update_user_meta($user->ID, 'yoga_email_verification_success_sent_at', current_time('mysql', true));
	}
	delete_user_meta($user->ID, $lock_key);

	return (bool) $sent;
}

/**
 * Send the registration-specific 24-hour email verification link.
 *
 * The raw bearer token is sent only by email; user meta stores its HMAC hash.
 *
 * @return array|WP_Error
 */
function yoga_send_registration_email_verification_link($user_id) {
	return yoga_send_email_verification_link($user_id, 'registration');
}

/**
 * Send a single-use link to the user's current email address.
 *
 * @return array|WP_Error
 */
function yoga_send_email_verification_link($user_id, $context = 'profile') {
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return new WP_Error('invalid_email', 'Укажите корректную эл. почту.');
	}
	if (yoga_is_user_email_verified($user->ID)) {
		return new WP_Error('already_verified', 'Эл. почта уже подтверждена.');
	}
	$last_sent = (int) get_user_meta($user->ID, 'yoga_email_link_sent_at', true);
	$remaining = YOGA_EMAIL_LINK_RESEND_DELAY - (time() - $last_sent);
	if ($context !== 'registration' && $last_sent > 0 && $remaining > 0) {
		return new WP_Error('rate_limited', sprintf('Повторная отправка будет доступна через %d сек.', $remaining), array('retry_after' => $remaining));
	}

	$token = wp_generate_password(64, false, false);
	$action_url = add_query_arg(
		array(
			'yoga_verify_email' => '1',
			'uid' => (int) $user->ID,
			'token' => $token,
		),
		home_url('/')
	);
	$user_name = trim((string) $user->display_name);
	if ($user_name === '') {
		$user_name = $user->user_email;
	}

	$is_registration = $context === 'registration';
	$sent = function_exists('yoga_mail_send')
		? yoga_mail_send($is_registration ? 'email-verification-registration' : 'email-verification-profile', array(
			'to' => $user->user_email,
			'data' => array(
				'user_name' => $user_name,
				'user_email' => $user->user_email,
				'action_url' => $action_url,
			),
		))
		: wp_mail(
			$user->user_email,
			'Подтвердите эл. почту',
			$is_registration
				? "Сат Нам, {$user_name}!\n\nСпасибо за регистрацию. Остался один шаг — подтвердите вашу эл. почту, чтобы активировать аккаунт.\n\nПодтвердить эл. почту: {$action_url}\n\nСсылка активна 24 ч. Если вы не регистрировались — просто проигнорируйте это письмо."
				: "Сат Нам, {$user_name}!\n\nПодтвердите вашу эл. почту, перейдя по ссылке:\n{$action_url}\n\nСсылка активна 24 ч. Если вы не запрашивали подтверждение, просто проигнорируйте это письмо.",
			array('Content-Type: text/plain; charset=UTF-8')
		);
	if (!$sent) {
		return new WP_Error('mail_failed', 'Не удалось отправить письмо. Попробуйте ещё раз позже.');
	}
	update_user_meta($user->ID, 'yoga_email_link_hash', yoga_email_verification_link_hash($token));
	update_user_meta($user->ID, 'yoga_email_link_expires', time() + YOGA_EMAIL_LINK_TTL);
	update_user_meta($user->ID, 'yoga_email_link_address', sanitize_email($user->user_email));
	update_user_meta($user->ID, 'yoga_email_link_sent_at', time());
	yoga_clear_email_verification_code($user->ID);
	delete_user_meta($user->ID, 'yoga_email_code_sent_at');

	return array('retry_after' => YOGA_EMAIL_LINK_RESEND_DELAY, 'email' => $user->user_email);
}

function yoga_handle_email_verification_link() {
	if (!isset($_GET['yoga_verify_email'])) {
		return;
	}

	nocache_headers();
	$user_id = isset($_GET['uid']) ? absint($_GET['uid']) : 0;
	$token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
	$user = $user_id > 0 ? get_user_by('id', $user_id) : false;
	$stored_hash = $user ? (string) get_user_meta($user_id, 'yoga_email_link_hash', true) : '';
	$expires = $user ? (int) get_user_meta($user_id, 'yoga_email_link_expires', true) : 0;
	$link_email = $user ? sanitize_email((string) get_user_meta($user_id, 'yoga_email_link_address', true)) : '';
	$current_email = $user ? sanitize_email((string) $user->user_email) : '';
	$token_valid = preg_match('/^[A-Za-z0-9]{64}$/', $token)
		&& $stored_hash !== ''
		&& hash_equals($stored_hash, yoga_email_verification_link_hash($token));

	if (!$user || !$token_valid || $expires < time() || $link_email === '' || strcasecmp($link_email, $current_email) !== 0) {
		if ($user && $expires < time()) {
			yoga_clear_email_verification_link($user_id);
		}
		wp_safe_redirect(add_query_arg('email_verification', 'invalid', yoga_email_verification_get_lk_url()));
		exit;
	}

	update_user_meta($user_id, 'yoga_verified_email', $current_email);
	update_user_meta($user_id, 'yoga_email_verified_at', current_time('mysql', true));
	yoga_clear_email_verification_link($user_id);
	yoga_clear_email_verification_code($user_id);
	delete_user_meta($user_id, 'yoga_email_link_sent_at');
	delete_user_meta($user_id, 'yoga_email_code_sent_at');
	yoga_send_email_verification_success($user_id);
	wp_safe_redirect(add_query_arg('email_verified', '1', yoga_email_verification_get_lk_url()));
	exit;
}

function yoga_email_verification_require_user() {
	if (!is_user_logged_in()) {
		wp_send_json_error(array('code' => 'not_logged_in', 'message' => 'Необходимо войти в аккаунт.'), 401);
	}
	check_ajax_referer('yoga_email_verification', 'nonce');
}

function yoga_send_email_verification_link_ajax() {
	yoga_email_verification_require_user();
	$result = yoga_send_email_verification_link(get_current_user_id());
	if (is_wp_error($result)) {
		$data = array('code' => $result->get_error_code(), 'message' => $result->get_error_message());
		$error_data = $result->get_error_data();
		if (is_array($error_data)) {
			$data = array_merge($data, $error_data);
		}
		wp_send_json_error($data, $result->get_error_code() === 'rate_limited' ? 429 : 422);
	}
	wp_send_json_success(array('message' => 'Ссылка для подтверждения отправлена на почту', 'retry_after' => $result['retry_after']));
}
