<?php
/**
 * AJAX-обработчики: comments.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}


function yoga_comment_ajax_success(int $comment_id): void {
	$comment = get_comment($comment_id);
	if (!$comment instanceof WP_Comment) {
		wp_send_json_error('Комментарий сохранён, но не удалось обновить список');
	}

	wp_send_json_success(array(
		'comment_id' => $comment_id,
		'parent_id'  => (int) $comment->comment_parent,
		'html'       => yoga_render_ajax_comment($comment_id),
	));
}


add_action('wp_ajax_submit_custom_comment', 'handle_custom_comment');
add_action('wp_ajax_nopriv_submit_custom_comment', 'handle_custom_comment');

function handle_custom_comment() {

    if (!isset($_POST['comment_security']) || !wp_verify_nonce($_POST['comment_security'], 'yoga_ajax_nonce')) {
        wp_send_json_error('Ошибка безопасности');
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $comment_content = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

    $target_post = $post_id > 0 ? get_post($post_id) : null;
    if (!$target_post instanceof WP_Post || !in_array($target_post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
        wp_send_json_error('Комментирование для этой записи недоступно');
    }

    if ($comment_content === '') {
        wp_send_json_error('Введите текст комментария');
    }

    if (!comments_open($post_id)) {
        wp_send_json_error('Комментирование для этой записи закрыто');
    }

    if (!is_user_logged_in() && get_option('comment_registration')) {
        wp_send_json_error('Для отправки комментария необходимо авторизоваться');
    }


    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = yoga_get_user_public_name((int) $current_user->ID);
        if ($comment_author === '') {
            $comment_author = $current_user->display_name ?: $current_user->user_login;
        }
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Гость';
        $comment_author_email = '';
        $user_id = 0;
    }


    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $comment_content,
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'comment_author_url' => '',
        'user_id' => (int) $user_id,
        'comment_approved' => 1
    );

    $comment_id = yoga_insert_ajax_comment($comment_data, true);

    if (!is_wp_error($comment_id) && $comment_id) {
        yoga_practice_comment_fix_author_binding((int) $comment_id, is_user_logged_in() ? (int) get_current_user_id() : 0);
        yoga_comment_ajax_success((int) $comment_id);
    } else {
        $error_message = is_wp_error($comment_id)
            ? $comment_id->get_error_message()
            : '';


        if (!$error_message) {
            $fallback_comment_id = yoga_insert_ajax_comment($comment_data, false);
            if ($fallback_comment_id) {
                yoga_practice_comment_fix_author_binding((int) $fallback_comment_id, is_user_logged_in() ? (int) get_current_user_id() : 0);
                yoga_comment_ajax_success((int) $fallback_comment_id);
            }
        }

        global $wpdb;
        if (!$error_message && !empty($wpdb->last_error)) {
            $error_message = 'DB: ' . $wpdb->last_error;
        }

        if (!$error_message) {
            $error_message = 'Ошибка при добавлении комментария';
        }

        error_log('handle_custom_comment failed. post_id=' . $post_id . '; user_id=' . $user_id . '; error=' . $error_message);
        wp_send_json_error($error_message);
    }
}


add_action('wp_ajax_submit_comment_reply', 'handle_comment_reply');
add_action('wp_ajax_nopriv_submit_comment_reply', 'handle_comment_reply');

add_action('yoga_send_comment_reply_email', static function (string $email, string $subject, string $message): void {
	if (is_email($email)) {
		if (function_exists('yoga_mail_send')) {
			yoga_mail_send('comment-reply', array(
				'to' => $email,
				'subject' => $subject,
				'content' => nl2br(esc_html($message)),
			));
		} else {
			wp_mail($email, $subject, $message);
		}
	}
}, 10, 3);

function handle_comment_reply() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_send_json_error('Ошибка безопасности');
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Для ответа необходимо авторизоваться');
    }


    $current_user = wp_get_current_user();
    $comment_author = yoga_get_user_public_name((int) $current_user->ID);
    if ($comment_author === '') {
        $comment_author = $current_user->display_name ?: $current_user->user_login;
    }
    $comment_author_email = $current_user->user_email;
    $user_id = $current_user->ID;

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';

    $reply_post = $post_id > 0 ? get_post($post_id) : null;
    if (!$reply_post instanceof WP_Post || !in_array($reply_post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
        wp_send_json_error('Ответ на комментарий для этой записи недоступен');
    }

    $parent_comment = $parent_id > 0 ? get_comment($parent_id) : null;
    if ($parent_id <= 0 || !$parent_comment) {
        wp_send_json_error('Некорректный родительский комментарий');
    }

    if ((int) $parent_comment->comment_post_ID !== $post_id) {
        wp_send_json_error('Некорректная привязка ответа к комментарию');
    }

    if ($content === '') {
        wp_send_json_error('Введите текст ответа');
    }

    if (!comments_open($post_id)) {
        wp_send_json_error('Комментирование для этой записи закрыто');
    }

    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $content,
        'comment_parent' => $parent_id,
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'user_id' => (int) $user_id,
        'comment_approved' => 1,
    );

    $comment_id = yoga_insert_ajax_comment($comment_data, false);

    if ($comment_id) {
        yoga_practice_comment_fix_author_binding((int) $comment_id, (int) $user_id);
		$recipient_user_id = (int) $parent_comment->user_id;
		if ($recipient_user_id > 0 && $recipient_user_id !== (int) $user_id) {
			$reply_url = get_permalink($post_id) . '#comment-' . (int) $comment_id;
			$reply_message = sprintf(__('%s ответил(а) на ваш комментарий.', 'yoga'), $comment_author);
			yoga_add_user_notification(
				$recipient_user_id,
				'comment_reply',
				__('Ответ на комментарий', 'yoga'),
				$reply_message,
				$reply_url,
				array(
					'comment_id' => (int) $comment_id,
					'parent_comment_id' => $parent_id,
					'post_id' => $post_id,
				)
			);

			if (yoga_notification_preference($recipient_user_id, 'comment_reply_email', true)) {
				$recipient = get_userdata($recipient_user_id);
				if ($recipient instanceof WP_User && is_email($recipient->user_email)) {
					wp_schedule_single_event(time() + 5, 'yoga_send_comment_reply_email', array(
						(string) $recipient->user_email,
						(string) __('Ответ на ваш комментарий', 'yoga'),
						(string) ($reply_message . "\n\n" . $reply_url),
					));
				}
			}
		}
        yoga_comment_ajax_success((int) $comment_id);
    } else {
        wp_send_json_error('Ошибка при добавлении ответа');
    }
}


add_action('wp_ajax_update_comment', 'handle_comment_update');

function handle_comment_update() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }

    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);

    if (!$comment instanceof WP_Comment || !yoga_user_can_manage_own_theme_comment($comment_id)) {
        wp_send_json_error('Недостаточно прав для редактирования комментария');
    }

    $comment_data = array(
        'comment_ID' => $comment_id,
        'comment_content' => sanitize_textarea_field($_POST['content']),
    );

    $result = wp_update_comment($comment_data);

    if ($result) {
        wp_send_json_success(array(
			'comment_id' => $comment_id,
			'html' => yoga_render_ajax_comment($comment_id),
		));
    } else {
        wp_send_json_error('Ошибка при обновлении комментария');
    }
}


add_action('wp_ajax_delete_comment', 'handle_comment_delete');

function handle_comment_delete() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }

    $comment_id = intval($_POST['comment_id']);

    if (!yoga_user_can_manage_own_theme_comment($comment_id)) {
        wp_send_json_error('Недостаточно прав для удаления комментария');
    }

    $result = wp_delete_comment($comment_id, true);

    if ($result) {
        wp_send_json_success('Комментарий удален');
    } else {
        wp_send_json_error('Ошибка при удалении комментария');
    }
}
