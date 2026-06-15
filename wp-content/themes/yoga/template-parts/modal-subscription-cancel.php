<?php
/**
 * ЛК: удаление карты (3 шага) и отмена автопродления.
 */

if (!defined('ABSPATH')) {
	exit;
}

$img_uri     = get_template_directory_uri() . '/assets/img';
$sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');

$brand_icons = array(
	'mir'        => array(
		'id'      => 'lk-payment-mir',
		'viewBox' => '0 0 50 15',
	),
	'mastercard' => array(
		'id'      => 'lk-payment-mastercard',
		'viewBox' => '0 0 50 39',
	),
	'visa'       => array(
		'id'      => 'lk-payment-visa',
		'viewBox' => '0 0 50 16',
	),
);
?>
<div class="modal modal-default modal-default_card" id="ytr-modal-cancel-subscription" aria-hidden="true">
	<div class="modal-card">
		<div class="modal-card__main">
			<div class="card-info">
				<div class="card-info__text">
					<span id="ytr-modal-card-brand-name"><?php esc_html_e('Мир', 'yoga'); ?></span>
					<b id="ytr-modal-card-number">**0000</b>
				</div>
				<div class="card-info__img" id="ytr-modal-card-brand">
					<?php foreach ($brand_icons as $type => $icon) : ?>
						<svg
							class="card-info__brand card-info__brand--<?php echo esc_attr($type); ?>"
							data-brand="<?php echo esc_attr($type); ?>"
							viewBox="<?php echo esc_attr($icon['viewBox']); ?>"
							aria-hidden="true"
							focusable="false"
							hidden
						>
							<use href="<?php echo $sprite_href; ?>#<?php echo esc_attr($icon['id']); ?>" width="100%" height="100%"></use>
						</svg>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
	<div class="delcomm">
		<div class="delcomm__main">
			<div class="delcomm-buttons">
				<button type="button" class="btn btn_white" id="ytr-modal-cancel-subscription-keep">
					<span><?php esc_html_e('Отменить', 'yoga'); ?></span>
				</button>
				<button type="button" class="btn btn_dark" id="ytr-modal-cancel-subscription-next">
					<span><?php esc_html_e('Удалить карту', 'yoga'); ?></span>
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_carddel" id="ytr-modal-cancel-subscription-confirm" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm" id="ytr-modal-cancel-delcomm">
		<div class="delcomm__main">
			<h3><?php esc_html_e('Хотите удалить карту?', 'yoga'); ?></h3>
			<p><?php esc_html_e('Чтобы платить ей в будущем, придётся вводить данные вручную', 'yoga'); ?></p>
			<div class="delcomm-buttons">
				<button type="button" class="btn btn_white" id="ytr-modal-cancel-subscription-back">
					<span><?php esc_html_e('Оставить всё как есть', 'yoga'); ?></span>
				</button>
				<button type="button" class="btn btn_dark" id="ytr-modal-cancel-subscription-delete">
					<span><?php esc_html_e('Да, удалить', 'yoga'); ?></span>
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_cardsucces" id="ytr-modal-cancel-subscription-success" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm active">
		<div class="delcomm__succes">
			<b id="ytr-modal-cancel-subscription-success-text"><?php esc_html_e('Карта удалена', 'yoga'); ?></b>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_carddel" id="ytr-modal-unsubscribe" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm">
		<div class="delcomm__main">
			<h3><?php esc_html_e('Отмена подписки', 'yoga'); ?></h3>
			<p>
				<?php esc_html_e('Автопродление будет отключено. Доступ к платным материалам сохранится до', 'yoga'); ?>
				<strong id="ytr-unsubscribe-end-date">—</strong>.
			</p>
			<p><?php esc_html_e('После этой даты тариф не продлится автоматически.', 'yoga'); ?></p>
			<div class="delcomm-buttons">
				<button type="button" class="btn btn_white" id="ytr-unsubscribe-keep">
					<span><?php esc_html_e('Оставить подписку', 'yoga'); ?></span>
				</button>
				<button type="button" class="btn btn_dark" id="ytr-unsubscribe-confirm">
					<span><?php esc_html_e('Отменить автопродление', 'yoga'); ?></span>
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_cardsucces" id="ytr-modal-unsubscribe-success" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm active">
		<div class="delcomm__succes">
			<b id="ytr-unsubscribe-success-text"><?php esc_html_e('Автопродление отключено', 'yoga'); ?></b>
			<p id="ytr-unsubscribe-success-hint" class="ytr-unsubscribe-success-hint">
				<?php esc_html_e('Следующее списание не будет выполнено. Доступ сохранится до конца оплаченного периода.', 'yoga'); ?>
			</p>
		</div>
	</div>
</div>
