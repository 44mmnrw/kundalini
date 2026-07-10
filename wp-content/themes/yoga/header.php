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
		if (!$myaccount_url) {
			$myaccount_url = home_url('/');
		}
		$favorites_href = ($lk_page_url !== '')
			? trailingslashit($lk_page_url) . '#lk-slide-favorites'
			: home_url('/');
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
			if ($lk_page_url !== '') {
				$pill_href = trailingslashit($lk_page_url) . '#lk-slide-settings';
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
								<div class="notification-icon notification-icon_header" role="button" tabindex="0" aria-expanded="false" aria-controls="header-notifications-popup">
									<svg class="notification-icon__img" aria-hidden="true">
										<use href="#notification-bell-icon"></use>
									</svg>
									<div class="lk-notifications-popup" id="header-notifications-popup" aria-hidden="true">
										<div class="lk-notifications-popup__title"><?php esc_html_e('Уведомления', 'yoga'); ?></div>
										<div class="lk-notifications-popup__empty"><?php esc_html_e('Ничего нет...', 'yoga'); ?></div>
									</div>
								</div>
								<a class="header-favorites-link" href="<?php echo esc_url($favorites_href); ?>" aria-label="<?php esc_attr_e('Избранное', 'yoga'); ?>">
									<svg aria-hidden="true" focusable="false">
										<use href="#noun-heart"></use>
									</svg>
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
