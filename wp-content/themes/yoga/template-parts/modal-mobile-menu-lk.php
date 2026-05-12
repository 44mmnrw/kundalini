<?php
/**
 * Выезжающая панель навигации ЛК (мобильная / планшет).
 * Макет Figma sidebar_lk 620:12651: раздел «ЛК» + раздел с внешними ссылками + «Выйти».
 * У каждого <svg> задан viewBox как у symbol + <use width/height="100%"> — иначе внешний спрайт не масштабируется под CSS.
 */
if (!is_user_logged_in()) {
	return;
}

$theme_uri = get_template_directory_uri();
$sprite_href = esc_url($theme_uri . '/assets/svg/sprite.svg');
$lk_secondary = function_exists('yoga_lk_sidebar_secondary_nav_urls') ? yoga_lk_sidebar_secondary_nav_urls() : array(
	'library' => home_url('/'),
	'tariffs' => home_url('/'),
	'about' => home_url('/'),
	'blog' => home_url('/'),
	'contacts' => home_url('/'),
	'faq' => home_url('/'),
);

$tariff_name = '';
if (function_exists('get_current_user_tariff')) {
	$tariff = get_current_user_tariff();
	if (is_array($tariff) && !empty($tariff['product_name'])) {
		$tariff_name = (string) $tariff['product_name'];
	}
}
?>
<div class="modal-mobile-menu-lk">
	<div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="<?php echo $sprite_href; ?>#lk-modal-close" width="100%" height="100%"></use>
		</svg>
	</div>
	<div class="mobile-menu-inner">
		<div class="personal-status<?php echo $tariff_name === '' ? ' personal-status_empty' : ''; ?>">
			<svg class="personal-status__img" viewBox="0 0 16 15.9326" aria-hidden="true" width="24" height="24">
				<use href="<?php echo $sprite_href; ?>#personal-status-crown" width="100%" height="100%"></use>
			</svg>
			<?php if ($tariff_name !== '') : ?>
				<span><?php echo esc_html($tariff_name); ?></span>
			<?php endif; ?>
		</div>
		<div class="mobile-menu">
			<div class="mobile-menu__slide mobile-menu__slide_main active">
				<div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
					<nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
						<div class="sidebar-menu__item active" data-target="1">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-user" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('Мои данные', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="2">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-history" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('История практик', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="3">
							<div class="sidebar-menu__item-icon sidebar-menu__item-icon--heart">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 17.4 15.4852" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-heart" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('Избранное', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="4">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="-0.6 -0.6 18.2 18.2" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-smile" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="5">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-question" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('Мои вопросы', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="6">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo $sprite_href; ?>#lk-sidebar-settings" width="100%" height="100%"></use>
								</svg>
							</div>
							<span><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
						</div>
					</nav>
				</div>

				<hr class="sidebar-menu-sep" aria-hidden="true">

				<div class="sidebar-menu-lk-group sidebar-menu-lk-group--secondary">
					<nav class="sidebar-menu-secondary" aria-label="<?php esc_attr_e('Навигация по сайту', 'yoga'); ?>">
						<a class="sidebar-menu-secondary__link sidebar-menu-secondary__link--library" href="<?php echo esc_url($lk_secondary['library']); ?>">
							<span><?php esc_html_e('Библиотека практик', 'yoga'); ?></span>
							<svg class="sidebar-menu-secondary__chevron" viewBox="0 0 9 16" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-library-chevron" width="100%" height="100%"></use>
							</svg>
						</a>
						<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['tariffs']); ?>">
							<span><?php esc_html_e('Тарифы и подписка', 'yoga'); ?></span>
						</a>
						<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['about']); ?>">
							<span><?php esc_html_e('О нас', 'yoga'); ?></span>
						</a>
						<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['blog']); ?>">
							<span><?php esc_html_e('Блог', 'yoga'); ?></span>
						</a>
						<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['contacts']); ?>">
							<span><?php esc_html_e('Контакты', 'yoga'); ?></span>
						</a>
						<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['faq']); ?>">
							<span><?php esc_html_e('FAQ', 'yoga'); ?></span>
						</a>
					</nav>
				</div>

				<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<svg class="sidebar-exit__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<use href="<?php echo $sprite_href; ?>#lk-sidebar-logout" width="100%" height="100%"></use>
						</svg>
					</div>
					<span><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
