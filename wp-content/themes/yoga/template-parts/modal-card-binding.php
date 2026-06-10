<?php
/**
 * Привязка карты в ЛК — Figma 2113:23344.
 * Данные карты вводятся на странице ЮKassa; поля в модалке — только UI перед редиректом.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!is_user_logged_in()) {
	return;
}

$img_uri = get_template_directory_uri() . '/assets/img';
?>
<div class="modal-addnewcard" id="ytr-modal-bind-card" aria-hidden="true">
	<button type="button" class="modal-close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</button>
	<div class="addnewcard-inner">
		<h3><?php esc_html_e('Привязка новой карты', 'yoga'); ?></h3>
		<p><?php esc_html_e('Для проверки спишем и вернём небольшую сумму', 'yoga'); ?></p>
		<form class="form" id="ytr-bind-card-form" action="#" method="post" novalidate>
			<div class="input-card-custom input-card-custom_big">
				<span class="input-card-custom__placeholder"><?php esc_html_e('Номер карты', 'yoga'); ?></span>
				<input
					type="text"
					class="input input_card"
					name="ytr_card_number"
					placeholder="<?php esc_attr_e('Номер карты', 'yoga'); ?>"
					autocomplete="off"
					inputmode="numeric"
					aria-label="<?php esc_attr_e('Номер карты', 'yoga'); ?>"
				>
			</div>
			<div class="form-row-split">
				<div class="input-card-custom">
					<span class="input-card-custom__placeholder"><?php esc_html_e('Действует до', 'yoga'); ?></span>
					<input
						type="text"
						class="input input_carddate"
						name="ytr_card_expiry"
						placeholder="<?php esc_attr_e('Действует до', 'yoga'); ?>"
						autocomplete="off"
						inputmode="numeric"
						aria-label="<?php esc_attr_e('Действует до', 'yoga'); ?>"
					>
				</div>
				<div class="input-card-custom">
					<span class="input-card-custom__placeholder"><?php esc_html_e('Код CSV', 'yoga'); ?></span>
					<input
						type="text"
						class="input input_cardcode"
						name="ytr_card_cvc"
						placeholder="<?php esc_attr_e('Код CSV', 'yoga'); ?>"
						autocomplete="off"
						inputmode="numeric"
						aria-label="<?php esc_attr_e('Код CSV', 'yoga'); ?>"
					>
				</div>
			</div>
			<button type="submit" id="ytr-bind-card-submit" class="ytr-bind-card-submit-native" tabindex="-1" aria-hidden="true"></button>
			<label for="ytr-bind-card-submit" class="btn ytr-bind-card-btn" id="ytr-bind-card-btn">
				<span><?php esc_html_e('Добавить карту', 'yoga'); ?></span>
			</label>
		</form>
	</div>
</div>
