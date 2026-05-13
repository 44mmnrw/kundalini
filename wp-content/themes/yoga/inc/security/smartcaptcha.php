<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Yandex SmartCaptcha (Yandex Cloud).
 *
 * Ключи (по приоритету):
 * 1) Константы в wp-config.php (перекрывают админку):
 *    define( 'YOGA_SMARTCAPTCHA_CLIENT_KEY', '...' );
 *    define( 'YOGA_SMARTCAPTCHA_SERVER_KEY', '...' );
 * 2) Поля в админке: «Настройки темы» → блок Yandex SmartCaptcha (ACF options).
 *
 * @see https://yandex.cloud/ru/docs/smartcaptcha/
 */

if (!function_exists('yoga_smartcaptcha_normalize_key')) {
    /**
     * Убираем пробелы/переносы строк (часто после вставки ключей из почты/дока).
     *
     * @param mixed $raw
     * @return string
     */
    function yoga_smartcaptcha_normalize_key($raw) {
        if ($raw === null || $raw === '' || !is_string($raw)) {
            return '';
        }
        return preg_replace('/\s+/u', '', trim(wp_check_invalid_utf8($raw, true)));
    }
}

if (!function_exists('yoga_smartcaptcha_client_key')) {
    /**
     * @return string
     */
    function yoga_smartcaptcha_client_key() {
        if (defined('YOGA_SMARTCAPTCHA_CLIENT_KEY') && yoga_smartcaptcha_normalize_key(YOGA_SMARTCAPTCHA_CLIENT_KEY) !== '') {
            return apply_filters('yoga_smartcaptcha_client_key', yoga_smartcaptcha_normalize_key((string) YOGA_SMARTCAPTCHA_CLIENT_KEY));
        }

        $key = '';
        if (function_exists('get_field')) {
            $v = get_field('smartcaptcha_client_key', 'option');
            if (is_string($v)) {
                $key = yoga_smartcaptcha_normalize_key($v);
            }
        }
        if ($key === '') {
            foreach (array('options_smartcaptcha_client_key', 'smartcaptcha_client_key') as $opt_key) {
                $fallback = get_option($opt_key);
                $key = is_string($fallback) ? yoga_smartcaptcha_normalize_key($fallback) : '';
                if ($key !== '') {
                    break;
                }
            }
        }

        return apply_filters('yoga_smartcaptcha_client_key', $key);
    }
}

if (!function_exists('yoga_smartcaptcha_server_key')) {
    /**
     * @return string
     */
    function yoga_smartcaptcha_server_key() {
        if (defined('YOGA_SMARTCAPTCHA_SERVER_KEY') && yoga_smartcaptcha_normalize_key(YOGA_SMARTCAPTCHA_SERVER_KEY) !== '') {
            return apply_filters('yoga_smartcaptcha_server_key', yoga_smartcaptcha_normalize_key((string) YOGA_SMARTCAPTCHA_SERVER_KEY));
        }

        $key = '';
        if (function_exists('get_field')) {
            $v = get_field('smartcaptcha_server_key', 'option');
            if (is_string($v)) {
                $key = yoga_smartcaptcha_normalize_key($v);
            }
        }
        if ($key === '') {
            foreach (array('options_smartcaptcha_server_key', 'smartcaptcha_server_key') as $opt_key) {
                $fallback = get_option($opt_key);
                $key = is_string($fallback) ? yoga_smartcaptcha_normalize_key($fallback) : '';
                if ($key !== '') {
                    break;
                }
            }
        }

        return apply_filters('yoga_smartcaptcha_server_key', $key);
    }
}

if (!function_exists('yoga_smartcaptcha_is_enforced')) {
    function yoga_smartcaptcha_is_enforced() {
        return yoga_smartcaptcha_client_key() !== '' && yoga_smartcaptcha_server_key() !== '';
    }
}

if (!function_exists('yoga_smartcaptcha_validate_url')) {
    function yoga_smartcaptcha_validate_url() {
        return 'https://smartcaptcha.cloud.yandex.ru/validate';
    }
}

if (!function_exists('yoga_smartcaptcha_remote_ip')) {
    /**
     * IP для параметра запроса валидации (учёт простых прокси).
     */
    function yoga_smartcaptcha_remote_ip() {
        $keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        foreach ($keys as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }
            $raw = sanitize_text_field(wp_unslash($_SERVER[$header]));
            if (strpos($raw, ',') !== false) {
                $raw = trim(explode(',', $raw)[0]);
            }
            if (filter_var($raw, FILTER_VALIDATE_IP)) {
                return $raw;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('yoga_smartcaptcha_verify_token')) {
    /**
     * Токен из поля формы smart-token.
     *
     * @param string $token
     * @return bool
     */
    function yoga_smartcaptcha_verify_token($token) {
        $token = trim((string) $token);
        $secret = yoga_smartcaptcha_server_key();
        if ($token === '' || $secret === '') {
            return false;
        }

        $response = wp_remote_post(
            yoga_smartcaptcha_validate_url(),
            array(
                'timeout' => 3,
                'body' => array(
                    'secret' => $secret,
                    'token' => $token,
                    'ip' => yoga_smartcaptcha_remote_ip(),
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $body = json_decode($body_raw, true);

        if ($code !== 200 || !is_array($body)) {
            return false;
        }

        return isset($body['status']) && $body['status'] === 'ok';
    }
}

if (!function_exists('yoga_smartcaptcha_require_valid')) {
    /**
     * Прекращает обработчик через yoga_ajax_error, если капча обязательна и не прошла проверку.
     */
    function yoga_smartcaptcha_require_valid() {
        if (!yoga_smartcaptcha_is_enforced()) {
            return;
        }

        $token = isset($_POST['smart-token']) ? sanitize_text_field(wp_unslash($_POST['smart-token'])) : '';

        if ($token === '') {
            yoga_ajax_error('Подтвердите, что вы не робот', 'smartcaptcha_missing', 422);
        }

        if (!yoga_smartcaptcha_verify_token($token)) {
            yoga_ajax_error('Проверка капчи не пройдена. Попробуйте ещё раз.', 'smartcaptcha_failed', 422);
        }
    }
}
