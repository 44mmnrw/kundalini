<?php
/**
 * Шаблон комментариев: для записей блога — тот же AJAX-блок, что у практик; для прочих типов — стандартная разметка WordPress.
 *
 * @package yoga
 */

if (post_password_required()) {
	return;
}

$post_id = (int) get_the_ID();

if (get_post_type($post_id) === 'post') :
	?>
	<div id="comments" class="praktika-comments post-comments-yoga">
		<h3 class="post-comments-yoga__title"><?php esc_html_e('Комментарии', 'yoga'); ?></h3>

		<div class="comment-form-main">
			<form id="custom-comment-form" class="comment-form">
				<textarea name="comment" class="input textarea-resize" placeholder="<?php esc_attr_e('Оставьте комментарий', 'yoga'); ?>" rows="1" required></textarea>
				<button type="submit" class="btn"><?php esc_html_e('ОТПРАВИТЬ', 'yoga'); ?></button>

				<input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>">
				<input type="hidden" name="action" value="submit_custom_comment">
				<?php wp_nonce_field('yoga_ajax_nonce', 'comment_security'); ?>
			</form>
		</div>

		<div class="comments-items">
			<?php yoga_render_threaded_ajax_comments_list($post_id); ?>
		</div>
	</div>
	<?php
	return;
endif;
?>

<div id="comments" class="comments-area">
	<?php if (have_comments()) : ?>
		<h2 class="comments-title"><?php comments_number(); ?></h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 42,
			));
			?>
		</ol>

		<?php the_comments_navigation(); ?>

	<?php endif; ?>

	<?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
		<p class="no-comments"><?php esc_html_e('Комментарии закрыты.', 'yoga'); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>
</div>
