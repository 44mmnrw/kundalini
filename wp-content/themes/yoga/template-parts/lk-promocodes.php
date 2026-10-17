<?php
/** Кабинет: персональные промокоды. Figma 2894:26918, 2898:27532. */
$coupons = yoga_get_account_coupons((int) ($args['user_id'] ?? get_current_user_id()));
$active_count = count(array_filter($coupons, static function ($item) { return $item['active']; }));
?>
<div class="lk-community lk-promocodes">
	<header class="lk-community__head"><h1>Мои промокоды</h1><p>Здесь хранятся все полученные вами скидочные промокоды. Применяйте их при оплате подписки.</p></header>
	<?php if (!$coupons) : ?>
	<div class="lk-promo-steps">
		<article><span>1</span><div><h2>Поделитесь мнением о платформе</h2><p>Опишите свои впечатления от нашего сервиса и обязательно прикрепите своё фото к отзыву</p></div></article>
		<article><span>2</span><div><h2>Заберите скидку 30%</h2><p>Промокод придёт на эл.почту после модерации отзыва и появится на этой странице, если вы сделали всё правильно</p></div></article>
	</div>
	<section class="lk-promo-empty">
		<h2 class="lk-community__section-title"><?php yoga_lk_section_icon('promo-icon'); ?>Полученные промокоды (0)</h2>
		<p>У вас пока нет промокодов. Получите ваш персональный <strong>промокод на скидку 30% от стоимости тарифа на 3 месяца</strong> прямо сейчас! Для этого достаточно оставить отзыв о нашем сервисе .</p>
		<a href="<?php echo esc_url(yoga_get_lk_section_url('reviews')); ?>" class="lk-promo-empty__button"><span>Получить промокод</span><span aria-hidden="true"><svg viewBox="0 0 20 20"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use></svg></span></a>
	</section>
	<?php else : ?>
	<div class="lk-promo-tabs" role="tablist" aria-label="Промокоды">
		<button type="button" role="tab" id="lk-promo-active-tab" aria-controls="lk-promo-active" aria-selected="true" data-coupon-tab="active"><?php yoga_lk_section_icon('promo-active'); ?>Активные <span><?php echo $active_count; ?></span></button>
		<button type="button" role="tab" id="lk-promo-inactive-tab" aria-controls="lk-promo-inactive" aria-selected="false" tabindex="-1" data-coupon-tab="inactive"><?php yoga_lk_section_icon('promo-clock'); ?>Неактивные <span><?php echo count($coupons) - $active_count; ?></span></button>
	</div>
	<?php foreach (array('active', 'inactive') as $state) : ?>
	<div class="lk-promo-grid" id="lk-promo-<?php echo $state; ?>" role="tabpanel" aria-labelledby="lk-promo-<?php echo $state; ?>-tab"<?php echo $state === 'inactive' ? ' hidden' : ''; ?>>
		<?php foreach ($coupons as $item) : if ($item['active'] !== ($state === 'active')) { continue; } $coupon = $item['coupon']; $expiry = $coupon->get_date_expires(); ?>
		<article class="lk-promo-card">
			<header><strong>-<?php echo esc_html(wc_format_localized_decimal($coupon->get_amount()) . ($coupon->get_discount_type() === 'percent' ? '%' : ' ' . get_woocommerce_currency_symbol())); ?></strong><span><?php echo $item['active'] ? 'Активен' : 'Неактивен'; ?></span></header>
			<h2><?php echo esc_html($coupon->get_meta('_yoga_lk_title') ?: 'Персональная скидка'); ?></h2>
			<?php if ($coupon->get_description()) : ?><p class="lk-promo-card__description"><?php echo esc_html($coupon->get_description()); ?></p><?php endif; ?>
			<p class="lk-promo-card__expiry"><?php yoga_lk_section_icon('promo-clock'); ?>Срок действия: <?php echo $expiry ? 'До ' . esc_html($expiry->date_i18n('d.m.Y')) : 'Без ограничения'; ?></p>
			<div class="lk-promo-card__code"><code><?php echo esc_html($coupon->get_code()); ?></code><button type="button" data-copy-coupon="<?php echo esc_attr($coupon->get_code()); ?>"><?php yoga_lk_section_icon('promo-copy'); ?><span>Скопировать</span></button></div>
		</article>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
	<p class="lk-promo-copy-status" role="status" hidden></p>
	<?php endif; ?>
</div>
