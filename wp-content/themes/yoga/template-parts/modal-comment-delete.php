<?php
/**
 * Переиспользуемый шаблонный блок: modal comment delete.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

$theme_uri = get_template_directory_uri();
?>
<div class="modal modal-default yoga-comment-delete-modal" id="yoga-comment-delete-confirm" role="dialog" aria-modal="true" aria-labelledby="yoga-comment-delete-title" aria-hidden="true">
	<button type="button" class="modal-close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg>
	</button>
	<div class="yoga-comment-delete-modal__content">
		<div class="yoga-comment-delete-modal__copy">
			<h3 id="yoga-comment-delete-title"><?php esc_html_e('Удалить комментарий', 'yoga'); ?></h3>
			<p><?php esc_html_e('Вы уверены, что хотите удалить этот комментарий? Это действие не может быть отменено,', 'yoga'); ?></p>
		</div>
		<div class="yoga-comment-delete-modal__actions">
			<button type="button" class="yoga-comment-delete-modal__button yoga-comment-delete-modal__button_cancel"><?php esc_html_e('Нет, отменить', 'yoga'); ?></button>
			<button type="button" class="yoga-comment-delete-modal__button yoga-comment-delete-modal__button_confirm"><?php esc_html_e('Да, удалить', 'yoga'); ?></button>
		</div>
	</div>
</div>

<div class="modal modal-default yoga-comment-delete-modal yoga-comment-delete-modal_success" id="yoga-comment-delete-success" role="dialog" aria-modal="true" aria-labelledby="yoga-comment-delete-success-title" aria-hidden="true">
	<button type="button" class="modal-close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg>
	</button>
	<div class="yoga-comment-delete-modal__success-content">
		<img class="yoga-comment-delete-modal__success-icon" src="<?php echo esc_url($theme_uri . '/assets/svg/comment-delete-success.svg'); ?>" width="77" height="77" alt="">
		<h3 id="yoga-comment-delete-success-title"><?php esc_html_e('Комментарий удалён', 'yoga'); ?></h3>
	</div>
</div>
