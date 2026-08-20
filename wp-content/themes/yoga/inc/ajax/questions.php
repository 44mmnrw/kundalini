<?php
/**
 * AJAX-обработчики: questions.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}




function handle_question_submission() {
	$is_ajax = wp_doing_ajax();
	if (!isset($_POST['question_nonce']) || !wp_verify_nonce($_POST['question_nonce'], 'submit_question')) {
		if ($is_ajax) {
			wp_send_json_error(array('message' => __('Ошибка безопасности', 'yoga')), 403);
		}
		wp_die('Ошибка безопасности');
	}

	if (!is_user_logged_in()) {
		if ($is_ajax) {
			wp_send_json_error(array('message' => __('Вы не авторизованы', 'yoga')), 401);
		}
		wp_die('Вы не авторизованы');
	}

	$question_text = sanitize_textarea_field(wp_unslash((string) ($_POST['question_text'] ?? '')));

	if (empty($question_text)) {
		if ($is_ajax) {
			wp_send_json_error(array('message' => __('Вопрос не может быть пустым', 'yoga')), 400);
		}
		wp_die('Вопрос не может быть пустым');
	}

	$user_id = get_current_user_id();
	$author_name = yoga_get_question_author_name((int) $user_id);


	$question_data = array(
	'post_title' => sprintf(__('Вопрос от %s', 'yoga'), $author_name),
        'post_content' => $question_text,
        'post_status' => 'publish',
        'post_type' => 'question',
		'post_author' => $user_id,
		'meta_input' => array(
			'question_source' => 'lk',
		)
	);

	$question_id = wp_insert_post($question_data);

	if (is_wp_error($question_id)) {
		if ($is_ajax) {
			wp_send_json_error(array('message' => __('Ошибка при сохранении вопроса', 'yoga')), 500);
		}
		wp_die('Ошибка при сохранении вопроса');
	}


	wp_schedule_single_event(time(), 'yoga_send_new_question_admin_email', array((int) $question_id));

	if ($is_ajax) {
		ob_start();
		yoga_render_user_questions_list((int) $user_id);
		$questions_html = (string) ob_get_clean();
		wp_send_json_success(array(
			'message' => __('Вопрос отправлен', 'yoga'),
			'questions_html' => $questions_html,
		));
	}

	wp_redirect(add_query_arg('question_submitted', 'true', wp_get_referer()));
	exit;
}
add_action('admin_post_submit_question', 'handle_question_submission');
add_action('admin_post_nopriv_submit_question', 'handle_question_submission');
add_action('wp_ajax_submit_question', 'handle_question_submission');


	add_action('wp_ajax_faq_contact_form', 'handle_faq_contact_form');
	add_action('wp_ajax_nopriv_faq_contact_form', 'handle_faq_contact_form');

	function handle_faq_contact_form() {

		if (!wp_verify_nonce($_POST['faq_nonce'], 'faq_contact_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
			exit;
		}


		$name = sanitize_text_field($_POST['name']);
		$email = sanitize_email($_POST['email']);
		$message = sanitize_textarea_field($_POST['message']);


		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$profile_name = sanitize_text_field((string) $current_user->display_name);
			$profile_email = sanitize_email((string) $current_user->user_email);

			if ($profile_name !== '') {
				$name = $profile_name;
			}
			if ($profile_email !== '') {
				$email = $profile_email;
			}
		}


		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
			exit;
		}






































		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
			exit;
		}


		$to = get_option('admin_email');
		$subject = 'Новый вопрос из раздела FAQ: ' . $name;
		$headers = array('Content-Type: text/html; charset=UTF-8');

		$body = "
        <h3>Новый вопрос из раздела FAQ</h3>
        <p><strong>Имя:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Вопрос:</strong></p>
        <p>" . nl2br($message) . "</p>
        <hr>
        <p><small>Сообщение отправлено с сайта " . get_bloginfo('name') . "</small></p>
		";

		$post_id = wp_insert_post(array(
			'post_title' => 'Вопрос от ' . $name,
			'post_content' => $message,
			'post_type' => 'question',
			'post_status' => 'publish',
            'meta_input' => array(
			'contact_email' => $email,
			'contact_date' => current_time('mysql'),
			'question_source' => 'faq'
            )
		), true);

		if (is_wp_error($post_id) || !$post_id) {
			wp_send_json_error(array('message' => 'Не удалось сохранить вопрос. Попробуйте еще раз.'));
			exit;
		}


		$email_sent = wp_mail($to, $subject, $body, $headers);
		$question_success_url = home_url('/question-sent/');

		wp_send_json_success(array(
            'message' => get_field('faq_form_success_message', 'option') ?: 'Ваш вопрос отправлен! Мы ответим вам в ближайшее время.',
            'mail_sent' => (bool) $email_sent,
			'redirect_url' => $question_success_url
		));

		exit;
	}
