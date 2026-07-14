<?php

if (!defined('ABSPATH')) {
	exit;
}

function display_question_item(WP_Post $question, bool $hidden = false): void {
	$question_id = $question->ID;
	$answers = yoga_get_question_answers($question_id);
	
	$status_class = !empty($answers) ? '' : 'lk-questions-item_new';
	$hidden_class = $hidden ? 'hidden' : '';
?>
    <div class="lk-questions-item <?php echo $status_class . ' ' . $hidden_class; ?>">
        <div class="lk-question">
            <div class="lk-question__time">
                <time><?php echo get_the_date('d.m.Y', $question_id); ?></time>
                <time><?php echo get_the_time('H:i', $question_id); ?></time>
		</div>
            <div class="lk-question__text">
                <p><?php echo esc_html($question->post_content); ?></p>
		</div>
		<?php if (empty($answers)): ?>
		<span class="lk-question__status">Ожидает ответа</span>
		<?php endif; ?>
	</div>

	<?php foreach ($answers as $answer): ?>
	<?php
		$answer_content = (string) ($answer['content'] ?? '');
		$answer_date = (string) ($answer['created_at'] ?? '');
		$admin_id = (int) ($answer['admin_id'] ?? 0);
		$admin_name = $admin_id > 0 ? (string) get_the_author_meta('display_name', $admin_id) : __('Администратор', 'yoga');
		$answer_timestamp = $answer_date !== '' ? strtotime($answer_date) : false;
	?>
	<div class="lk-question lk-question_answer">
		<div class="lk-question__time">
			<b>Ответ <?php echo esc_html($admin_name); ?></b>
			<?php if ($answer_timestamp): ?>
				<time datetime="<?php echo esc_attr(wp_date('c', $answer_timestamp)); ?>"><?php echo esc_html(wp_date('d.m.Y', $answer_timestamp)); ?></time>
				<time><?php echo esc_html(wp_date('H:i', $answer_timestamp)); ?></time>
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

function yoga_render_user_questions_list(int $user_id): void {
	$questions = get_user_questions($user_id);
	if (empty($questions)) {
		echo '<p class="no-questions">' . esc_html__('У вас пока нет заданных вопросов.', 'yoga') . '</p>';
		return;
	}

	foreach ($questions as $index => $question) {
		display_question_item($question, $index >= 4);
	}

	if (count($questions) > 4) {
		echo '<div class="btn show-more-questions">';
		echo '<span class="active">' . esc_html__('Показать еще', 'yoga') . '</span>';
		echo '<span>' . esc_html__('Свернуть', 'yoga') . '</span>';
		echo '</div>';
	}
}

