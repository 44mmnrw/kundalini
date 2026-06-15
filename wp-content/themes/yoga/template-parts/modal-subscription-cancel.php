<?php
/**
 * ЛК: удаление карты и отмена автопродления.
 */

if (!defined('ABSPATH')) {
	exit;
}

$img_uri = get_template_directory_uri() . '/assets/img';
?>
<div class="modal modal-default modal-default_carddel" id="ytr-modal-remove-card" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm">
		<div class="delcomm__main">
			<h3><?php esc_html_e('Удалить карту?', 'yoga'); ?></h3>
			<p id="ytr-remove-card-label"><?php esc_html_e('Карта будет удалена из личного кабинета. Автопродление отключится.', 'yoga'); ?></p>
			<div class="delcomm-buttons">
				<button type="button" class="btn btn_white" id="ytr-remove-card-cancel">
					<span><?php esc_html_e('Оставить', 'yoga'); ?></span>
				</button>
				<button type="button" class="btn btn_dark" id="ytr-remove-card-confirm">
					<span><?php esc_html_e('Удалить', 'yoga'); ?></span>
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_cardsucces" id="ytr-modal-remove-card-success" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm">
		<div class="delcomm__succes">
			<b id="ytr-remove-card-success-text"><?php esc_html_e('Карта удалена', 'yoga'); ?></b>
		</div>
	</div>
</div>

<div class="modal modal-default modal-default_carddel" id="ytr-modal-unsubscribe" aria-hidden="true">
	<div class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
	</div>
	<div class="delcomm">
		<div class="delcomm__main">
			<h3><?php esc_html_e('Отменить автопродление', 'yoga'); ?></h3>
			<p>
				<?php esc_html_e('Списания прекратятся. Доступ к платным материалам сохранится до', 'yoga'); ?>
				<strong id="ytr-unsubscribe-end-date">—</strong>.
			</p>
			<p><?php esc_html_e('После этой даты тариф не продлится автоматически.', 'yoga'); ?></p>
			<div class="delcomm-buttons">
				<button type="button" class="btn btn_white" id="ytr-unsubscribe-keep">
					<span><?php esc_html_e('Оставить автопродление', 'yoga'); ?></span>
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
	<div class="delcomm">
		<div class="delcomm__succes">
			<b id="ytr-unsubscribe-success-text"><?php esc_html_e('Автопродление отключено', 'yoga'); ?></b>
			<p id="ytr-unsubscribe-success-hint" class="ytr-unsubscribe-success-hint">
				<?php esc_html_e('Следующее списание не будет выполнено. Доступ сохранится до конца оплаченного периода.', 'yoga'); ?>
			</p>
		</div>
	</div>
</div>
