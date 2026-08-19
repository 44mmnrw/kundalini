<?php
/**
 * Practice sadhana duration modal.
 *
 * @package Yoga
 */

$practice_id = (int) get_queried_object_id();
$sprite_url  = get_template_directory_uri() . '/assets/svg/sprite.svg';
$minimum_days = function_exists('kundalini_sadhanas_minimum_target_days')
	? kundalini_sadhanas_minimum_target_days()
	: 7;
$preset_options = array(
	40  => __('Изменить привычку', 'yoga'),
	90  => __('Закрепить навык', 'yoga'),
	120 => __('Стать практиком', 'yoga'),
);
$available_presets = array_filter(
	$preset_options,
	static function ($days) use ($minimum_days): bool {
		return (int) $days >= $minimum_days;
	},
	ARRAY_FILTER_USE_KEY
);
$default_days = $available_presets !== array() ? (string) array_key_first($available_presets) : 'custom';
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

	<form class="yoga-sadhana-modal__form<?php echo $default_days === 'custom' ? ' yoga-sadhana-modal__form--custom' : ''; ?>" data-practice-id="<?php echo esc_attr((string) $practice_id); ?>" data-minimum-days="<?php echo esc_attr((string) $minimum_days); ?>" data-default-days="<?php echo esc_attr($default_days); ?>">
		<div class="yoga-sadhana-modal__header">
			<h2 id="yoga-sadhana-modal-title"><?php esc_html_e('На сколько дней возьмёте крийю в садхану?', 'yoga'); ?></h2>
			<p id="yoga-sadhana-modal-description"><?php esc_html_e('Выберите длительность. Если пропустите день - садхана начнётся сначала.', 'yoga'); ?></p>
		</div>

		<fieldset class="yoga-sadhana-modal__options">
			<legend class="screen-reader-text"><?php esc_html_e('Длительность садханы', 'yoga'); ?></legend>
			<?php foreach ($available_presets as $days => $description) : ?>
				<label class="yoga-sadhana-option">
					<input type="radio" name="sadhana_days" value="<?php echo esc_attr((string) $days); ?>" <?php checked((string) $days, $default_days); ?>>
					<strong><?php echo esc_html(sprintf(__('%d дней', 'yoga'), $days)); ?></strong>
					<span><?php echo esc_html($description); ?></span>
				</label>
			<?php endforeach; ?>
			<div class="yoga-sadhana-option yoga-sadhana-option_custom">
				<input id="yoga-sadhana-days-custom" type="radio" name="sadhana_days" value="custom" <?php checked('custom', $default_days); ?>>
				<label class="yoga-sadhana-option__default" for="yoga-sadhana-days-custom">
					<strong><?php esc_html_e('Свой вариант', 'yoga'); ?></strong>
					<span><?php echo esc_html(sprintf(__('От %1$d до %2$d дней', 'yoga'), $minimum_days, 1000)); ?></span>
				</label>
				<span class="yoga-sadhana-option__custom" aria-hidden="<?php echo $default_days === 'custom' ? 'false' : 'true'; ?>">
					<input class="yoga-sadhana-option__input" type="number" name="custom_sadhana_days" value="<?php echo esc_attr((string) $minimum_days); ?>" min="<?php echo esc_attr((string) $minimum_days); ?>" max="1000" inputmode="numeric" <?php echo $default_days === 'custom' ? 'required' : 'disabled'; ?> aria-label="<?php esc_attr_e('Количество дней садханы', 'yoga'); ?>">
					<button class="yoga-sadhana-option__clear" type="button" aria-label="<?php esc_attr_e('Очистить поле', 'yoga'); ?>">
						<svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url($sprite_url . '#lk-modal-close'); ?>"></use></svg>
					</button>
				</span>
				<span class="yoga-sadhana-option__hint"><?php echo esc_html(sprintf(__('От %1$d до %2$d дней', 'yoga'), $minimum_days, 1000)); ?></span>
			</div>
		</fieldset>

		<div class="yoga-sadhana-modal__actions">
			<button class="yoga-sadhana-modal__cancel" type="button"><?php esc_html_e('Отмена', 'yoga'); ?></button>
			<button class="yoga-sadhana-modal__submit" type="submit"><?php esc_html_e('Начать садхану', 'yoga'); ?></button>
		</div>
	</form>
</div>
