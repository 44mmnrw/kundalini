<?php

if (!defined('ABSPATH')) {
    exit;
}

/* Axecode.tech: Этап 3 (универсальность).
 * Модуль подготовлен для выноса AJAX-логики платежных карт из functions.php.
 * Function guards сохраняют обратную совместимость на переходном этапе.
 */

if (!function_exists('handle_add_payment_method')) {
    function handle_add_payment_method() {
        /* Axecode.tech: Универсальный гейт зависимости WooCommerce. */
        if (!yoga_require_woocommerce_for_ajax()) {
            return;
        }

        if (!isset($_POST['payment_nonce']) || !wp_verify_nonce($_POST['payment_nonce'], 'add_payment_method')) {
            yoga_ajax_error('Ошибка безопасности', 'invalid_nonce', 403);
        }

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        $user_id = get_current_user_id();
        $card_data = array(
            'id' => 'card_' . uniqid(),
            'brand' => sanitize_text_field($_POST['card_brand']),
            'last4' => sanitize_text_field($_POST['card_last4']),
            'exp_month' => sanitize_text_field($_POST['card_exp_month']),
            'exp_year' => sanitize_text_field($_POST['card_exp_year']),
            'type' => sanitize_text_field($_POST['card_type']),
        );

        $saved_cards = get_user_meta($user_id, 'saved_payment_cards', true) ?: array();
        $saved_cards[] = $card_data;

        update_user_meta($user_id, 'saved_payment_cards', $saved_cards);

        yoga_ajax_success('Карта успешно добавлена');
    }
}

if (!function_exists('handle_remove_payment_method')) {
    function handle_remove_payment_method() {
        /* Axecode.tech: Универсальный гейт зависимости WooCommerce. */
        if (!yoga_require_woocommerce_for_ajax()) {
            return;
        }

        if (!isset($_POST['card_id']) || !wp_verify_nonce($_POST['security'], 'remove_payment_method')) {
            yoga_ajax_error('Ошибка безопасности', 'invalid_nonce', 403);
        }

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        $user_id = get_current_user_id();
        $card_id = sanitize_text_field($_POST['card_id']);
        $saved_cards = get_user_meta($user_id, 'saved_payment_cards', true) ?: array();

        $updated_cards = array_filter($saved_cards, function ($card) use ($card_id) {
            return $card['id'] !== $card_id;
        });

        update_user_meta($user_id, 'saved_payment_cards', $updated_cards);

        yoga_ajax_success('Карта успешно удалена');
    }
}

/* Axecode.tech: Этап 3 — после выноса логики регистрируем хуки на уровне модуля. */
add_action('wp_ajax_add_payment_method', 'handle_add_payment_method');
add_action('wp_ajax_remove_payment_method', 'handle_remove_payment_method');

