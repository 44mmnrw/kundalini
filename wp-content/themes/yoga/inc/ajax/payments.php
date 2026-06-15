<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('handle_add_payment_method')) {
    /**
     * Привязка карты — YTR_Card_Binding (плагин yoga-tariff-renewal, ajax ytr_bind_card_start).
     */
    function handle_add_payment_method() {
        if (class_exists('YTR_LK')) {
            return;
        }

        if (!yoga_require_woocommerce_for_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        yoga_ajax_error(
            'Привязка карты недоступна. Установите и активируйте плагин автопродления тарифов.',
            'card_binding_unavailable',
            400
        );
    }
}

if (!function_exists('handle_remove_payment_method')) {
    function handle_remove_payment_method() {
        if (!yoga_require_woocommerce_for_ajax()) {
            return;
        }

        if (
            !isset($_POST['security'])
            || (
                !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'remove_payment_method')
                && !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['security'])), 'yoga_ajax_nonce')
            )
        ) {
            yoga_ajax_error('Ошибка безопасности', 'invalid_nonce', 403);
        }

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        $user_id = get_current_user_id();
        $card_id = sanitize_text_field(wp_unslash((string) $_POST['card_id']));
        $had_auto_renew = class_exists('YTR_User') && YTR_User::is_auto_renew_enabled($user_id);

        if (class_exists('YTR_Saved_Cards') && YTR_Saved_Cards::remove_card($user_id, $card_id)) {
            $message = 'Карта удалена';
            if ($had_auto_renew && class_exists('YTR_User') && !YTR_User::is_auto_renew_enabled($user_id)) {
                $message = 'Карта удалена. Автопродление отключено. Доступ сохранится до конца оплаченного периода.';
            }
            yoga_ajax_success($message);
        }

        yoga_ajax_error('Карта не найдена', 'card_not_found', 404);
    }
}

add_action('wp_ajax_add_payment_method', 'handle_add_payment_method');
add_action('wp_ajax_remove_payment_method', 'handle_remove_payment_method');
