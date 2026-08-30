<?php
/**
 * AJAX-обработчики: email verification.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

const YOGA_EMAIL_CODE_RESEND_DELAY = 60;
const YOGA_EMAIL_CODE_MAX_ATTEMPTS = 5;
const YOGA_EMAIL_LINK_TTL = DAY_IN_SECONDS;

add_action('wp_ajax_yoga_send_email_verification_code', 'yoga_send_email_verification_code_ajax');
add_action('wp_ajax_yoga_verify_email_code', 'yoga_verify_email_code_ajax');
add_action('template_redirect', 'yoga_handle_email_verification_link');
add_action('admin_menu', 'yoga_email_verification_add_settings_page');
add_action('admin_init', 'yoga_email_verification_register_settings');

function yoga_email_verification_default_subject() {
	return 'Код подтверждения эл. почты — {site_name}';
}

function yoga_email_verification_default_message() {
	return "Здравствуйте, {user_name}!\n\nВаш код подтверждения: {code}\n\nКод действует {ttl_minutes} минут. Если вы не запрашивали подтверждение, просто проигнорируйте письмо.\n\n— {site_name}";
}

function yoga_email_verification_add_settings_page() {
	add_options_page(
		'Подтверждение эл. почты',
		'Подтверждение эл. почты',
		'manage_options',
		'yoga-email-verification',
		'yoga_email_verification_render_settings_page'
	);
}

function yoga_email_verification_register_settings() {
	register_setting('yoga_email_verification', 'yoga_email_verification_subject', array(
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => yoga_email_verification_default_subject(),
	));
	register_setting('yoga_email_verification', 'yoga_email_verification_message', array(
		'type' => 'string',
		'sanitize_callback' => 'yoga_email_verification_sanitize_message',
		'default' => yoga_email_verification_default_message(),
	));
	register_setting('yoga_email_verification', 'yoga_email_verification_ttl_minutes', array(
		'type' => 'integer',
		'sanitize_callback' => 'yoga_email_verification_sanitize_ttl',
		'default' => 60,
	));
}

function yoga_email_verification_sanitize_message($value) {
	return sanitize_textarea_field((string) $value);
}

function yoga_email_verification_sanitize_ttl($value) {
	return max(1, min(60, absint($value)));
}

function yoga_email_verification_get_ttl() {
	$minutes = yoga_email_verification_sanitize_ttl(get_option('yoga_email_verification_ttl_minutes', 60));
	return $minutes * MINUTE_IN_SECONDS;
}

function yoga_email_verification_render_settings_page() {
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Подтверждение эл. почты</h1>
		<p>Доступные переменные: <code>{code}</code>, <code>{site_name}</code>, <code>{user_name}</code>, <code>{email}</code>, <code>{ttl_minutes}</code>.</p>
		<form method="post" action="options.php">
			<?php settings_fields('yoga_email_verification'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="yoga-email-subject">Тема письма</label></th>
					<td><input id="yoga-email-subject" name="yoga_email_verification_subject" type="text" class="regular-text" value="<?php echo esc_attr(get_option('yoga_email_verification_subject', yoga_email_verification_default_subject())); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yoga-email-message">Текст письма</label></th>
					<td><textarea id="yoga-email-message" name="yoga_email_verification_message" rows="12" class="large-text code"><?php echo esc_textarea(get_option('yoga_email_verification_message', yoga_email_verification_default_message())); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="yoga-email-ttl">Срок действия кода</label></th>
					<td><input id="yoga-email-ttl" name="yoga_email_verification_ttl_minutes" type="number" min="1" max="60" value="<?php echo esc_attr((string) get_option('yoga_email_verification_ttl_minutes', 60)); ?>"> минут</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

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
	$action_url = function_exists('yoga_get_lk_page_url') ? (string) yoga_get_lk_page_url() : '';
	if ($action_url === '') {
		$action_url = home_url('/lk/');
	}

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
			"Сат Нам, {$user_name}!\n\nМы очень рады, что вы с нами. Всё готово к практике — вот с чего удобнее начать.\n\n1. Поставьте аватар в профиле, чтобы быть ярче в комментариях.\n2. Выберите часовой пояс в настройках.\n3. Настройте нужные уведомления и напоминания.\n4. Загляните в библиотеку практик и добавьте первую крийю в избранное.\n\nА главное — выберите садхану: практику, которую вы выполняете каждый день подряд.\n\nПерейти в ЛК: {$action_url}",
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
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return new WP_Error('invalid_email', 'Укажите корректную эл. почту.');
	}
	if (yoga_is_user_email_verified($user->ID)) {
		return new WP_Error('already_verified', 'Эл. почта уже подтверждена.');
	}

	$token = wp_generate_password(64, false, false);
	update_user_meta($user->ID, 'yoga_email_link_hash', yoga_email_verification_link_hash($token));
	update_user_meta($user->ID, 'yoga_email_link_expires', time() + YOGA_EMAIL_LINK_TTL);
	update_user_meta($user->ID, 'yoga_email_link_address', sanitize_email($user->user_email));
	update_user_meta($user->ID, 'yoga_email_code_sent_at', time());

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

	$sent = function_exists('yoga_mail_send')
		? yoga_mail_send('email-verification-registration', array(
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
			"Сат Нам, {$user_name}!\n\nСпасибо за регистрацию. Остался один шаг — подтвердите вашу эл. почту, чтобы активировать аккаунт.\n\nПодтвердить эл. почту: {$action_url}\n\nСсылка активна 24 ч. Если вы не регистрировались — просто проигнорируйте это письмо.",
			array('Content-Type: text/plain; charset=UTF-8')
		);
	if (!$sent) {
		yoga_clear_email_verification_link($user->ID);
		delete_user_meta($user->ID, 'yoga_email_code_sent_at');
		return new WP_Error('mail_failed', 'Не удалось отправить письмо. Попробуйте ещё раз позже.');
	}

	return array('retry_after' => YOGA_EMAIL_CODE_RESEND_DELAY, 'email' => $user->user_email);
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
		wp_safe_redirect(add_query_arg('email_verification', 'invalid', home_url('/lk/')));
		exit;
	}

	update_user_meta($user_id, 'yoga_verified_email', $current_email);
	update_user_meta($user_id, 'yoga_email_verified_at', current_time('mysql', true));
	yoga_clear_email_verification_link($user_id);
	yoga_clear_email_verification_code($user_id);
	delete_user_meta($user_id, 'yoga_email_code_sent_at');
	yoga_send_email_verification_success($user_id);
	wp_safe_redirect(add_query_arg('email_verified', '1', home_url('/lk/')));
	exit;
}

function yoga_send_email_verification_code($user_id, $force = false) {
	$user = get_user_by('id', (int) $user_id);
	if (!$user || !is_email($user->user_email)) {
		return new WP_Error('invalid_email', 'Укажите корректную эл. почту.');
	}
	if (yoga_is_user_email_verified($user->ID)) {
		return new WP_Error('already_verified', 'эл. почта уже подтверждена.');
	}

	$last_sent = (int) get_user_meta($user->ID, 'yoga_email_code_sent_at', true);
	$remaining = YOGA_EMAIL_CODE_RESEND_DELAY - (time() - $last_sent);
	if (!$force && $last_sent > 0 && $remaining > 0) {
		return new WP_Error('rate_limited', sprintf('Повторная отправка будет доступна через %d сек.', $remaining), array('retry_after' => $remaining));
	}

	$code = (string) random_int(1000, 9999);
	$code_digits = str_split($code);
	update_user_meta($user->ID, 'yoga_email_code_hash', wp_hash_password($code));
	$ttl = yoga_email_verification_get_ttl();
	$ttl_minutes = (int) round($ttl / MINUTE_IN_SECONDS);
	update_user_meta($user->ID, 'yoga_email_code_expires', time() + $ttl);
	update_user_meta($user->ID, 'yoga_email_code_attempts', 0);
	update_user_meta($user->ID, 'yoga_email_code_sent_at', time());

	$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
	$user_name = trim((string) $user->display_name);
	if ($user_name === '') {
		$user_name = $user->user_email;
	}
	$replacements = array(
		'{code}' => $code,
		'{site_name}' => $site_name,
		'{user_name}' => $user_name,
		'{email}' => $user->user_email,
		'{ttl_minutes}' => (string) $ttl_minutes,
	);
	$subject = strtr((string) get_option('yoga_email_verification_subject', yoga_email_verification_default_subject()), $replacements);
	$message = strtr((string) get_option('yoga_email_verification_message', yoga_email_verification_default_message()), $replacements);
	$sent = function_exists('yoga_mail_send')
		? yoga_mail_send('email-verification', array(
			'to' => $user->user_email,
			'subject' => $subject,
			'content' => nl2br(esc_html($message)),
			'data' => array(
				'user_name' => $user_name,
				'user_email' => $user->user_email,
				'code' => $code,
				'code_digit_1' => $code_digits[0],
				'code_digit_2' => $code_digits[1],
				'code_digit_3' => $code_digits[2],
				'code_digit_4' => $code_digits[3],
				'ttl_minutes' => (string) $ttl_minutes,
			),
		))
		: wp_mail($user->user_email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
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
	wp_send_json_success(array('message' => 'Код отправлен на вашу эл. почту.', 'retry_after' => $result['retry_after']));
}

function yoga_verify_email_code_ajax() {
	yoga_email_verification_require_user();
	$user_id = get_current_user_id();
	$code = isset($_POST['code']) ? preg_replace('/\D+/', '', wp_unslash($_POST['code'])) : '';
	if (strlen($code) !== 4) {
		wp_send_json_error(array('code' => 'invalid_code', 'message' => 'Введите 4 цифры из письма.'), 422);
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
	yoga_send_email_verification_success($user_id);
	wp_send_json_success(array('message' => 'эл. почта успешно подтверждена.'));
}
