<?php
/**
 * Компонент темы: questions.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function display_question_item(WP_Post $question, bool $hidden = false): void {
	$question_id = $question->ID;
	$answers = yoga_get_question_answers($question_id);

	$status_class = !empty($answers) ? '' : 'lk-questions-item_new';
	$hidden_class = $hidden ? 'hidden' : '';
	$extra_class = $hidden ? 'lk-questions-item_extra' : '';
?>
    <div class="lk-questions-item <?php echo $status_class . ' ' . $hidden_class . ' ' . $extra_class; ?>" data-question-date="<?php echo esc_attr($question->post_date); ?>" data-question-id="<?php echo esc_attr((string) $question_id); ?>">
        <div class="lk-question">
            <div class="lk-question__body">
                <div class="lk-question__head">
                    <?php $practice_id = (int) get_post_meta($question_id, 'practice_id', true); ?>
                    <?php if ($practice_id > 0 && get_post_type($practice_id) === 'practice'): ?>
                    <a class="lk-question__practice<?php echo empty($answers) ? '' : ' lk-question__practice--answered'; ?>" href="<?php echo esc_url(get_permalink($practice_id)); ?>"><?php echo esc_html(get_the_title($practice_id)); ?></a>
                    <?php endif; ?>
                    <div class="lk-question__time">
                        <time><?php echo get_the_date('d.m.Y', $question_id); ?></time>
                        <time><?php echo get_the_time('H:i', $question_id); ?></time>
                    </div>
                </div>
                <div class="lk-question__text">
                    <p><?php echo esc_html($question->post_content); ?></p>
                </div>
            </div>
		<?php if (empty($answers)): ?>
		<span class="lk-question__status">
			<img class="lk-question__status-icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/questions-waiting-clock.svg'); ?>" alt="">
			<span><?php esc_html_e('Ожидает ответа', 'yoga'); ?></span>
		</span>
		<?php else: ?>
		<span class="lk-question__status lk-question__status--answered">
			<img class="lk-question__status-icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/questions-answered-check.svg'); ?>" alt="">
			<span><?php esc_html_e('Отвечено', 'yoga'); ?></span>
		</span>
		<?php endif; ?>
	</div>

	<?php foreach ($answers as $answer): ?>
	<?php
		$answer_content = (string) ($answer['content'] ?? '');
		$answer_date = (string) ($answer['created_at'] ?? '');
		$admin_id = (int) ($answer['admin_id'] ?? 0);
		$answer_author = $admin_id > 0 ? get_userdata($admin_id) : false;
		$is_administrator = $answer_author instanceof WP_User
			&& in_array('administrator', (array) $answer_author->roles, true);
		$admin_name = $admin_id > 0 ? (string) get_the_author_meta('display_name', $admin_id) : __('Администратор', 'yoga');
		$answer_label = $is_administrator || !$answer_author
			? __('Ответ администратора', 'yoga')
			: __('Ответ преподавателя', 'yoga');
		$answer_timestamp = $answer_date !== '' ? strtotime($answer_date) : false;
	?>
	<div class="lk-question lk-question_answer">
		<div class="lk-question__time">
			<b title="<?php echo esc_attr($admin_name); ?>"><?php echo esc_html($answer_label); ?></b>
			<?php if ($answer_timestamp): ?>
			<span class="lk-question__date">
				<time datetime="<?php echo esc_attr(wp_date('c', $answer_timestamp)); ?>"><?php echo esc_html(wp_date('d.m.Y', $answer_timestamp)); ?></time>
				<time><?php echo esc_html(wp_date('H:i', $answer_timestamp)); ?></time>
			</span>
			<?php endif; ?>
		</div>
		<div class="lk-question__text">
			<?php echo wpautop(wp_kses_post($answer_content)); ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
    <?php
}

function yoga_render_user_questions_list(int $user_id, string $active_tab = 'practice'): void {
	$groups = array('general' => array(), 'practice' => array());
	foreach (get_user_questions($user_id, true) as $question) {
		$source = (string) get_post_meta($question->ID, 'question_source', true);
		$groups[in_array($source, array('practice', 'practice_form'), true) ? 'practice' : 'general'][] = $question;
	}
	$active_tab = $active_tab === 'practice' && $groups['practice'] ? 'practice' : 'general';
	$asset_url = get_template_directory_uri() . '/assets/svg/';
	$labels = array('general' => __('Общие вопросы', 'yoga'), 'practice' => __('Вопросы по практикам', 'yoga'));
?>
<div class="lk-questions-toolbar" data-active-question-tab="<?php echo esc_attr($active_tab); ?>">
	<div class="lk-questions-tabs" role="tablist" aria-label="<?php esc_attr_e('Категории вопросов', 'yoga'); ?>">
		<?php foreach ($labels as $key => $label): ?>
		<button type="button" class="lk-questions-tab<?php echo $key === $active_tab ? ' is-active' : ''; ?>" role="tab" id="questions-tab-<?php echo esc_attr($key); ?>" aria-controls="questions-panel-<?php echo esc_attr($key); ?>" aria-selected="<?php echo $key === $active_tab ? 'true' : 'false'; ?>" tabindex="<?php echo $key === $active_tab ? '0' : '-1'; ?>" data-question-tab="<?php echo esc_attr($key); ?>">
			<span><?php echo esc_html($label); ?></span>
			<span class="lk-questions-tab__count">
				<?php foreach (array('violet-fill', 'violet-ring', 'white-fill', 'white-ring') as $asset): ?>
				<img class="lk-questions-tab__badge lk-questions-tab__badge--<?php echo esc_attr($asset); ?>" src="<?php echo esc_url($asset_url . 'questions-count-' . $asset . '.svg'); ?>" alt="">
				<?php endforeach; ?>
				<span><?php echo esc_html((string) count($groups[$key])); ?></span>
			</span>
		</button>
		<?php endforeach; ?>
	</div>
	<label class="lk-questions-sort">
		<select data-question-sort aria-label="<?php esc_attr_e('Порядок вопросов', 'yoga'); ?>">
			<option value="newest"><?php esc_html_e('Сначала новые', 'yoga'); ?></option>
			<option value="oldest"><?php esc_html_e('Сначала старые', 'yoga'); ?></option>
		</select>
		<img class="lk-questions-sort__arrow" src="<?php echo esc_url($asset_url . 'questions-sort-arrow.svg'); ?>" alt="">
		<span class="lk-questions-sort__mobile"><img src="<?php echo esc_url($asset_url . 'questions-sort-mobile.svg'); ?>" alt=""></span>
	</label>
</div>
<?php foreach ($groups as $key => $questions): ?>
<div class="lk-questions-panel" role="tabpanel" id="questions-panel-<?php echo esc_attr($key); ?>" aria-labelledby="questions-tab-<?php echo esc_attr($key); ?>" data-question-panel="<?php echo esc_attr($key); ?>"<?php echo $key === $active_tab ? '' : ' hidden'; ?>>
	<?php if (!$questions): ?>
		<p class="no-questions"><?php echo esc_html($key === 'practice' ? __('У вас пока нет вопросов по практикам.', 'yoga') : __('У вас пока нет общих вопросов.', 'yoga')); ?></p>
	<?php endif; ?>
	<?php foreach ($questions as $index => $question) { display_question_item($question, $index >= 4); } ?>
	<?php if (count($questions) > 4): ?>
		<button type="button" class="btn show-more-questions"><span class="active"><?php esc_html_e('Показать еще', 'yoga'); ?></span><span><?php esc_html_e('Свернуть', 'yoga'); ?></span></button>
	<?php endif; ?>
</div>
<?php endforeach;
}
