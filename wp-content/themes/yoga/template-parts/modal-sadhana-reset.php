<?php
/**
 * Sadhana progress reset confirmation modal.
 *
 * @package Yoga
 */

$reset_practice_id = is_singular('practice') ? (int) get_queried_object_id() : 0;
$reset_sprite_url  = get_template_directory_uri() . '/assets/svg/sprite.svg';
?>
<div
	class="modal yoga-sadhana-reset-modal"
	id="yoga-sadhana-reset-modal"
	role="dialog"
	aria-modal="true"
	aria-labelledby="yoga-sadhana-reset-title"
	aria-describedby="yoga-sadhana-reset-description"
	aria-hidden="true"
>
	<button class="modal-close yoga-sadhana-reset-modal__close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
			<use href="<?php echo esc_url($reset_sprite_url . '#lk-modal-close'); ?>"></use>
		</svg>
	</button>

	<div class="yoga-sadhana-reset-modal__content" data-practice-id="<?php echo esc_attr((string) $reset_practice_id); ?>">
		<div class="yoga-sadhana-reset-modal__copy">
			<h2 id="yoga-sadhana-reset-title"><?php esc_html_e('Хотите сбросить прогресс?', 'yoga'); ?></h2>
			<p id="yoga-sadhana-reset-description"><?php esc_html_e('Прогресс будет потерян, но вы сможете всегда начать сначала.', 'yoga'); ?></p>
		</div>
		<div class="yoga-sadhana-reset-modal__actions">
			<button class="yoga-sadhana-reset-modal__cancel" type="button"><?php esc_html_e('Отмена', 'yoga'); ?></button>
			<button class="yoga-sadhana-reset-modal__confirm" type="button"><?php esc_html_e('Да, сбросить', 'yoga'); ?></button>
		</div>
	</div>
</div>
