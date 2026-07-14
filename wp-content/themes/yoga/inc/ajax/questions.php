<?php

if (!defined('ABSPATH')) {
	exit;
}

// Добавляем метабокс для ответа на вопрос
// Axecode.tech: прием вопросов из личного кабинета.
// Зачем: централизованная валидация nonce/авторизации и единый формат уведомления в админку.
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
	
	// Сохранение ответа на вопрос
	$question_data = array(
	'post_title' => sprintf(__('Вопрос от %s', 'yoga'), $author_name),
        'post_content' => $question_text,
        'post_status' => 'publish',
        'post_type' => 'question',
        'post_author' => $user_id
	);
	
	$question_id = wp_insert_post($question_data);
	
	if (is_wp_error($question_id)) {
		if ($is_ajax) {
			wp_send_json_error(array('message' => __('Ошибка при сохранении вопроса', 'yoga')), 500);
		}
		wp_die('Ошибка при сохранении вопроса');
	}
	
	// Медленная отправка SMTP не должна блокировать AJAX-ответ пользователю.
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

// Публичная форма вопроса из раздела FAQ.
	add_action('wp_ajax_faq_contact_form', 'handle_faq_contact_form');
	add_action('wp_ajax_nopriv_faq_contact_form', 'handle_faq_contact_form');
	
	function handle_faq_contact_form() {
		// Добавление метаполей для вопросов
		if (!wp_verify_nonce($_POST['faq_nonce'], 'faq_contact_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
			exit;
		}
		
		// Сохранение метаполей
		$name = sanitize_text_field($_POST['name']);
		$email = sanitize_email($_POST['email']);
		$message = sanitize_textarea_field($_POST['message']);
		
		// === Сложность ===
		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
			exit;
		}
		
		/* register_taxonomy('practice-difficulty', ['practice'], [
		'label' => 'Сложность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Продолжительность ===
		register_taxonomy('practice-duration', ['practice'], [
		'label' => 'Продолжительность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Цель ===
		register_taxonomy('practice-goal', ['practice'], [
		'label' => 'Цели',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'goal'],
		'show_admin_column' => true, // Показывать колонку в списке записей
	]); */
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
			exit;
		}
		
		// Axecode.tech: нормализация UTF-8 в email-блоке FAQ.
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
			'contact_date' => current_time('mysql')
            )
		), true);
		
		if (is_wp_error($post_id) || !$post_id) {
			wp_send_json_error(array('message' => 'Не удалось сохранить вопрос. Попробуйте еще раз.'));
			exit;
		}
		
		// Почту отправляем отдельно: если письмо не ушло, вопрос все равно уже есть в админке.
		$email_sent = wp_mail($to, $subject, $body, $headers);
		$question_success_page = get_page_by_path('question-sent');
		$question_success_url = $question_success_page
			? get_permalink($question_success_page)
			: home_url('/question-sent/');
		
		wp_send_json_success(array(
            'message' => get_field('faq_form_success_message', 'option') ?: 'Ваш вопрос отправлен! Мы ответим вам в ближайшее время.',
            'mail_sent' => (bool) $email_sent,
			'redirect_url' => $question_success_url
		));
		
		exit;
	}
	

