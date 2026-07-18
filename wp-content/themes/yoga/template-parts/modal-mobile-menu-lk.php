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
$mobile_lk_target = function_exists('yoga_get_initial_lk_target') ? yoga_get_initial_lk_target() : '1';
$mobile_lk_tariff = function_exists('get_current_user_tariff') ? get_current_user_tariff() : false;
$mobile_lk_tariff_label = is_array($mobile_lk_tariff) && !empty($mobile_lk_tariff['product_name']) ? (string) $mobile_lk_tariff['product_name'] : __('Подписка не активна', 'yoga');
$mobile_lk_urls = function_exists('yoga_lk_sidebar_secondary_nav_urls') ? yoga_lk_sidebar_secondary_nav_urls() : array_fill_keys(array('library', 'tariffs', 'about', 'blog', 'contacts', 'faq'), home_url('/'));
?><div class="modal-mobile-menu-lk">
	<div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 17 17" width="17" height="17" aria-hidden="true" focusable="false">
			<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#email-confirmation-close'); ?>" width="100%" height="100%"></use>
		</svg>
	</div>
	<div class="mobile-menu-inner">
		<div class="lk-mobile-menu-head"><a class="lk-mobile-menu-rate" href="<?php echo esc_url($mobile_lk_urls['tariffs']); ?>"><svg aria-hidden="true"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#personal-status-crown'); ?>"></use></svg><span><?php echo esc_html($mobile_lk_tariff_label); ?></span></a></div>
		<div class="mobile-menu">
			<div class="mobile-menu__slide mobile-menu__slide_main active">
				<div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
					<nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-user'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои данные', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-history'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('История практик', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 21 20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-lotus'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои садханы', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '3' ? ' active' : ''; ?>" data-target="3">
						<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 18 16" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-heart'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Избранное', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 17 17" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-smile'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-question'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои вопросы', 'yoga'); ?></span>
							<?php
							$mobile_unread_question_answers_count = function_exists('yoga_get_unread_question_answer_notifications')
								? count(yoga_get_unread_question_answer_notifications((int) get_current_user_id()))
								: 0;
							if ($mobile_unread_question_answers_count > 0) :
							?>
								<span class="sidebar-menu__count" aria-label="<?php echo esc_attr(sprintf(_n('%d непрочитанный ответ', '%d непрочитанных ответов', $mobile_unread_question_answers_count, 'yoga'), $mobile_unread_question_answers_count)); ?>"><?php echo esc_html((string) $mobile_unread_question_answers_count); ?></span>
							<?php endif; ?>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '8' ? ' active' : ''; ?>" data-target="8">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 21 23" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#notification-bell-icon'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Уведомления', 'yoga'); ?></span>
							<?php
							$mobile_unread_notifications_count = function_exists('yoga_get_unread_user_notifications')
								? count(yoga_get_unread_user_notifications((int) get_current_user_id()))
								: 0;
							if ($mobile_unread_notifications_count > 0) :
							?>
								<span class="sidebar-menu__count" aria-label="<?php echo esc_attr(sprintf(_n('%d непрочитанное уведомление', '%d непрочитанных уведомлений', $mobile_unread_notifications_count, 'yoga'), $mobile_unread_notifications_count)); ?>"><?php echo esc_html((string) $mobile_unread_notifications_count); ?></span>
							<?php endif; ?>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '6' ? ' active' : ''; ?>" data-target="6">
							<div class="sidebar-menu__item-icon">
								<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-settings'); ?>" width="100%" height="100%"></use>
								</svg>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
						</div>
					</nav>
				</div>

				<hr class="sidebar-menu-sep" aria-hidden="true">
				<nav class="sidebar-menu-secondary" aria-label="<?php esc_attr_e('Разделы сайта', 'yoga'); ?>">
					<a class="sidebar-menu-secondary__link sidebar-menu-secondary__link--library" href="<?php echo esc_url($mobile_lk_urls['library']); ?>"><span><?php esc_html_e('Библиотека практик', 'yoga'); ?></span><svg class="sidebar-menu-secondary__chevron" viewBox="0 0 8 16" aria-hidden="true"><path d="M1 1l6 7-6 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_urls['tariffs']); ?>"><span><?php esc_html_e('Тарифы и подписка', 'yoga'); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_urls['about']); ?>"><span><?php esc_html_e('О нас', 'yoga'); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_urls['blog']); ?>"><span><?php esc_html_e('Блог', 'yoga'); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_urls['contacts']); ?>"><span><?php esc_html_e('Контакты', 'yoga'); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_urls['faq']); ?>"><span><?php esc_html_e('FAQ', 'yoga'); ?></span></a>
				</nav>
				<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<svg class="sidebar-exit__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-logout'); ?>" width="100%" height="100%"></use>
						</svg>
					</div>
					<span class="sidebar-menu__label"><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
