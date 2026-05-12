<?php
/**
 * Выезжающая панель навигации ЛК (мобильная / планшет).
 * Иконки — sprite.svg (макет Figma sidebar_lk 620:12651).
 */
if (!is_user_logged_in()) {
	return;
}

$theme_uri = get_template_directory_uri();
$sprite_href = esc_url($theme_uri . '/assets/svg/sprite.svg');

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
		<svg class="modal-close__icon" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="<?php echo $sprite_href; ?>#lk-modal-close"></use>
		</svg>
	</div>
	<div class="mobile-menu-inner">
		<div class="personal-status<?php echo $tariff_name === '' ? ' personal-status_empty' : ''; ?>">
			<svg class="personal-status__img" aria-hidden="true" width="24" height="24">
				<use href="<?php echo $sprite_href; ?>#personal-status-crown"></use>
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
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-user"></use>
							</svg>
						</div>
						<span><?php esc_html_e('Мои данные', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="2">
						<div class="sidebar-menu__item-icon">
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-history"></use>
							</svg>
						</div>
						<span><?php esc_html_e('История практик', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="3">
						<div class="sidebar-menu__item-icon">
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-heart"></use>
							</svg>
						</div>
						<span><?php esc_html_e('Избранное', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="4">
						<div class="sidebar-menu__item-icon">
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-smile"></use>
							</svg>
						</div>
						<span><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="5">
						<div class="sidebar-menu__item-icon">
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-question"></use>
							</svg>
						</div>
						<span><?php esc_html_e('Мои вопросы', 'yoga'); ?></span>
					</div>
					<div class="sidebar-menu__item" data-target="6">
						<div class="sidebar-menu__item-icon">
							<svg class="sidebar-menu__item-svg" width="20" height="20" aria-hidden="true" focusable="false">
								<use href="<?php echo $sprite_href; ?>#lk-sidebar-settings"></use>
							</svg>
						</div>
						<span><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
					</div>
				</nav>
				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<svg class="sidebar-exit__svg" width="20" height="20" aria-hidden="true" focusable="false">
							<use href="<?php echo $sprite_href; ?>#lk-sidebar-logout"></use>
						</svg>
					</div>
					<span><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
