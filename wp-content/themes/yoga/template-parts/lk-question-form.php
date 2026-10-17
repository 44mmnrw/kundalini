<?php
/** Форма вопроса из личного кабинета: Figma 3278:31346. */
if (!defined('ABSPATH') || !is_user_logged_in()) { return; }
?>
<div class="lk-questions-form">
	<form id="question-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" aria-busy="false">
		<input type="hidden" name="action" value="submit_question">
		<?php wp_nonce_field('submit_question', 'question_nonce'); ?>
		<textarea class="input" name="question_text" rows="1" required placeholder="<?php esc_attr_e('Задайте ваш вопрос', 'yoga'); ?>" aria-label="<?php esc_attr_e('Ваш вопрос', 'yoga'); ?>"></textarea>
		<button type="submit" class="btn"><?php esc_html_e('Задать вопрос', 'yoga'); ?></button>
	</form>
</div>
