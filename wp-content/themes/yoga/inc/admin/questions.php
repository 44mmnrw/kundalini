<?php
/**
 * Компонент темы: questions.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}





function yoga_migrate_question_record_statuses(): void {
	if ((int) get_option('yoga_question_record_schema_version', 0) >= 1) {
		return;
	}
	global $wpdb;

	$question_ids = get_posts(array(
		'post_type' => 'question',
		'post_status' => array('draft', 'pending', 'private'),
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'none',
	));

	if (!empty($question_ids)) {
		$updated = $wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_status = %s WHERE post_type = %s AND post_status IN (%s, %s, %s)",
			'publish',
			'question',
			'draft',
			'pending',
			'private'
		));
		if ($updated === false) {
			return;
		}
		foreach ($question_ids as $question_id) {
			clean_post_cache((int) $question_id);
		}
	}

	update_option('yoga_question_record_schema_version', 1, false);
}
add_action('admin_init', 'yoga_migrate_question_record_statuses');

function yoga_migrate_question_sources(): void {
	if ((int) get_option('yoga_question_source_schema_version', 0) >= 1) {
		return;
	}

	$questions = get_posts(array(
		'post_type' => 'question',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'orderby' => 'none',
	));

	foreach ($questions as $question) {
		$source = sanitize_key((string) get_post_meta($question->ID, 'question_source', true));
		if ($source === 'practice_form') {
			update_post_meta($question->ID, 'question_source', 'practice');
			continue;
		}
		if ($source !== '') {
			continue;
		}

		$contact_email = sanitize_email((string) get_post_meta($question->ID, 'contact_email', true));
		update_post_meta($question->ID, 'question_source', $contact_email !== '' ? 'faq' : 'lk');
	}

	update_option('yoga_question_source_schema_version', 1, false);
}
add_action('init', 'yoga_migrate_question_sources', 20);

function yoga_question_admin_post_states(array $post_states, WP_Post $post): array {
	if ($post->post_type !== 'question') {
		return $post_states;
	}

	return empty(yoga_get_question_answers((int) $post->ID))
		? array('yoga_new' => __('Новое', 'yoga'))
		: array();
}
add_filter('display_post_states', 'yoga_question_admin_post_states', 10, 2);





function yoga_question_remove_default_editor(): void {
	remove_post_type_support('question', 'title');
	remove_post_type_support('question', 'editor');
}
add_action('init', 'yoga_question_remove_default_editor', 999);


function add_question_answer_meta_box() {
	add_meta_box(
		'question_request',
		'Вопрос пользователя',
		'render_question_request_meta_box',
		'question',
		'normal',
		'high'
	);

	add_meta_box(
        'question_answer',
        'Ответ на вопрос',
        'render_question_answer_meta_box',
        'question',
        'normal',
        'high'
	);
}
add_action('add_meta_boxes', 'add_question_answer_meta_box');





function yoga_question_remove_publish_box(WP_Post $post): void {
	remove_meta_box('submitdiv', 'question', 'side');
}
add_action('add_meta_boxes_question', 'yoga_question_remove_publish_box', 100);

function yoga_question_screen_layout_columns(array $columns): array {
	$columns['question'] = 1;
	return $columns;
}
add_filter('screen_layout_columns', 'yoga_question_screen_layout_columns');

function yoga_question_force_single_column($columns): int {
	return 1;
}
add_filter('get_user_option_screen_layout_question', 'yoga_question_force_single_column');


function yoga_get_unanswered_questions_count(): int {
	$question_ids = get_posts(array(
		'post_type' => 'question',
		'post_status' => array('publish', 'pending', 'draft', 'private'),
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'orderby' => 'none',
	));

	$count = 0;
	foreach ($question_ids as $question_id) {
		if (empty(yoga_get_question_answers((int) $question_id))) {
			$count++;
		}
	}

	return $count;
}

function yoga_add_unanswered_questions_menu_count(): void {
	global $menu;

	$count = yoga_get_unanswered_questions_count();
	if ($count <= 0 || !is_array($menu)) {
		return;
	}

	foreach ($menu as &$menu_item) {
		if (!isset($menu_item[2]) || $menu_item[2] !== 'edit.php?post_type=question') {
			continue;
		}

		$menu_item[0] .= sprintf(
			' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
			$count,
			esc_html(sprintf(_n('%d вопрос без ответа', '%d вопросов без ответа', $count, 'yoga'), $count))
		);
		break;
	}
	unset($menu_item);
}
add_action('admin_menu', 'yoga_add_unanswered_questions_menu_count', 999);

function yoga_question_source_labels(): array {
	return array(
		'lk' => 'Вопросы в ЛК',
		'contacts' => 'Вопросы из контактов',
		'faq' => 'Вопросы FAQ',
		'practice' => 'Вопросы по крийям',
	);
}

function yoga_register_question_admin_submenus(): void {
	$parent = 'edit.php?post_type=question';
	foreach (yoga_question_source_labels() as $source => $label) {
		add_submenu_page(
			$parent,
			$label,
			$label,
			'edit_posts',
			$parent . '&question_source=' . $source
		);
	}

	add_submenu_page(
		$parent,
		'Уведомления по крийям',
		'Настройки уведомлений',
		'manage_options',
		'yoga-question-notifications',
		'yoga_render_question_notifications_settings'
	);

	global $submenu;
	if (isset($submenu[$parent][5][0])) {
		$submenu[$parent][5][0] = 'Все вопросы';
	}
}
add_action('admin_menu', 'yoga_register_question_admin_submenus', 20);

function yoga_highlight_question_source_submenu($submenu_file, $parent_file): string {
	if ($parent_file !== 'edit.php?post_type=question') {
		return (string) $submenu_file;
	}

	$source = isset($_GET['question_source']) ? sanitize_key(wp_unslash((string) $_GET['question_source'])) : '';
	if (!array_key_exists($source, yoga_question_source_labels())) {
		return (string) $submenu_file;
	}

	return 'edit.php?post_type=question&question_source=' . $source;
}
add_filter('submenu_file', 'yoga_highlight_question_source_submenu', 10, 2);

function yoga_filter_questions_by_source(WP_Query $query): void {
	if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'question') {
		return;
	}

	$source = isset($_GET['question_source']) ? sanitize_key(wp_unslash((string) $_GET['question_source'])) : '';
	if (!array_key_exists($source, yoga_question_source_labels())) {
		return;
	}

	$query->set('meta_key', 'question_source');
	$query->set('meta_value', $source);
}
add_action('pre_get_posts', 'yoga_filter_questions_by_source');

function yoga_question_admin_columns(array $columns): array {
	$columns['question_source'] = 'Источник';
	return $columns;
}
add_filter('manage_question_posts_columns', 'yoga_question_admin_columns');

function yoga_question_admin_column(string $column, int $post_id): void {
	if ($column !== 'question_source') {
		return;
	}

	$source = sanitize_key((string) get_post_meta($post_id, 'question_source', true));
	$labels = yoga_question_source_labels();
	echo esc_html($labels[$source] ?? 'Не указан');
}
add_action('manage_question_posts_custom_column', 'yoga_question_admin_column', 10, 2);

function yoga_sanitize_practice_questions_notification_email($value): string {
	$email = sanitize_email((string) $value);
	if ($email === '') {
		return '';
	}
	if (!is_email($email)) {
		add_settings_error(
			'yoga_practice_questions_notification_email',
			'invalid_email',
			'Укажите корректный адрес электронной почты.'
		);
		return sanitize_email((string) get_option('yoga_practice_questions_notification_email', ''));
	}
	return $email;
}

function yoga_register_question_notification_settings(): void {
	register_setting(
		'yoga_question_notifications',
		'yoga_practice_questions_notification_email',
		array(
			'type' => 'string',
			'sanitize_callback' => 'yoga_sanitize_practice_questions_notification_email',
			'default' => '',
		)
	);
}
add_action('admin_init', 'yoga_register_question_notification_settings');

function yoga_render_question_notifications_settings(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Уведомления по крийям', 'yoga'); ?></h1>
		<form action="options.php" method="post">
			<?php settings_fields('yoga_question_notifications'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="yoga-practice-questions-email"><?php esc_html_e('Адрес получателя', 'yoga'); ?></label></th>
					<td>
						<input class="regular-text" id="yoga-practice-questions-email" name="yoga_practice_questions_notification_email" type="email" value="<?php echo esc_attr((string) get_option('yoga_practice_questions_notification_email', '')); ?>" placeholder="<?php echo esc_attr((string) get_option('admin_email')); ?>">
						<p class="description"><?php esc_html_e('Сюда будут приходить уведомления о новых вопросах со страниц практик. Если поле пустое, используется основной email сайта.', 'yoga'); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function render_question_request_meta_box(WP_Post $post): void {
	$email = sanitize_email((string) get_post_meta($post->ID, 'contact_email', true));
	$date = get_post_meta($post->ID, 'contact_date', true) ?: $post->post_date;
	$answers = yoga_get_question_answers($post->ID);
	$notification_user_id = yoga_get_question_notification_user_id($post->ID);
	$email_notifications_enabled = $notification_user_id <= 0 || yoga_notification_preference($notification_user_id, 'question_answer_email', false);
	wp_nonce_field('manage_question_answers', 'question_answers_nonce');
	?>
	<div class="yoga-question-admin-card">
		<div class="yoga-question-admin-card__meta">
			<span><strong><?php esc_html_e('Получатель ответа:', 'yoga'); ?></strong> <?php echo $email ? esc_html($email) : esc_html__('эл. почта не указана', 'yoga'); ?></span>
			<span><strong><?php esc_html_e('Получено:', 'yoga'); ?></strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($date))); ?></span>
		</div>
		<div class="yoga-question-admin-card__message">
			<?php echo wpautop(esc_html($post->post_content)); ?>
		</div>
		<?php foreach ($answers as $answer_index => $answer): ?>
			<?php
			$content = isset($answer['content']) ? (string) $answer['content'] : '';
			$status = isset($answer['status']) ? (string) $answer['status'] : '';
			$sent_at = isset($answer['sent_at']) ? (string) $answer['sent_at'] : '';
			$created_at = isset($answer['created_at']) ? (string) $answer['created_at'] : '';
			$display_status = $status === 'failed' && !$email_notifications_enabled ? 'email_disabled' : $status;
			$status_labels = array(
				'sent' => __('Письмо отправлено', 'yoga'),
				'email_disabled' => __('Уведомления по эл. почте отключены', 'yoga'),
				'missing_recipient' => __('Не указана эл. почта получателя', 'yoga'),
				'failed' => __('Ошибка отправки письма', 'yoga'),
			);
			$status_label = $status_labels[$display_status] ?? __('Письмо не отправлено', 'yoga');
			$status_class = in_array($display_status, array('sent', 'email_disabled', 'failed'), true) ? ' yoga-question-admin-card__status--' . $display_status : '';
			?>
			<div class="yoga-question-admin-card__reply">
				<div class="yoga-question-admin-card__reply-meta">
					<strong><?php esc_html_e('Ответ администратора', 'yoga'); ?></strong>
					<button type="submit" class="button-link-delete yoga-question-admin-card__delete" name="question_delete_answer" value="<?php echo esc_attr((string) $answer_index); ?>" onclick="return window.confirm('Удалить этот ответ?');">
						<?php esc_html_e('Удалить', 'yoga'); ?>
					</button>
					<?php if ($sent_at && $display_status === 'sent'): ?>
						<span class="yoga-question-admin-card__status yoga-question-admin-card__status--sent"><?php echo esc_html($status_label); ?></span>
						<span><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($sent_at))); ?></span>
					<?php else: ?>
						<span class="yoga-question-admin-card__status<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
						<?php if ($created_at): ?><span><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($created_at))); ?></span><?php endif; ?>
					<?php endif; ?>
				</div>
				<div class="yoga-question-admin-card__reply-text">
					<?php echo wpautop(wp_kses_post($content)); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

function render_question_answer_meta_box(WP_Post $post): void {
	wp_nonce_field('save_question_answer', 'answer_nonce');
?>
	<?php $recipient_email = sanitize_email((string) get_post_meta($post->ID, 'contact_email', true)); ?>
	<div class="yoga-question-answer">
		<div class="yoga-question-answer__head">
			<div>
				<label for="question_answer"><?php esc_html_e('Новый ответ', 'yoga'); ?></label>
				<p><?php esc_html_e('После отправки текст появится под вопросом, а это поле очистится.', 'yoga'); ?></p>
			</div>
			<span class="yoga-question-answer__recipient"><?php echo $recipient_email ? esc_html($recipient_email) : esc_html__('эл. почта не указана', 'yoga'); ?></span>
		</div>
		<textarea id="question_answer" name="question_answer" class="large-text" rows="9" placeholder="Напишите ответ пользователю"></textarea>
		<div class="yoga-question-answer__footer">
			<button type="submit" class="button button-primary" name="question_send_reply" value="1">
				<?php esc_html_e('Отправить ответ', 'yoga'); ?>
			</button>
			<span><?php esc_html_e('Каждая отправка создаёт новый ответ и не изменяет предыдущие.', 'yoga'); ?></span>
		</div>
	</div>
    <?php
}

function yoga_question_admin_styles(): void {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'question') {
		return;
	}
	?>
	<style>
		#poststuff #post-body.columns-2 { margin-right: 0; }
		#post-body.columns-2 #postbox-container-1 { display: none; }
		#poststuff #post-body.columns-2 #postbox-container-2 { width: 100%; }
		#question_request .inside, #question_answer .inside { padding: 0; margin: 0; }
		.yoga-question-admin-card, .yoga-question-answer { padding: 20px; }
		.yoga-question-admin-card__meta { display: flex; flex-wrap: wrap; gap: 8px 24px; color: #50575e; font-size: 13px; }
		.yoga-question-admin-card__message { margin-top: 16px; padding: 18px 20px; border-left: 3px solid #2271b1; background: #f6f7f7; font-size: 15px; line-height: 1.6; }
		.yoga-question-admin-card__message > :first-child { margin-top: 0; }
		.yoga-question-admin-card__message > :last-child { margin-bottom: 0; }
		.yoga-question-admin-card__reply { margin-top: 16px; padding: 18px 20px; border: 1px solid #b8d8bd; border-radius: 4px; background: #f6fbf6; }
		.yoga-question-admin-card__reply-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; color: #50575e; font-size: 13px; }
		.yoga-question-admin-card__reply-meta strong { color: #1d2327; font-size: 14px; }
		.yoga-question-admin-card__delete { margin-left: auto; }
		.yoga-question-admin-card__status { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e7e7e7; color: #50575e; }
		.yoga-question-admin-card__status--sent { background: #d7f0d9; color: #1e6b2b; }
		.yoga-question-admin-card__status--email_disabled { background: #f0f0f1; color: #50575e; }
		.yoga-question-admin-card__status--failed { background: #fce2e3; color: #8a2424; }
		.yoga-question-admin-card__reply-text { margin-top: 12px; font-size: 14px; line-height: 1.6; }
		.yoga-question-admin-card__reply-text > :first-child { margin-top: 0; }
		.yoga-question-admin-card__reply-text > :last-child { margin-bottom: 0; }
		.yoga-question-answer__head, .yoga-question-answer__footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
		.yoga-question-answer__head label { display: block; font-weight: 600; font-size: 14px; }
		.yoga-question-answer__head p, .yoga-question-answer__footer span { margin: 4px 0 0; color: #646970; font-size: 13px; }
		.yoga-question-answer__recipient { padding: 6px 10px; border-radius: 999px; background: #f0f6fc; color: #135e96; font-size: 13px; }
		#question_answer textarea.large-text { display: block; width: 100%; min-height: 190px; margin: 16px 0; padding: 12px; border-color: #8c8f94; border-radius: 4px; line-height: 1.5; resize: vertical; }
		.yoga-question-answer__footer { align-items: flex-start; }
		.yoga-question-answer__delivery { margin: -4px 20px 16px; }
		@media (max-width: 782px) { .yoga-question-answer__head, .yoga-question-answer__footer { display: block; } .yoga-question-answer__recipient { display: inline-block; margin-top: 10px; } .yoga-question-answer__footer span { display: block; } }
	</style>
	<?php
}
add_action('admin_head-post.php', 'yoga_question_admin_styles');
add_action('admin_head-post-new.php', 'yoga_question_admin_styles');




function save_question_answer(int $post_id): void {
	if (!isset($_POST['answer_nonce']) || !wp_verify_nonce($_POST['answer_nonce'], 'save_question_answer')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['question_delete_answer']) && isset($_POST['question_answers_nonce']) && wp_verify_nonce($_POST['question_answers_nonce'], 'manage_question_answers')) {
		$answer_index = absint($_POST['question_delete_answer']);
		$answers = yoga_get_question_answers($post_id);
		if (array_key_exists($answer_index, $answers)) {
			$deleted_answer = $answers[$answer_index];
			array_splice($answers, $answer_index, 1);
			update_post_meta($post_id, '_question_answers', $answers);
			yoga_remove_question_answer_notifications(
				$post_id,
				is_array($deleted_answer) ? sanitize_text_field((string) ($deleted_answer['id'] ?? '')) : ''
			);
		}
		return;
	}


	if (!empty($_POST['question_send_reply']) && isset($_POST['question_answer'])) {
		$answer = wp_kses_post((string) $_POST['question_answer']);
		if (trim(wp_strip_all_tags($answer)) === '') {
			return;
		}

		$recipient_email = sanitize_email((string) get_post_meta($post_id, 'contact_email', true));
		if ($recipient_email === '') {
			$question_author = get_userdata((int) get_post_field('post_author', $post_id));
			$recipient_email = $question_author ? sanitize_email((string) $question_author->user_email) : '';
		}

		$question = get_post($post_id);
		$subject = sprintf(__('Ответ на ваш вопрос — %s', 'yoga'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
		$question_text = $question ? wp_strip_all_tags((string) $question->post_content) : '';
		$body = '<p>' . esc_html__('Здравствуйте!', 'yoga') . '</p>';
		$body .= '<p><strong>' . esc_html__('Ваш вопрос:', 'yoga') . '</strong><br>' . nl2br(esc_html($question_text)) . '</p>';
		$body .= '<p><strong>' . esc_html__('Ответ:', 'yoga') . '</strong></p>' . wpautop($answer);
		$body .= '<p>' . esc_html__('С уважением, администрация сайта.', 'yoga') . '</p>';
		$notification_user_id = yoga_get_question_notification_user_id($post_id);
		$email_notifications_enabled = $notification_user_id <= 0 || yoga_notification_preference($notification_user_id, 'question_answer_email', false);
		$sent = false;
		if ($recipient_email === '') {
			$delivery_status = 'missing_recipient';
		} elseif (!$email_notifications_enabled) {
			$delivery_status = 'email_disabled';
		} else {
			$sent = function_exists('yoga_mail_send')
				? yoga_mail_send('question-answer', array(
					'to' => $recipient_email,
					'subject' => $subject,
					'content' => $body,
				))
				: wp_mail($recipient_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
			$delivery_status = $sent ? 'sent' : 'failed';
		}

		$answer_id = wp_generate_uuid4();
		$answers = yoga_get_question_answers($post_id);
		$answers[] = array(
			'id' => $answer_id,
			'content' => $answer,
			'created_at' => current_time('mysql'),
			'admin_id' => get_current_user_id(),
			'sent_at' => $sent ? current_time('mysql') : '',
			'email' => $recipient_email,
			'status' => $delivery_status,
		);
		update_post_meta($post_id, '_question_answers', $answers);

		$notification_user_id = yoga_get_question_notification_user_id($post_id);
		if ($notification_user_id > 0) {
			yoga_add_user_notification(
				$notification_user_id,
				'question_answer',
				__('Получен ответ на ваш вопрос', 'yoga'),
				__('Администратор ответил на ваш вопрос. Откройте раздел «Мои вопросы».', 'yoga'),
				yoga_get_lk_questions_url(),
				array('question_id' => $post_id, 'answer_id' => $answer_id)
			);
		}
		return;
	}

	if (isset($_POST['question_answer'])) {
		$answer = wp_kses_post($_POST['question_answer']);
		$old_answer = get_post_meta($post_id, '_answer', true);

		update_post_meta($post_id, '_answer', $answer);
		if ($answer !== $old_answer) {
			update_post_meta($post_id, '_answer_date', current_time('mysql'));
			update_post_meta($post_id, '_answer_admin', get_current_user_id());
		}


		if (empty($_POST['question_send_reply'])) {
			return;
		}

		if (trim(wp_strip_all_tags($answer)) === '') {
			update_post_meta($post_id, '_answer_delivery_status', 'empty_answer');
			return;
		}

		$recipient_email = sanitize_email((string) get_post_meta($post_id, 'contact_email', true));
		if ($recipient_email === '') {
			$question_author = get_userdata((int) get_post_field('post_author', $post_id));
			$recipient_email = $question_author ? sanitize_email((string) $question_author->user_email) : '';
		}

		if ($recipient_email === '') {
			update_post_meta($post_id, '_answer_delivery_status', 'missing_recipient');
			return;
		}

		$question = get_post($post_id);
		$subject = sprintf(__('Ответ на ваш вопрос — %s', 'yoga'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
		$question_text = $question ? wp_strip_all_tags((string) $question->post_content) : '';
		$body = '<p>' . esc_html__('Здравствуйте!', 'yoga') . '</p>';
		$body .= '<p><strong>' . esc_html__('Ваш вопрос:', 'yoga') . '</strong><br>' . nl2br(esc_html($question_text)) . '</p>';
		$body .= '<p><strong>' . esc_html__('Ответ:', 'yoga') . '</strong></p>' . wpautop(wp_kses_post($answer));
		$body .= '<p>' . esc_html__('С уважением, администрация сайта.', 'yoga') . '</p>';

		$sent = function_exists('yoga_mail_send')
			? yoga_mail_send('question-answer', array(
				'to' => $recipient_email,
				'subject' => $subject,
				'content' => $body,
			))
			: wp_mail($recipient_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
		update_post_meta($post_id, '_answer_delivery_status', $sent ? 'sent' : 'failed');
		if ($sent) {
			update_post_meta($post_id, '_answer_sent_at', current_time('mysql'));
			update_post_meta($post_id, '_answer_sent_by', get_current_user_id());
			update_post_meta($post_id, '_answer_sent_email', $recipient_email);
		}

		return;


		if ($answer !== $old_answer) {
			update_post_meta($post_id, '_answer_date', current_time('mysql'));
			update_post_meta($post_id, '_answer_admin', get_current_user_id());


			$question = get_post($post_id);
			$user = get_userdata($question->post_author);
			$subject = 'Ответ на ваш вопрос';
			$message = "Здравствуйте, {$user->display_name}!\n\n";
			$message .= "На ваш вопрос получен ответ:\\n\\n";
			$message .= "Вопрос: {$question->post_content}\n\n";
			$message .= "Ответ: {$answer}\n\n";
			$message .= "С уважением, администрация сайта";

			if (function_exists('yoga_mail_send')) {
				yoga_mail_send('question-answer', array(
					'to' => $user->user_email,
					'subject' => $subject,
					'content' => nl2br(esc_html($message)),
				));
			} else {
				wp_mail($user->user_email, $subject, $message);
			}
		}
	}
}
add_action('save_post_question', 'save_question_answer');
