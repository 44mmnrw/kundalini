<?php
/**
 * AJAX-обработчики: notifications.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_save_notification_preference(): void {
	if (!is_user_logged_in()) {
		wp_send_json_error(null, 401);
	}

	check_ajax_referer('yoga_ajax_nonce', 'nonce');
	$key = sanitize_key((string) ($_POST['key'] ?? ''));
	if (!array_key_exists($key, yoga_get_notification_preference_defaults())) {
		wp_send_json_error(null, 400);
	}

	$preferences = get_user_meta(get_current_user_id(), 'yoga_notification_preferences', true);
	$preferences = is_array($preferences) ? $preferences : array();
	$preferences[$key] = !empty($_POST['enabled']);
	update_user_meta(get_current_user_id(), 'yoga_notification_preferences', $preferences);

	wp_send_json_success();
}
add_action('wp_ajax_yoga_save_notification_preference', 'yoga_save_notification_preference');

function yoga_mark_question_answer_notifications_read(): void {
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Необходима авторизация.', 'yoga')), 401);
	}

	check_ajax_referer('yoga_ajax_nonce', 'nonce');
	$user_id = (int) get_current_user_id();
	$mark_all = !empty($_POST['mark_all']);
	$notification_id = sanitize_text_field((string) ($_POST['notification_id'] ?? ''));

	wp_send_json_success(array(
		'unread_count' => yoga_mark_user_notifications_read($user_id, $notification_id, $mark_all),
		'unread_question_answers_count' => count(yoga_get_unread_question_answer_notifications($user_id)),
	));
}
add_action('wp_ajax_yoga_mark_question_answer_notifications_read', 'yoga_mark_question_answer_notifications_read');
