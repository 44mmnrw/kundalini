<?php
/**
 * Переиспользуемый шаблонный блок: modal mobile menu lk.
 *
 * @package Yoga
 */
if (!is_user_logged_in()) {
	return;
}
$mobile_lk_target = function_exists('yoga_get_initial_lk_target') ? yoga_get_initial_lk_target() : '1';
$mobile_lk_tariff = function_exists('get_current_user_tariff') ? get_current_user_tariff() : false;
$mobile_lk_tariff_label = is_array($mobile_lk_tariff) && !empty($mobile_lk_tariff['product_name']) ? (string) $mobile_lk_tariff['product_name'] : __('Подписка не активна', 'yoga');
$mobile_lk_site_navigation = function_exists('yoga_get_secondary_site_navigation') ? yoga_get_secondary_site_navigation() : array();
$mobile_lk_link = static function($key, $label) use ($mobile_lk_site_navigation) {
	$link = $mobile_lk_site_navigation[$key] ?? array();
	return array(
		'label' => (string) ($link['label'] ?? $label),
		'url' => (string) ($link['url'] ?? home_url('/')),
	);
};
$mobile_lk_library_link = $mobile_lk_link('library', 'Библиотека практик');
$mobile_lk_tariffs_link = $mobile_lk_link('tariffs', 'Тарифы и подписка');
$mobile_lk_about_link = $mobile_lk_link('about', 'О нас');
$mobile_lk_blog_link = $mobile_lk_link('blog', 'Блог');
$mobile_lk_contacts_link = $mobile_lk_link('contacts', 'Контакты');
$mobile_lk_faq_link = $mobile_lk_link('faq', 'FAQ');
$mobile_active_sadhanas_count = function_exists('yoga_sadhana_active_count')
	? yoga_sadhana_active_count((int) get_current_user_id())
	: 0;
?><div class="modal-mobile-menu-lk">
	<div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
			<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-modal-close'); ?>" width="100%" height="100%"></use>
		</svg>
	</div>
	<div class="mobile-menu-inner">
		<div class="lk-mobile-menu-head"><a class="lk-mobile-menu-rate" href="<?php echo esc_url($mobile_lk_tariffs_link['url']); ?>"><svg aria-hidden="true"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#personal-status-crown'); ?>"></use></svg><span><?php echo esc_html($mobile_lk_tariff_label); ?></span></a></div>
		<div class="mobile-menu">
			<div class="mobile-menu__slide mobile-menu__slide_main active">
				<div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
					<nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
							<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-user', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои данные', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
							<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-history', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('История практик', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
							<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-lotus', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Мои садханы', 'yoga'); ?></span>
							<?php if ($mobile_active_sadhanas_count > 0) : ?>
								<span class="sidebar-menu__count" data-sadhana-active-count aria-label="<?php echo esc_attr(sprintf(_n('%d активная садхана', '%d активных садхан', $mobile_active_sadhanas_count, 'yoga'), $mobile_active_sadhanas_count)); ?>"><?php echo esc_html((string) $mobile_active_sadhanas_count); ?></span>
							<?php endif; ?>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '3' ? ' active' : ''; ?>" data-target="3">
						<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-heart', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Избранное', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
							<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-smile', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Рекомендации', 'yoga'); ?></span>
						</div>
						<div class="sidebar-menu__item<?php echo $mobile_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
							<div class="sidebar-menu__item-icon">
								<?php yoga_render_lk_menu_icon('lk-sidebar-question', 'sidebar-menu__item-svg'); ?>
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
								<?php yoga_render_lk_menu_icon('lk_bell', 'sidebar-menu__item-svg'); ?>
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
								<?php yoga_render_lk_menu_icon('lk-sidebar-settings', 'sidebar-menu__item-svg'); ?>
							</div>
							<span class="sidebar-menu__label"><?php esc_html_e('Настройки подписки', 'yoga'); ?></span>
						</div>
					</nav>
				</div>

				<hr class="sidebar-menu-sep" aria-hidden="true">
				<nav class="sidebar-menu-secondary" aria-label="<?php esc_attr_e('Разделы сайта', 'yoga'); ?>">
					<a class="sidebar-menu-secondary__link sidebar-menu-secondary__link--library" href="<?php echo esc_url($mobile_lk_library_link['url']); ?>"><span><?php echo esc_html($mobile_lk_library_link['label']); ?></span><svg class="sidebar-menu-secondary__chevron" viewBox="0 0 8 16" aria-hidden="true"><path d="M1 1l6 7-6 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_tariffs_link['url']); ?>"><span><?php echo esc_html($mobile_lk_tariffs_link['label']); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_about_link['url']); ?>"><span><?php echo esc_html($mobile_lk_about_link['label']); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_blog_link['url']); ?>"><span><?php echo esc_html($mobile_lk_blog_link['label']); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_contacts_link['url']); ?>"><span><?php echo esc_html($mobile_lk_contacts_link['label']); ?></span></a>
					<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($mobile_lk_faq_link['url']); ?>"><span><?php echo esc_html($mobile_lk_faq_link['label']); ?></span></a>
				</nav>
				<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

				<div class="mobile-menu-exit sidebar-exit modal-call modal-call_logout">
					<div class="sidebar-exit__icon">
						<?php yoga_render_lk_menu_icon('lk-sidebar-logout', 'sidebar-exit__svg'); ?>
					</div>
					<span class="sidebar-menu__label"><?php esc_html_e('Выйти', 'yoga'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
