<?php
/**
 * Выезжающая панель навигации ЛК (мобильная / планшет).
 * Макет Figma sidebar_lk 2113:20086: разделы ЛК + «Выйти».
 * У каждого <svg> задан viewBox как у symbol + <use width/height="100%">,
 * чтобы иконки стабильно масштабировались по CSS.
 */
if (!is_user_logged_in()) {
	return;
}
?><div class="modal-mobile-menu-lk">
	<div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="#lk-modal-close" width="100%" height="100%"></use>
		</svg>
	</div>
	<div class="mobile-menu-inner">
		<div class="mobile-menu">
			<div class="mobile-menu__slide mobile-menu__slide_main active">
				<div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
					<nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
						<div class="sidebar-menu__item active" data-target="1">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-user" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои данные', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="2">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-history" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('История практик', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="7">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 16" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-lotus" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои садханы', 'yoga'); ?></span>
							<span class="sidebar-menu__badge" aria-label="<?php esc_attr_e('6 садхан', 'yoga'); ?>">6</span>
						</div>
						<div class="sidebar-menu__item" data-target="3">
							<div class="sidebar-menu__item-icon sidebar-menu__item-icon--heart">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 17.4 15.4852" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-heart" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Избранное', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="4">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="-0.6 -0.6 18.2 18.2" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-smile" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="5">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-question" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои вопросы', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="8">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 22 22" aria-hidden="true" focusable="false">
									<use href="#notification-bell-icon" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Уведомления', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item" data-target="6">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="#lk-sidebar-settings" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
						</div>
					</nav>
				</div>

				<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<svg class="sidebar-exit__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<use href="#lk-sidebar-logout" width="100%" height="100%"></use>
						</svg>
					</div>
					<span class="sidebar-menu__label"><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
