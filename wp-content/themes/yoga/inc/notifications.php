<?php
/**
 * Компонент темы: notifications.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_notification_has_live_source(array $notification): bool {
	$type = (string) ($notification['type'] ?? '');
	if ($type === 'comment_reply') {
		$comment_id = absint($notification['comment_id'] ?? 0);
		$comment = $comment_id > 0 ? get_comment($comment_id) : null;
		if (!$comment instanceof WP_Comment) {
			return false;
		}

		$parent_comment_id = absint($notification['parent_comment_id'] ?? $comment->comment_parent);
		return $parent_comment_id <= 0 || get_comment($parent_comment_id) instanceof WP_Comment;
	}

	if ($type !== 'question_answer') {
		return true;
	}

	$question_id = absint($notification['question_id'] ?? 0);
	if ($question_id <= 0) {
		return false;
	}

	$question = get_post($question_id);
	return $question instanceof WP_Post
		&& $question->post_type === 'question'
		&& $question->post_status !== 'trash';
}


function yoga_get_user_notifications(int $user_id, int $limit = 50): array {
	$notifications = get_user_meta($user_id, 'yoga_notifications', true);
	if (!is_array($notifications)) {
		return array();
	}
	$notifications = array_values(array_filter($notifications, static function ($notification): bool {
		return is_array($notification) && yoga_notification_has_live_source($notification);
	}));
	usort($notifications, static function (array $left, array $right): int {
		return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
	});
	return array_slice($notifications, 0, max(1, $limit));
}


function yoga_get_unread_user_notifications(int $user_id): array {
	$notifications = yoga_get_user_notifications($user_id, 100);
	return array_values(array_filter($notifications, static function (array $notification): bool {
		return empty($notification['read_at']);
	}));
}


function yoga_get_unread_question_answer_notifications(int $user_id): array {
	return array_values(array_filter(
		yoga_get_unread_user_notifications($user_id),
		static function (array $notification): bool {
			return ($notification['type'] ?? '') === 'question_answer';
		}
	));
}

function yoga_notification_preference(int $user_id, string $key, bool $default = true): bool {
	$preferences = get_user_meta($user_id, 'yoga_notification_preferences', true);
	return is_array($preferences) && array_key_exists($key, $preferences) ? (bool) $preferences[$key] : $default;
}

function yoga_get_notification_preference_defaults(): array {
	return apply_filters('yoga_notification_preference_defaults', array(
		'subscription_expiring_site' => true,
		'subscription_expiring_email' => true,
		'payment_card_expiring_site' => true,
		'payment_card_expiring_email' => false,
		'subscription_ended_site' => true,
		'subscription_ended_email' => true,
		'question_answer_site' => true,
		'question_answer_email' => false,
		'comment_reply_site' => false,
		'comment_reply_email' => true,
		'sadhana_progress_site' => true,
		'sadhana_progress_email' => true,
		'sadhana_interrupted_site' => false,
		'sadhana_interrupted_email' => false,
		'sadhana_completed_site' => true,
		'sadhana_completed_email' => true,
		'new_practices_email' => true,
		'new_articles_email' => false,
		'promotions_email' => true,
	));
}

function yoga_get_user_notification_preferences(int $user_id): array {
	$preferences = get_user_meta($user_id, 'yoga_notification_preferences', true);
	$preferences = is_array($preferences) ? $preferences : array();
	$result = yoga_get_notification_preference_defaults();
	foreach ($result as $key => $default) {
		if (array_key_exists($key, $preferences)) {
			$result[$key] = (bool) $preferences[$key];
		}
	}
	return $result;
}

function yoga_add_user_notification(int $user_id, string $type, string $title, string $message, string $url = '', array $context = array()): void {
	if ($user_id <= 0) {
		return;
	}
	$site_preference_keys = array(
		'question_answer' => 'question_answer_site',
		'comment_reply' => 'comment_reply_site',
		'subscription_expiring' => 'subscription_expiring_site',
		'payment_card_expiring' => 'payment_card_expiring_site',
		'subscription_ended' => 'subscription_ended_site',
		'sadhana_started' => 'sadhana_started_site',
		'sadhana_progress' => 'sadhana_progress_site',
		'sadhana_interrupted' => 'sadhana_interrupted_site',
		'sadhana_completed' => 'sadhana_completed_site',
	);
	if (isset($site_preference_keys[$type])) {
		$preference_key = $site_preference_keys[$type];
		$defaults = yoga_get_notification_preference_defaults();
		if (!yoga_notification_preference($user_id, $preference_key, (bool) ($defaults[$preference_key] ?? true))) {
			return;
		}
	}
	$notifications = yoga_get_user_notifications($user_id, 100);
	$dedupe_key = sanitize_text_field((string) ($context['dedupe_key'] ?? ''));
	if ($dedupe_key !== '') {
		$matching_indexes = array();
		foreach ($notifications as $index => $existing_notification) {
			$has_same_key = hash_equals((string) ($existing_notification['dedupe_key'] ?? ''), $dedupe_key);
			$is_legacy_match = ($existing_notification['type'] ?? '') === sanitize_key($type)
				&& ($existing_notification['title'] ?? '') === sanitize_text_field($title)
				&& ($existing_notification['message'] ?? '') === sanitize_text_field($message);
			if ($has_same_key || $is_legacy_match) {
				$matching_indexes[] = $index;
			}
		}
		if ($matching_indexes !== array()) {
			$keep_index = array_shift($matching_indexes);
			$notifications[$keep_index]['dedupe_key'] = $dedupe_key;
			$notifications[$keep_index]['title'] = sanitize_text_field($title);
			$notifications[$keep_index]['message'] = sanitize_text_field($message);
			$notifications[$keep_index]['url'] = esc_url_raw($url);
			foreach ($matching_indexes as $duplicate_index) {
				unset($notifications[$duplicate_index]);
			}
			update_user_meta($user_id, 'yoga_notifications', array_values($notifications));
			return;
		}
	}
	$notification = array(
		'id' => wp_generate_uuid4(),
		'type' => sanitize_key($type),
		'title' => sanitize_text_field($title),
		'message' => sanitize_text_field($message),
		'url' => esc_url_raw($url),
		'created_at' => current_time('mysql'),
		'read_at' => '',
	);
	if ($dedupe_key !== '') {
		$notification['dedupe_key'] = $dedupe_key;
	}
	if (!empty($context['question_id'])) {
		$notification['question_id'] = absint($context['question_id']);
	}
	if (!empty($context['answer_id'])) {
		$notification['answer_id'] = sanitize_text_field((string) $context['answer_id']);
	}
	if (!empty($context['comment_id'])) {
		$notification['comment_id'] = absint($context['comment_id']);
	}
	if (!empty($context['parent_comment_id'])) {
		$notification['parent_comment_id'] = absint($context['parent_comment_id']);
	}
	if (!empty($context['post_id'])) {
		$notification['post_id'] = absint($context['post_id']);
	}
	$notifications[] = $notification;
	update_user_meta($user_id, 'yoga_notifications', array_slice($notifications, -100));
}

function yoga_get_question_notification_user_id(int $question_id): int {
	$author_id = (int) get_post_field('post_author', $question_id);
	if ($author_id > 0) {
		return $author_id;
	}
	$email = sanitize_email((string) get_post_meta($question_id, 'contact_email', true));
	$user = $email !== '' ? get_user_by('email', $email) : false;
	return $user instanceof WP_User ? (int) $user->ID : 0;
}

function yoga_remove_question_notifications(int $question_id): void {
	if (get_post_type($question_id) !== 'question') {
		return;
	}

	$user_id = yoga_get_question_notification_user_id($question_id);
	if ($user_id <= 0) {
		return;
	}

	$notifications = get_user_meta($user_id, 'yoga_notifications', true);
	if (!is_array($notifications)) {
		return;
	}

	$remaining = array_values(array_filter($notifications, static function ($notification) use ($question_id): bool {
		return !is_array($notification)
			|| ($notification['type'] ?? '') !== 'question_answer'
			|| absint($notification['question_id'] ?? 0) !== $question_id;
	}));

	if (count($remaining) !== count($notifications)) {
		update_user_meta($user_id, 'yoga_notifications', $remaining);
	}
}
add_action('trashed_post', 'yoga_remove_question_notifications');
add_action('before_delete_post', 'yoga_remove_question_notifications');

function yoga_remove_question_answer_notifications(int $question_id, string $answer_id = ''): void {
	if ($question_id <= 0) {
		return;
	}

	$user_id = yoga_get_question_notification_user_id($question_id);
	if ($user_id <= 0) {
		return;
	}

	$notifications = get_user_meta($user_id, 'yoga_notifications', true);
	if (!is_array($notifications)) {
		return;
	}

	$remaining = array_values(array_filter($notifications, static function ($notification) use ($question_id, $answer_id): bool {
		if (!is_array($notification)
			|| ($notification['type'] ?? '') !== 'question_answer'
			|| absint($notification['question_id'] ?? 0) !== $question_id) {
			return true;
		}

		$notification_answer_id = sanitize_text_field((string) ($notification['answer_id'] ?? ''));
		return $answer_id !== '' && $notification_answer_id !== '' && !hash_equals($notification_answer_id, $answer_id);
	}));

	if (count($remaining) !== count($notifications)) {
		update_user_meta($user_id, 'yoga_notifications', $remaining);
	}
}

function yoga_remove_comment_reply_notifications(int $deleted_comment_id): void {
	if ($deleted_comment_id <= 0) {
		return;
	}

	$user_ids = get_users(array(
		'meta_key' => 'yoga_notifications',
		'fields' => 'ID',
	));
	foreach ($user_ids as $user_id) {
		$notifications = get_user_meta((int) $user_id, 'yoga_notifications', true);
		if (!is_array($notifications)) {
			continue;
		}

		$remaining = array_values(array_filter($notifications, static function ($notification) use ($deleted_comment_id): bool {
			if (!is_array($notification) || ($notification['type'] ?? '') !== 'comment_reply') {
				return true;
			}

			$reply_comment_id = absint($notification['comment_id'] ?? 0);
			$parent_comment_id = absint($notification['parent_comment_id'] ?? 0);
			if ($reply_comment_id === $deleted_comment_id || $parent_comment_id === $deleted_comment_id) {
				return false;
			}

			$reply_comment = $reply_comment_id > 0 ? get_comment($reply_comment_id) : null;
			return !($reply_comment instanceof WP_Comment && (int) $reply_comment->comment_parent === $deleted_comment_id);
		}));

		if (count($remaining) !== count($notifications)) {
			update_user_meta((int) $user_id, 'yoga_notifications', $remaining);
		}
	}
}
add_action('delete_comment', 'yoga_remove_comment_reply_notifications');
add_action('trashed_comment', 'yoga_remove_comment_reply_notifications');

function yoga_cleanup_orphaned_question_notifications(): void {
	if ((int) get_option('yoga_notification_source_schema_version', 0) >= 1) {
		return;
	}

	$user_ids = get_users(array(
		'meta_key' => 'yoga_notifications',
		'fields' => 'ID',
	));
	foreach ($user_ids as $user_id) {
		$notifications = get_user_meta((int) $user_id, 'yoga_notifications', true);
		if (!is_array($notifications)) {
			continue;
		}
		$remaining = array_values(array_filter($notifications, static function ($notification): bool {
			return is_array($notification) && yoga_notification_has_live_source($notification);
		}));
		if (count($remaining) !== count($notifications)) {
			update_user_meta((int) $user_id, 'yoga_notifications', $remaining);
		}
	}

	update_option('yoga_notification_source_schema_version', 1, false);
}
add_action('admin_init', 'yoga_cleanup_orphaned_question_notifications');

function yoga_get_lk_notifications_url(): string {
	return function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('notifications') : home_url('/');
}

function yoga_get_lk_questions_url(): string {
	return function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('questions') : home_url('/');
}

function yoga_mark_user_notifications_read(int $user_id, string $notification_id = '', bool $mark_all = false): int {
	$notifications = yoga_get_user_notifications($user_id, 100);
	$changed = false;
	foreach ($notifications as &$notification) {
		$is_selected = $notification_id !== '' && hash_equals((string) ($notification['id'] ?? ''), $notification_id);
		if (($mark_all || $is_selected) && empty($notification['read_at'])) {
			$notification['read_at'] = current_time('mysql');
			$changed = true;
		}
	}
	unset($notification);

	if ($changed) {
		update_user_meta($user_id, 'yoga_notifications', $notifications);
	}

	return count(yoga_get_unread_user_notifications($user_id));
}

function yoga_get_notification_read_url(array $notification, string $section = 'notifications'): string {
	$notification_id = sanitize_text_field((string) ($notification['id'] ?? ''));
	$url = function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url($section) : home_url('/');
	if ($notification_id === '') {
		return $url;
	}

	return add_query_arg(array(
		'read-notification' => $notification_id,
		'_yoga-notification-nonce' => wp_create_nonce('yoga_read_notification_' . $notification_id),
	), $url);
}

function yoga_handle_notification_read_route(): void {
	if (!is_user_logged_in() || empty($_GET['read-notification'])) {
		return;
	}

	$notification_id = sanitize_text_field(wp_unslash((string) $_GET['read-notification']));
	$nonce = sanitize_text_field(wp_unslash((string) ($_GET['_yoga-notification-nonce'] ?? '')));
	if ($notification_id === '' || !wp_verify_nonce($nonce, 'yoga_read_notification_' . $notification_id)) {
		return;
	}

	yoga_mark_user_notifications_read((int) get_current_user_id(), $notification_id);
}
add_action('template_redirect', 'yoga_handle_notification_read_route', 5);
