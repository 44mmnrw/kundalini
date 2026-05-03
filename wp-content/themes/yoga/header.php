<?php
	/**
		* The header for our theme
	*/
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
	
	<body <?php body_class('body body_main'); ?> id="body">
		<header id="header" class="header animated fadeIn slow delay-200ms">
			<div class="container">
				<div class="row">
					<div class="header-content">
						<a href="<?php echo esc_url(home_url('/')); ?>" class="logo-header">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/logo/logo.svg'); ?>" alt="<?php bloginfo('name'); ?>">
						</a>
						<nav class="main-menu">
							<?php
								wp_nav_menu( array(
								'theme_location' => 'primary',
								'container' => false,
								'menu_class' => '',
								'items_wrap' => '<ul>%3$s</ul>',
								'walker' => new Custom_Menu_Walker()
								) );
							?>
						</nav>
						<div class="header-lk">
							<?php
							$has_paid_tariff = is_user_logged_in() && get_current_user_tariff();
							if (!$has_paid_tariff) :
								$tariffs_url = home_url('/product-category/tariffs/');
								if (is_user_logged_in()) : ?>
							<a href="<?php echo esc_url($tariffs_url); ?>" class="btn btn_white">
								<span>Попробовать бесплатно</span>
							</a>
							<?php else : ?>
							<div class="btn btn_white modal-call_login">
								<span>Попробовать бесплатно</span>
							</div>
							<?php endif; endif; ?>
							<?php if (is_user_logged_in()): ?>
							<?php
								$current_user = wp_get_current_user();
								$myaccount_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
								$user_first_name = trim((string) get_user_meta($current_user->ID, 'first_name', true));
								$user_display_name = trim((string) $current_user->display_name);
								$user_login = trim((string) $current_user->user_login);
								$user_source_name = $user_first_name !== '' ? $user_first_name : ($user_display_name !== '' ? $user_display_name : $user_login);
								if ($user_source_name === '') {
									$user_source_name = 'U';
								}
								if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
									$user_initial = mb_strtoupper(mb_substr($user_source_name, 0, 1, 'UTF-8'), 'UTF-8');
								} else {
									$user_initial = strtoupper(substr($user_source_name, 0, 1));
								}
							?>
							<a href="<?php echo esc_url($myaccount_url); ?>" class="login-icon login-icon_logged" aria-label="<?php echo esc_attr__('Личный кабинет', 'yoga'); ?>">
								<span class="login-icon__initial"><?php echo esc_html($user_initial); ?></span>
							</a>
							<?php else: ?>
							<div class="login-icon modal-call_login">
								<svg aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#login-user-icon'); ?>"></use>
								</svg>
							</div>
							<?php endif; ?>
							<div class="burger modal-call">
								<svg aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#burger-menu-icon'); ?>"></use>
								</svg>
							</div>
						</div>
					</div>
				</div>
			</div>
		</header>
	<main>			