<?php
/**
 * Practice sadhana duration modal.
 *
 * @package Yoga
 */

$practice_id = (int) get_queried_object_id();
$sprite_url  = get_template_directory_uri() . '/assets/svg/sprite.svg';
?>
<div
	class="modal yoga-sadhana-modal"
	id="yoga-sadhana-modal"
	role="dialog"
	aria-modal="true"
	aria-labelledby="yoga-sadhana-modal-title"
	aria-describedby="yoga-sadhana-modal-description"
	aria-hidden="true"
>
	<button class="modal-close yoga-sadhana-modal__close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
			<use href="<?php echo esc_url($sprite_url . '#lk-modal-close'); ?>"></use>
		</svg>
	</button>

	<form class="yoga-sadhana-modal__form" data-practice-id="<?php echo esc_attr((string) $practice_id); ?>">
		<div class="yoga-sadhana-modal__header">
			<h2 id="yoga-sadhana-modal-title"><?php esc_html_e('На сколько дней возьмёте крийю в садхану?', 'yoga'); ?></h2>
			<p id="yoga-sadhana-modal-description"><?php esc_html_e('Выберите длительность. Если пропустите день - садхана начнётся сначала.', 'yoga'); ?></p>
		</div>

		<fieldset class="yoga-sadhana-modal__options">
			<legend class="screen-reader-text"><?php esc_html_e('Длительность садханы', 'yoga'); ?></legend>
			<label class="yoga-sadhana-option">
				<input type="radio" name="sadhana_days" value="40" checked>
				<strong><?php esc_html_e('40 дней', 'yoga'); ?></strong>
				<span><?php esc_html_e('Изменить привычку', 'yoga'); ?></span>
			</label>
			<label class="yoga-sadhana-option">
				<input type="radio" name="sadhana_days" value="90">
				<strong><?php esc_html_e('90 дней', 'yoga'); ?></strong>
				<span><?php esc_html_e('Закрепить навык', 'yoga'); ?></span>
			</label>
			<label class="yoga-sadhana-option">
				<input type="radio" name="sadhana_days" value="120">
				<strong><?php esc_html_e('120 дней', 'yoga'); ?></strong>
				<span><?php esc_html_e('Стать практиком', 'yoga'); ?></span>
			</label>
			<div class="yoga-sadhana-option yoga-sadhana-option_custom">
				<input id="yoga-sadhana-days-custom" type="radio" name="sadhana_days" value="custom">
				<label class="yoga-sadhana-option__default" for="yoga-sadhana-days-custom">
					<strong><?php esc_html_e('Свой вариант', 'yoga'); ?></strong>
					<span><?php esc_html_e('От 7 до 1000 дней', 'yoga'); ?></span>
				</label>
				<span class="yoga-sadhana-option__custom" aria-hidden="true">
					<input class="yoga-sadhana-option__input" type="number" name="custom_sadhana_days" value="7" min="7" max="1000" inputmode="numeric" disabled aria-label="<?php esc_attr_e('Количество дней садханы', 'yoga'); ?>">
					<button class="yoga-sadhana-option__clear" type="button" aria-label="<?php esc_attr_e('Очистить поле', 'yoga'); ?>">
						<svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url($sprite_url . '#lk-modal-close'); ?>"></use></svg>
					</button>
				</span>
				<span class="yoga-sadhana-option__hint"><?php esc_html_e('От 7 до 1000 дней', 'yoga'); ?></span>
			</div>
		</fieldset>

		<div class="yoga-sadhana-modal__actions">
			<button class="yoga-sadhana-modal__cancel" type="button"><?php esc_html_e('Отмена', 'yoga'); ?></button>
			<button class="yoga-sadhana-modal__submit" type="submit"><?php esc_html_e('Начать садхану', 'yoga'); ?></button>
		</div>
	</form>
</div>
