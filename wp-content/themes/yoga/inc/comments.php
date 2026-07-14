<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_user_public_name')) {
	function yoga_get_user_public_name(int $user_id): string {
		if ($user_id <= 0) {
			return '';
		}

		$user = get_userdata($user_id);
		if (!$user) {
			return '';
		}

		$first_name = trim((string) get_user_meta($user_id, 'first_name', true));
		$last_name = trim((string) get_user_meta($user_id, 'last_name', true));
		$full_name = trim($first_name . ' ' . $last_name);

		if ($full_name !== '') {
			return $full_name;
		}
		if ($first_name !== '') {
			return $first_name;
		}
		if (!empty($user->display_name)) {
			return (string) $user->display_name;
		}

		return (string) $user->user_login;
	}
}

if (!function_exists('yoga_get_user_avatar_id')) {
	function yoga_get_user_avatar_id(int $user_id): int {
		if ($user_id <= 0) {
			return 0;
		}

		$avatar_id = function_exists('get_field')
			? (int) get_field('user_avatar', 'user_' . $user_id)
			: (int) get_user_meta($user_id, 'user_avatar', true);

		return $avatar_id > 0 && get_post_type($avatar_id) === 'attachment' ? $avatar_id : 0;
	}
}

if (!function_exists('yoga_get_user_avatar_html')) {
	function yoga_get_user_avatar_html(int $user_id, int $size = 60, string $class = 'avatar'): string {
		if ($user_id > 0) {
			$avatar_id = yoga_get_user_avatar_id($user_id);
			if ($avatar_id > 0) {
				$attachment = wp_get_attachment_image(
					$avatar_id,
					array($size, $size),
					false,
					array(
						'class' => $class,
						'alt' => '',
						'loading' => 'lazy',
						'decoding' => 'async',
					)
				);
				if (!empty($attachment)) {
					return $attachment;
				}
			}
		}

		return get_avatar($user_id, $size, '', '', array('class' => $class));
	}
}

/**
 * Комментарий оставлен текущим залогиненным пользователем (по user_id или по email для legacy).
 */
function yoga_comment_is_owned_by_logged_in_user(WP_Comment $comment): bool {
	if (!is_user_logged_in()) {
		return false;
	}
	$current_id = (int) get_current_user_id();
	if ($current_id <= 0) {
		return false;
	}
	$comment_uid = (int) $comment->user_id;
	if ($comment_uid > 0 && $comment_uid === $current_id) {
		return true;
	}
	$user = wp_get_current_user();
	if (!$user || trim((string) $user->user_email) === '') {
		return false;
	}
	$c_email = trim((string) $comment->comment_author_email);
	return $c_email !== '' && strcasecmp($c_email, trim((string) $user->user_email)) === 0;
}

/**
 * После wp_new_comment/wp_insert_comment иногда остаётся user_id = 0 или пустой email — чиним привязку к автору.
 */
function yoga_practice_comment_fix_author_binding(int $comment_id, int $author_user_id): void {
	if ($comment_id <= 0 || $author_user_id <= 0) {
		return;
	}
	$c = get_comment($comment_id);
	if (!$c instanceof WP_Comment) {
		return;
	}
	$user = get_userdata($author_user_id);
	if (!$user) {
		return;
	}
	$updates = array('comment_ID' => $comment_id);
	if ((int) $c->user_id !== $author_user_id) {
		$updates['user_id'] = $author_user_id;
	}
	$email = trim((string) $user->user_email);
	if ($email !== '' && strcasecmp(trim((string) $c->comment_author_email), $email) !== 0) {
		$updates['comment_author_email'] = $email;
	}
	if (count($updates) > 1) {
		wp_update_comment($updates);
	}
}

/**
 * Типы записей, где включён единый AJAX-блок комментариев (практика, блог).
 */
function yoga_ajax_comment_supported_post_types(): array {
	return array('practice', 'post');
}

/**
 * Разрешить редактирование/удаление своего комментария без current_user_can('edit_comment'):
 * для CPT practice/post у автора часто нет edit_post на родительской записи.
 */
function yoga_user_can_manage_own_theme_comment(int $comment_id): bool {
	$c = get_comment($comment_id);
	if (!$c instanceof WP_Comment) {
		return false;
	}
	$post = get_post((int) $c->comment_post_ID);
	if (!$post instanceof WP_Post || !in_array($post->post_type, yoga_ajax_comment_supported_post_types(), true)) {
		return false;
	}
	return yoga_comment_is_owned_by_logged_in_user($c);
}

add_action('yoga_send_new_comment_notifications', static function (int $comment_id): void {
	wp_new_comment_notify_moderator($comment_id);
	wp_new_comment_notify_postauthor($comment_id);
});

/**
 * Insert a comment without making the visitor wait for WordPress email delivery.
 * Validation and flood protection still run through wp_new_comment when requested.
 *
 * @return int|false|WP_Error
 */
function yoga_insert_ajax_comment(array $comment_data, bool $validate = true) {
	remove_action('comment_post', 'wp_new_comment_notify_moderator');
	remove_action('comment_post', 'wp_new_comment_notify_postauthor');

	try {
		$comment_id = $validate
			? wp_new_comment($comment_data, true)
			: wp_insert_comment($comment_data);
	} finally {
		add_action('comment_post', 'wp_new_comment_notify_moderator');
		add_action('comment_post', 'wp_new_comment_notify_postauthor');
	}

	if (!is_wp_error($comment_id) && (int) $comment_id > 0) {
		wp_schedule_single_event(time() + 5, 'yoga_send_new_comment_notifications', array((int) $comment_id));
	}

	return $comment_id;
}


	
	// Отправка email администратору
	function enable_comments_for_practice(bool $open, int $post_id): bool {
		$post = get_post($post_id);
		if ($post instanceof WP_Post && $post->post_type === 'practice') {
			return true;
		}
		return $open;
	}
	add_filter('comments_open', 'enable_comments_for_practice', 10, 2);
	
	//$to = get_option('admin_email');
	function add_comments_support_for_practice() {
		add_post_type_support('practice', 'comments');
	}
	add_action('init', 'add_comments_support_for_practice');
	
	// Сохранение в базу данных (опционально)
	add_filter('avatar_defaults', 'custom_avatar_defaults');
	function custom_avatar_defaults(array $avatar_defaults): array {
		$avatar_defaults[get_template_directory_uri() . '/assets/img/default-avatar.png'] = 'Default Avatar';
		return $avatar_defaults;
	}
	
	// Сохранение сообщения в базу данных
	function russian_comment_time(string $date, string $d, WP_Comment $comment): string {
		if (!is_admin()) {
			return human_time_diff(get_comment_time('U'), current_time('timestamp')) . ' назад';
		}
		return $date;
	}
	add_filter('get_comment_date', 'russian_comment_time', 10, 3);

