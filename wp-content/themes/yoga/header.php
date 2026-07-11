<?php
/**
 * The header for our theme: главный сайт и ЛК — одна разметка, переключение через yoga_is_lk_shell().
 */
$is_lk_shell = function_exists( 'yoga_is_lk_shell' ) && yoga_is_lk_shell();
?>
<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="keywords" content="">
		<meta name="description" content="">
		<meta name="author" content="w-owl.ru">
		<meta name="copyright" content="">
		<meta name="format-detection" content="telephone=no">
		
		<title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
		<link rel="shortcut icon" href="<?php echo esc_url(get_template_directory_uri() . '/assets/img/favicon.png'); ?>" type="image/x-icon">
		<?php wp_head(); ?>
	</head>
	
	<body <?php body_class( $is_lk_shell ? 'body body_lk' : 'body body_main' ); ?> id="body">
		<?php get_template_part('template-parts/inline-svg-sprite'); ?>
		<?php
		$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
		$tariffs_url = home_url('/product-category/tariffs/');
		if ($tariffs_term && !is_wp_error($tariffs_term)) {
			$term_link = get_term_link($tariffs_term);
			if (!is_wp_error($term_link)) {
				$tariffs_url = $term_link;
			}
		}
		$lk_page_url = function_exists('yoga_get_lk_page_url') ? yoga_get_lk_page_url() : '';
		$myaccount_url = $lk_page_url !== ''
			? $lk_page_url
			: get_permalink(get_option('woocommerce_myaccount_page_id'));
		$header_notifications = is_user_logged_in() && function_exists('yoga_get_user_notifications')
			? yoga_get_user_notifications((int) get_current_user_id(), 3)
			: array();
		$header_unread_notifications = is_user_logged_in() && function_exists('yoga_get_unread_user_notifications')
			? yoga_get_unread_user_notifications((int) get_current_user_id())
			: array();
		$header_unread_notifications_count = count($header_unread_notifications);
		$format_header_notification_time = static function (string $created_at): string {
			$created_timestamp = strtotime($created_at);
			if (!$created_timestamp) {
				return '';
			}
			$diff = max(0, (int) current_time('timestamp') - $created_timestamp);
			if ($diff < MINUTE_IN_SECONDS) {
				return __('Только что', 'yoga');
			}
			if ($diff < HOUR_IN_SECONDS) {
				return sprintf(__('%d мин назад', 'yoga'), max(1, (int) floor($diff / MINUTE_IN_SECONDS)));
			}
			if ($diff < DAY_IN_SECONDS) {
				return sprintf(__('%d ч назад', 'yoga'), max(1, (int) floor($diff / HOUR_IN_SECONDS)));
			}
			if ($diff < 2 * DAY_IN_SECONDS) {
				return __('Вчера', 'yoga');
			}
			return sprintf(__('%d дн назад', 'yoga'), max(2, (int) floor($diff / DAY_IN_SECONDS)));
		};
		$header_notifications_url = function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('notifications') : $myaccount_url;
		if (!$myaccount_url) {
			$myaccount_url = home_url('/');
		}
		$favorites_href = function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('favorites') : home_url('/');
		$notifications_settings_href = function_exists('yoga_get_lk_section_url') ? yoga_get_lk_section_url('notification-settings') : $myaccount_url;
		$header_favorites = is_user_logged_in()
			? get_user_meta((int) get_current_user_id(), 'favorite_practices', true)
			: array();
		if (function_exists('yoga_normalize_favorites')) {
			$header_favorites = yoga_normalize_favorites($header_favorites);
		} elseif (!is_array($header_favorites)) {
			$header_favorites = array();
		}
		$header_favorites_count = count($header_favorites);
		$tariff_row = is_user_logged_in() && function_exists('get_current_user_tariff') ? get_current_user_tariff() : false;
		$tariff_product_name = '';
		if (is_array($tariff_row) && !empty($tariff_row['product_name'])) {
			$tariff_product_name = (string) $tariff_row['product_name'];
		}
		$pill_href = $tariffs_url;
		$pill_label = __('Подписка не активна', 'yoga');
		$pill_classes = 'header-rate-pill header-rate-pill_inactive';
		if ($tariff_product_name !== '') {
			$pill_label = $tariff_product_name;
			$pill_classes = 'header-rate-pill';
			if (function_exists('yoga_get_lk_section_url')) {
				$pill_href = yoga_get_lk_section_url('subscription');
			}
		}
		$user_initial = 'U';
		$user_avatar_html = '';
		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$user_first_name = trim((string) get_user_meta($current_user->ID, 'first_name', true));
			$user_display_name = trim((string) $current_user->display_name);
			$user_login = trim((string) $current_user->user_login);
			$user_source_name = $user_first_name !== '' ? $user_first_name : ($user_display_name !== '' ? $user_display_name : $user_login);
			if ($user_source_name !== '') {
				$user_initial = function_exists('mb_substr') && function_exists('mb_strtoupper')
					? mb_strtoupper(mb_substr($user_source_name, 0, 1, 'UTF-8'), 'UTF-8')
					: strtoupper(substr($user_source_name, 0, 1));
			}
			$user_avatar_id = function_exists('get_field') ? (int) get_field('user_avatar', 'user_' . $current_user->ID) : 0;
			$user_avatar_html = $user_avatar_id > 0
				? wp_get_attachment_image($user_avatar_id, 'thumbnail', false, array(
					'class' => 'login-icon__avatar',
					'alt' => '',
					'loading' => 'lazy',
					'decoding' => 'async',
				))
				: '';
		}
		?>
		<header id="header" class="header animated fadeIn slow delay-200ms">
			<div class="container">
				<div class="row">
					<div class="header-content">
						<a href="<?php echo esc_url(home_url('/')); ?>" class="logo-header">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/logo/logo.svg'); ?>" alt="<?php bloginfo('name'); ?>">
						</a>
						<nav class="main-menu">
							<?php
								wp_nav_menu(array(
									'theme_location' => 'primary',
									'container' => false,
									'menu_class' => '',
									'items_wrap' => '<ul>%3$s</ul>',
									'walker' => new Custom_Menu_Walker(),
								));
							?>
						</nav>
						<div class="header-lk<?php echo is_user_logged_in() ? ' header-lk--logged' : ''; ?>">
							<?php if (is_user_logged_in()) : ?>
							<div class="header-lk-logged-desktop">
								<a class="<?php echo esc_attr($pill_classes); ?>" href="<?php echo esc_url($pill_href); ?>">
									<svg class="header-rate-pill__icon" aria-hidden="true" focusable="false">
										<use href="#personal-status-crown"></use>
									</svg>
									<span><?php echo esc_html($pill_label); ?></span>
								</a>
								<div class="notification-icon notification-icon_header<?php echo $header_unread_notifications_count > 0 ? ' notification-icon_header--has-notifications' : ''; ?>" role="button" tabindex="0" aria-expanded="false" aria-controls="header-notifications-popup">
									<svg class="notification-icon__img" aria-hidden="true">
										<use href="#<?php echo $header_unread_notifications_count > 0 ? 'notification-bell-filled-icon' : 'notification-bell-icon'; ?>"></use>
									</svg>
									<?php if ($header_unread_notifications_count > 0): ?><span class="notification-icon__count"><?php echo esc_html((string) $header_unread_notifications_count); ?></span><?php endif; ?>
									<div class="lk-notifications-popup<?php echo $header_unread_notifications_count === 0 ? ' lk-notifications-popup--empty' : ''; ?>" id="header-notifications-popup" aria-hidden="true">
									<div class="lk-notifications-popup__head">
										<strong><?php esc_html_e('Уведомления', 'yoga'); ?></strong>
										<div class="lk-notifications-popup__head-actions">
											<a class="lk-notifications-popup__settings" href="<?php echo esc_url($notifications_settings_href); ?>" aria-label="<?php esc_attr_e('Настройки уведомлений', 'yoga'); ?>"><svg aria-hidden="true"><use href="#lk-sidebar-settings"></use></svg></a>
											<?php if ($header_unread_notifications_count > 0): ?>
												<button class="lk-notifications-popup__read-all lk-notifications-page__read-all" type="button"><?php esc_html_e('Прочитать все', 'yoga'); ?></button>
											<?php endif; ?>
										</div>
									</div>
										<?php if ($header_unread_notifications_count === 0): ?>
											<div class="lk-notifications-popup__empty">
												<span class="lk-notifications-popup__empty-icon"><svg aria-hidden="true"><use href="#notification-bell-icon"></use></svg></span>
												<strong><?php esc_html_e('Здесь пока ничего нет', 'yoga'); ?></strong>
												<span><?php esc_html_e('Здесь появятся уведомления', 'yoga'); ?></span>
											</div>
										<?php else: ?>
											<div class="lk-notifications-popup__list">
											<?php foreach ($header_unread_notifications as $notification): ?>
												<?php
												$notification_type = sanitize_key((string) ($notification['type'] ?? ''));
												$notification_title = $notification_type === 'question_answer'
													? __('Ответ преподавателя', 'yoga')
													: (string) ($notification['title'] ?? '');
												$notification_url = $notification_type === 'question_answer' && function_exists('yoga_get_notification_read_url')
													? yoga_get_notification_read_url($notification, 'questions')
													: (string) ($notification['url'] ?? $header_notifications_url);
												$notification_time = $format_header_notification_time((string) ($notification['created_at'] ?? ''));
												?>
												<a class="lk-notification lk-notifications-popup__item lk-notifications-popup__item--<?php echo esc_attr($notification_type ?: 'default'); ?>" data-notification-id="<?php echo esc_attr((string) ($notification['id'] ?? '')); ?>" data-notification-type="<?php echo esc_attr($notification_type); ?>" href="<?php echo esc_url($notification_url); ?>">
													<span class="lk-notifications-popup__item-head">
														<span class="lk-notifications-popup__item-icon"><svg aria-hidden="true"><use href="#<?php echo $notification_type === 'question_answer' ? 'notification-teacher-reply-icon' : 'notification-bell-icon'; ?>"></use></svg></span>
														<strong><?php echo esc_html($notification_title); ?></strong>
														<?php if ($notification_time !== ''): ?><time datetime="<?php echo esc_attr((string) ($notification['created_at'] ?? '')); ?>"><?php echo esc_html($notification_time); ?></time><?php endif; ?>
													</span>
													<span class="lk-notifications-popup__item-message"><?php echo esc_html((string) ($notification['message'] ?? '')); ?></span>
												</a>
											<?php endforeach; ?>
										</div>
										<a class="lk-notifications-popup__all" href="<?php echo esc_url($header_notifications_url); ?>"><?php esc_html_e('Смотреть все уведомления', 'yoga'); ?></a>
										<?php endif; ?>
									</div>
								</div>
								<a class="header-favorites-link<?php echo $header_favorites_count > 0 ? ' header-favorites-link--active' : ''; ?>" href="<?php echo esc_url($favorites_href); ?>" aria-label="<?php echo esc_attr(sprintf(__('Избранное: %d', 'yoga'), $header_favorites_count)); ?>">
									<svg aria-hidden="true" focusable="false">
										<use href="#<?php echo $header_favorites_count > 0 ? 'header-heart-filled' : 'header-heart'; ?>"></use>
									</svg>
									<?php if ($header_favorites_count > 0) : ?>
										<span class="header-favorites-link__count" aria-hidden="true"><?php echo esc_html((string) $header_favorites_count); ?></span>
									<?php endif; ?>
								</a>
							</div>
							<div class="header-lk__trailing">
								<a href="<?php echo esc_url($myaccount_url); ?>" class="login-icon login-icon_logged" aria-label="<?php echo esc_attr__('Личный кабинет', 'yoga'); ?>">
									<?php if ($user_avatar_html) : ?>
										<?php echo $user_avatar_html; ?>
									<?php else : ?>
										<span class="login-icon__initial"><?php echo esc_html($user_initial); ?></span>
									<?php endif; ?>
								</a>
								<div class="burger">
									<svg aria-hidden="true" focusable="false">
										<use href="#burger-menu-lines"></use>
									</svg>
								</div>
							</div>
							<?php else : ?>
							<div class="header-rate-pill header-rate-pill_inactive header-rate-pill_guest modal-call_login" role="button" tabindex="0">
								<span><?php echo esc_html($pill_label); ?></span>
							</div>
							<div class="header-lk__trailing">
								<div class="login-icon modal-call_login">
									<svg aria-hidden="true" focusable="false">
										<use href="#login-user-icon"></use>
									</svg>
								</div>
								<div class="burger">
									<svg aria-hidden="true" focusable="false">
										<use href="#burger-menu-lines"></use>
									</svg>
								</div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</header>
	<main>			
