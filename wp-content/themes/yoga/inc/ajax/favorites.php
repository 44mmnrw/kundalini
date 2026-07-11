<?php

if (!defined('ABSPATH')) {
    exit;
}

/* Axecode.tech: Этап 3 (универсальность).
 * Модуль вынесенной логики избранного. Подготовлен для поэтапной миграции из functions.php.
 */

if (!function_exists('toggle_favorite_practice')) {
    function yoga_normalize_favorites($favorites): array {
        if (empty($favorites)) {
            return array();
        }

        if (is_string($favorites)) {
            // Поддержка legacy-формата: "1,2,3"
            $favorites = array_filter(array_map('trim', explode(',', $favorites)));
        }

        if (!is_array($favorites)) {
            $favorites = array($favorites);
        }

        $favorites = array_map('intval', $favorites);
        $favorites = array_filter($favorites, function($id) {
            return $id > 0;
        });

        return array_values(array_unique($favorites));
    }

    function toggle_favorite_practice() {
        /* Axecode.tech: CSRF-проверка обязательна для изменения пользовательских данных через AJAX. */
        check_ajax_referer('yoga_ajax_nonce', 'security');

        if (!is_user_logged_in()) {
            yoga_ajax_error('Не авторизован', 'not_authenticated', 401);
        }

        $practice_id = intval($_POST['practice_id']);
        if ($practice_id <= 0) {
            yoga_ajax_error('Некорректный ID практики', 'invalid_practice_id', 400);
        }

        $user_id = get_current_user_id();
        $favorites = yoga_normalize_favorites(get_user_meta($user_id, 'favorite_practices', true));

        if (in_array($practice_id, $favorites, true)) {
            $favorites = array_values(array_diff($favorites, array($practice_id)));
            $message = 'Удалено из избранного';
			$is_favorite = false;
        } else {
            $favorites[] = $practice_id;
            $favorites = array_values(array_unique(array_map('intval', $favorites)));
            $message = 'Добавлено в избранное';
			$is_favorite = true;
        }

        update_user_meta($user_id, 'favorite_practices', $favorites);

        yoga_ajax_success($message, array(
			'is_favorite' => $is_favorite,
			'favorites_count' => count($favorites),
		));
    }
}

/* Axecode.tech: Этап 3 — после выноса логики регистрируем хук на уровне модуля. */
add_action('wp_ajax_toggle_favorite_practice', 'toggle_favorite_practice');
add_action('wp_ajax_nopriv_toggle_favorite_practice', 'toggle_favorite_practice');

