<?php

if (!defined('ABSPATH')) {
    exit;
}

/* Axecode.tech: Этап 3 (универсальность).
 * Модуль вынесенной логики избранного. Подготовлен для поэтапной миграции из functions.php.
 */

if (!function_exists('toggle_favorite_practice')) {
    function toggle_favorite_practice() {
        /* Axecode.tech: CSRF-проверка обязательна для изменения пользовательских данных через AJAX. */
        check_ajax_referer('favorite_practice_nonce', 'security');

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        $practice_id = intval($_POST['practice_id']);
        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'favorite_practices', true);

        if (empty($favorites)) {
            $favorites = array();
        }

        if (in_array($practice_id, $favorites, true)) {
            $favorites = array_diff($favorites, array($practice_id));
            $message = 'Удалено из избранного';
        } else {
            $favorites[] = $practice_id;
            $message = 'Добавлено в избранное';
        }

        update_user_meta($user_id, 'favorite_practices', $favorites);

        yoga_ajax_success($message);
    }
}

/* Axecode.tech: Этап 3 — после выноса логики регистрируем хук на уровне модуля. */
add_action('wp_ajax_toggle_favorite_practice', 'toggle_favorite_practice');

