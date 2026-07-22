<?php
/**
 * AJAX-обработчики: auth sms.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
    exit;
}






add_action('wp_ajax_send_sms_code', 'handle_send_sms_code');
add_action('wp_ajax_nopriv_send_sms_code', 'handle_send_sms_code');
add_action('wp_ajax_verify_sms_code', 'handle_verify_sms_code');
add_action('wp_ajax_nopriv_verify_sms_code', 'handle_verify_sms_code');
add_action('wp_ajax_resend_sms_code', 'handle_resend_sms_code');
add_action('wp_ajax_nopriv_resend_sms_code', 'handle_resend_sms_code');

add_action('wp_ajax_yoga_email_login', 'handle_yoga_email_login');
add_action('wp_ajax_nopriv_yoga_email_login', 'handle_yoga_email_login');
add_action('wp_ajax_yoga_email_register', 'handle_yoga_email_register');
add_action('wp_ajax_nopriv_yoga_email_register', 'handle_yoga_email_register');
add_action('wp_ajax_yoga_lost_password', 'handle_yoga_lost_password');
add_action('wp_ajax_nopriv_yoga_lost_password', 'handle_yoga_lost_password');

if (!function_exists('yoga_get_registration_role')) {
    function yoga_get_registration_role() {
        return get_role('customer') instanceof WP_Role ? 'customer' : 'subscriber';
    }
}

if (!function_exists('yoga_sanitize_ajax_notice_html')) {



    function yoga_sanitize_ajax_notice_html($message) {
        return wp_kses_post(wp_specialchars_decode((string) $message, ENT_QUOTES));
    }
}

if (!function_exists('handle_yoga_email_login')) {
    function handle_yoga_email_login() {
        check_ajax_referer('yoga_login_nonce', 'yoga_login_nonce');
        yoga_smartcaptcha_require_valid();
        $log = sanitize_text_field($_POST['log']);
        $pwd = $_POST['pwd'];
        if (empty($log) || empty($pwd)) {
            yoga_ajax_error('Введите почту и пароль', 'validation_error', 422);
        }
        $existing = get_user_by('email', $log);
        if (!$existing && !is_email($log)) {
            $existing = get_user_by('login', $log);
        }
        if (!$existing) {
            yoga_ajax_error('Пользователь не найден', 'not_found', 404);
        }
        $user = wp_signon(array(
            'user_login'    => $log,
            'user_password' => $pwd,
            'remember'      => true,
        ), false);
        if (is_wp_error($user)) {
            if ($user->get_error_code() === 'incorrect_password') {
                $forgot_link = sprintf(
                    '<a href="#" class="ml-sl-switch yoga-login-forgot-link" data-target="3">%s</a>',
                    esc_html__('Забыли пароль?', 'yoga')
                );
                yoga_ajax_error(
                    esc_html__('Неверный пароль.', 'yoga') . ' ' . $forgot_link,
                    'auth_failed',
                    401
                );
            }
            yoga_ajax_error(wp_strip_all_tags($user->get_error_message()), 'auth_failed', 401);
        }
        yoga_ajax_success('Успешный вход');
    }
}

if (!function_exists('handle_yoga_email_register')) {
    function handle_yoga_email_register() {
        check_ajax_referer('yoga_register_nonce', 'yoga_register_nonce');

        yoga_smartcaptcha_require_valid();
        $email = sanitize_email($_POST['user_email']);
        $name = sanitize_text_field($_POST['user_name']);
        $pass = $_POST['user_pass'];

        $required_consents = array('accept_terms', 'accept_personal_data', 'accept_contraindications', 'accept_marketing');
        foreach ($required_consents as $consent) {
            if (empty($_POST[$consent])) {
                yoga_ajax_error('Необходимо принять обязательные условия регистрации', 'validation_error', 422);
            }
        }

        if (empty($email) || !is_email($email)) {
            yoga_ajax_error('Введите корректный email', 'validation_error', 422);
        }
        if (empty($pass) || strlen($pass) < 6) {
            yoga_ajax_error('Пароль должен быть не короче 6 символов', 'validation_error', 422);
        }
        if (username_exists($email) || email_exists($email)) {
            yoga_ajax_error('Пользователь с таким email уже зарегистрирован', 'already_exists', 409);
        }
        $user_id = wp_create_user($email, $pass, $email);
        if (is_wp_error($user_id)) {
            yoga_ajax_error(yoga_sanitize_ajax_notice_html($user_id->get_error_message()), 'registration_failed', 500);
        }
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
            'role' => yoga_get_registration_role(),
        ));
        if ($name !== '') {
            update_user_meta($user_id, 'first_name', $name);
        }
        update_user_meta($user_id, 'yoga_marketing_consent', !empty($_POST['accept_marketing']) ? 'yes' : 'no');
        update_user_meta($user_id, 'yoga_registration_consents_at', current_time('mysql', true));

        wp_set_auth_cookie($user_id);
        wp_set_current_user($user_id);
        $mail_result = function_exists('yoga_send_email_verification_code')
            ? yoga_send_email_verification_code($user_id, true)
            : new WP_Error('verification_unavailable', 'Подтверждение эл. почты временно недоступно.');
        yoga_ajax_success(
            is_wp_error($mail_result)
                ? 'Регистрация выполнена. Код не отправлен — запросите его в личном кабинете.'
                : 'Регистрация выполнена. Код подтверждения отправлен на эл. почту.',
            array(
                'verification_sent' => !is_wp_error($mail_result),
                'verification_nonce' => wp_create_nonce('yoga_email_verification'),
                'email' => $email,
            )
        );
    }
}

if (!function_exists('handle_yoga_lost_password')) {
    function handle_yoga_lost_password() {
        check_ajax_referer('yoga_recovery_nonce', 'yoga_recovery_nonce');

        yoga_smartcaptcha_require_valid();
        $login = sanitize_text_field($_POST['user_login']);
        if (empty($login)) {
            yoga_ajax_error('Введите email', 'validation_error', 422);
        }
        $user = get_user_by('email', $login);
        if (!$user && !is_email($login)) {
            $user = get_user_by('login', $login);
        }
        if (!$user) {
            yoga_ajax_error('Пользователь с таким email не найден', 'not_found', 404);
        }
        $result = retrieve_password($user->user_login);
        if (is_wp_error($result)) {
            yoga_ajax_error(yoga_sanitize_ajax_notice_html($result->get_error_message()), 'password_reset_failed', 500);
        }
        yoga_ajax_success('Инструкции отправлены');
    }
}

if (!function_exists('handle_send_sms_code')) {
    function handle_send_sms_code() {
        check_ajax_referer('login_modal_nonce', 'security');

        $phone = sanitize_text_field($_POST['phone']);
        if (!validate_phone($phone)) {
            yoga_ajax_error('Введите корректный номер телефона', 'validation_error', 422);
        }

        $sms_code = rand(1000, 9999);
        set_transient('sms_code_' . $phone, $sms_code, 5 * MINUTE_IN_SECONDS);

        $sms_sent = send_sms_via_yandex_cloud($phone, $sms_code);
        if ($sms_sent) {
            yoga_ajax_success('Code sent');
        } else {
            yoga_ajax_error('Ошибка отправки SMS', 'sms_send_failed', 500);
        }
    }
}

if (!function_exists('handle_resend_sms_code')) {
    function handle_resend_sms_code() {

        handle_send_sms_code();
    }
}

if (!function_exists('handle_verify_sms_code')) {
    function handle_verify_sms_code() {
        check_ajax_referer('login_modal_nonce', 'security');

        $phone = sanitize_text_field($_POST['phone']);
        $sms_code = sanitize_text_field($_POST['sms_code']);
        $terms_accepted = isset($_POST['checkbox_conf']);

        if (!$terms_accepted) {
            yoga_ajax_error('Необходимо принять условия использования', 'validation_error', 422);
        }

        $stored_code = get_transient('sms_code_' . $phone);
        if ($stored_code && $stored_code == $sms_code) {
            $user = login_or_register_user($phone);
            if ($user && !is_wp_error($user)) {
                wp_set_auth_cookie($user->ID);
                delete_transient('sms_code_' . $phone);
                yoga_ajax_success('Успешный вход');
            } else {
                yoga_ajax_error('Ошибка входа', 'auth_failed', 401);
            }
        } else {
            yoga_ajax_error('Неверный код', 'invalid_code', 422);
        }
    }
}

if (!function_exists('login_or_register_user')) {
    function login_or_register_user($phone) {
        $username = 'user_' . preg_replace('/[^0-9]/', '', $phone);
        $user = get_user_by('login', $username);

        if (!$user) {
            $user_id = wp_create_user($username, wp_generate_password(), '');
            if (!is_wp_error($user_id)) {
                wp_update_user(array(
                    'ID' => $user_id,
                    'role' => yoga_get_registration_role(),
                ));
                update_user_meta($user_id, 'phone', $phone);
                $user = get_user_by('id', $user_id);
            }
        }

        return $user;
    }
}

if (!function_exists('validate_phone')) {
    function validate_phone($phone) {
        return preg_match('/^\+7\s?\(?\d{3}\)?\s?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/', $phone);
    }
}
