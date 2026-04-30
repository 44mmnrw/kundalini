<?php

if (!defined('ABSPATH')) {
    exit;
}

/* Axecode.tech: Этап 3 (универсальность).
 * Модуль авторизации и SMS вынесен из functions.php.
 * Сохраняем исходные сигнатуры функций для обратной совместимости.
 */

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

if (!function_exists('handle_yoga_email_login')) {
    function handle_yoga_email_login() {
        check_ajax_referer('yoga_login_nonce', 'yoga_login_nonce');
        $log = sanitize_text_field($_POST['log']);
        $pwd = $_POST['pwd'];
        if (empty($log) || empty($pwd)) {
            yoga_ajax_error('Введите почту и пароль', 'validation_error', 422);
        }
        $user = wp_signon(array(
            'user_login'    => $log,
            'user_password' => $pwd,
            'remember'      => true,
        ), false);
        if (is_wp_error($user)) {
            yoga_ajax_error($user->get_error_message(), 'auth_failed', 401);
        }
        yoga_ajax_success('Успешный вход');
    }
}

if (!function_exists('handle_yoga_email_register')) {
    function handle_yoga_email_register() {
        check_ajax_referer('yoga_register_nonce', 'yoga_register_nonce');

        $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
        if (!verify_recaptcha($recaptcha_response)) {
            yoga_ajax_error('Пожалуйста, подтвердите, что вы не робот', 'captcha_failed', 422);
        }

        $email = sanitize_email($_POST['user_email']);
        $name = sanitize_text_field($_POST['user_name']);
        $pass = $_POST['user_pass'];
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
            yoga_ajax_error($user_id->get_error_message(), 'registration_failed', 500);
        }
        wp_update_user(array('ID' => $user_id, 'display_name' => $name));

        $site_name = get_bloginfo('name');
        $login_url = wp_login_url(home_url('/'));
        $subject = sprintf('Регистрация на %s', $site_name);
        $message = sprintf(
            "Здравствуйте, %s!\n\nВы успешно зарегистрировались на сайте %s.\n\nДля входа используйте ваш email и пароль, который вы указали при регистрации.\n\nСтраница входа: %s\n\n— %s",
            $name,
            $site_name,
            $login_url,
            $site_name
        );
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);

        wp_set_auth_cookie($user_id);
        yoga_ajax_success('Регистрация выполнена');
    }
}

if (!function_exists('handle_yoga_lost_password')) {
    function handle_yoga_lost_password() {
        check_ajax_referer('yoga_recovery_nonce', 'yoga_recovery_nonce');

        $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
        if (!verify_recaptcha($recaptcha_response)) {
            yoga_ajax_error('Пожалуйста, подтвердите, что вы не робот', 'captcha_failed', 422);
        }

        $login = sanitize_text_field($_POST['user_login']);
        if (empty($login)) {
            yoga_ajax_error('Введите email', 'validation_error', 422);
        }
        $user = get_user_by('email', $login);
        if (!$user) {
            $user = get_user_by('login', $login);
        }
        if (!$user) {
            yoga_ajax_error('Пользователь с таким email не найден', 'not_found', 404);
        }
        $result = retrieve_password($user->user_login);
        if (is_wp_error($result)) {
            yoga_ajax_error($result->get_error_message(), 'password_reset_failed', 500);
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
        /* Axecode.tech: Этап 3 (универсальность) — единая точка повторной отправки SMS-кода. */
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
