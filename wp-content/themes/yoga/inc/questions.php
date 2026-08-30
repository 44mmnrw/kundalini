<?php
/**
 * Компонент темы: questions.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function get_user_questions(int $user_id): array {
	$args = array(
        'post_type' => 'question',
        'author' => $user_id,
		'meta_query' => array(
			'relation' => 'OR',
			array(
				'key' => 'question_source',
				'value' => 'lk',
			),
			array(
				'key' => 'question_source',
				'compare' => 'NOT EXISTS',
			),
		),
        'post_status' => array('publish', 'pending', 'draft', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
	);

	return get_posts($args);
}


function yoga_send_new_question_admin_email(int $question_id): void {
	$question = get_post($question_id);
	if (!$question instanceof WP_Post || $question->post_type !== 'question') {
		return;
	}

	$user = get_userdata((int) $question->post_author);
	$display_name = $user instanceof WP_User ? $user->display_name : __('Пользователь', 'yoga');
	$subject = __('Новый вопрос в личном кабинете', 'yoga');
	$message = sprintf(__('%s задал новый вопрос:', 'yoga'), $display_name) . "\n\n";
	$message .= $question->post_content . "\n\n";
	$message .= __('Ссылка для ответа:', 'yoga') . ' ' . admin_url("post.php?post={$question_id}&action=edit");

	if (function_exists('yoga_mail_send')) {
		yoga_mail_send('admin-new-question', array(
			'to' => (string) get_option('admin_email'),
			'subject' => $subject,
			'content' => nl2br(esc_html($message)),
		));
	} else {
		wp_mail((string) get_option('admin_email'), $subject, $message);
	}
}
add_action('yoga_send_new_question_admin_email', 'yoga_send_new_question_admin_email');

function yoga_get_question_author_name(int $user_id): string {
	$user = get_userdata($user_id);
	if (!$user instanceof WP_User) {
		return sprintf(__('Пользователь %d', 'yoga'), $user_id);
	}

	$name = trim((string) $user->display_name);
	return $name !== '' ? $name : (string) $user->user_login;
}

function yoga_get_practice_questions_notification_email(): string {
	$email = sanitize_email((string) get_option('yoga_practice_questions_notification_email', ''));
	return is_email($email) ? $email : sanitize_email((string) get_option('admin_email'));
}


function register_question_post_type() {


	$args = array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => array(),
        'labels' => array(
	'name' => 'Вопросы',
	'singular_name' => 'Вопрос',
	'menu_name' => 'Вопросы',
	'add_new' => 'Добавить вопрос',
	'add_new_item' => 'Добавить новый вопрос',
	'edit_item' => 'Редактировать вопрос',
	'new_item' => 'Новый вопрос',
	'view_item' => 'Просмотреть вопрос',
	'search_items' => 'Поиск вопросов',
	'not_found' => 'Вопросы не найдены',
	'not_found_in_trash' => 'Вопросы в корзине не найдены'
        )
	);

	register_post_type('question', $args);
}
add_action('init', 'register_question_post_type');


function yoga_get_question_answers(int $post_id): array {
	$answers = get_post_meta($post_id, '_question_answers', true);
	if (is_array($answers)) {
		return $answers;
	}


	$legacy_answer = (string) get_post_meta($post_id, '_answer', true);
	if (trim(wp_strip_all_tags($legacy_answer)) === '') {
		return array();
	}

	return array(array(
		'content' => $legacy_answer,
		'created_at' => (string) get_post_meta($post_id, '_answer_date', true),
		'admin_id' => (int) get_post_meta($post_id, '_answer_admin', true),
		'sent_at' => (string) get_post_meta($post_id, '_answer_sent_at', true),
		'email' => (string) get_post_meta($post_id, '_answer_sent_email', true),
		'status' => (string) get_post_meta($post_id, '_answer_delivery_status', true),
	));
}
