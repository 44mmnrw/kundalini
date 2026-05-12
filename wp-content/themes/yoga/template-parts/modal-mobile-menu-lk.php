<?php
/**
 * Выезжающая панель навигации ЛК (мобильная / планшет).
 */
if (!is_user_logged_in()) {
	return;
}

$theme_uri = get_template_directory_uri();

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
		<img src="<?php echo esc_url($theme_uri . '/assets/img/modal-close-img.png'); ?>" alt="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
	</div>
	<div class="mobile-menu-inner">
		<div class="personal-status<?php echo $tariff_name === '' ? ' personal-status_empty' : ''; ?>">
			<svg class="personal-status__img" aria-hidden="true">
				<use href="<?php echo esc_url($theme_uri . '/assets/svg/sprite.svg#personal-status-crown'); ?>"></use>
			</svg>
			<?php if ($tariff_name !== '') : ?>
				<span><?php echo esc_html($tariff_name); ?></span>
			<?php endif; ?>
		</div>
		<div class="mobile-menu">
			<div class="mobile-menu__slide mobile-menu__slide_main active">
				<nav class="sidebar-menu">
					<div class="sidebar-menu__item active" data-target="1">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_01.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_01-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('Мои данные', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="2">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_02.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_02-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('История практик', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="3">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_03.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_03-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('Избранное', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="4">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_04.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_04-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="5">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_05.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_05-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('Мои вопросы', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="6">
						<div class="sidebar-menu__item-icon">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_06.png'); ?>" alt="" class="active">
							<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-menu-icon_06-active.png'); ?>" alt="">
						</div>
						<span><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
					</div>
				</nav>
				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<img src="<?php echo esc_url($theme_uri . '/assets/img/sidebar-exit.png'); ?>" alt="" class="active">
					</div>
					<span><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
