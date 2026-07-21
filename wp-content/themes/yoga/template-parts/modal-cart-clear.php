<?php
/**
 * Переиспользуемый шаблонный блок: modal cart clear.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="modal modal-default modal-default_cart-clear" id="yoga-cart-clear-modal" role="dialog" aria-modal="true" aria-labelledby="yoga-cart-clear-title" aria-hidden="true">
	<button type="button" class="modal-close yoga-cart-clear__close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg>
	</button>
	<div class="yoga-cart-clear__content">
		<h3 id="yoga-cart-clear-title"><?php esc_html_e('Хотите очистить корзину?', 'yoga'); ?></h3>
		<div class="yoga-cart-clear__actions">
			<button type="button" class="yoga-cart-clear__button yoga-cart-clear__button_cancel"><?php esc_html_e('Отмена', 'yoga'); ?></button>
			<button type="button" class="yoga-cart-clear__button yoga-cart-clear__button_confirm"><?php esc_html_e('Да, очистить', 'yoga'); ?></button>
		</div>
	</div>
</div>
