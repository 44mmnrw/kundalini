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
							?>
							<a href="<?php echo esc_url($myaccount_url); ?>" class="login-icon">
								<span class="user-avatar">
									<?php
										$avatar_id = get_field('user_avatar', 'user_' . $current_user->ID);
										
										if ($avatar_id) {
											echo wp_get_attachment_image($avatar_id, 'thumbnail', false, array('class' => 'avatar'));
											} else {
											// Fallback на стандартный аватар WordPress
											echo get_avatar($current_user->ID, 96);
										}
									?>
								</span>
							</a>
							<?php else: ?>
							<div class="login-icon modal-call_login">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/login-icon.png'); ?>" alt="Вход">
							</div>
							<?php endif; ?>
							<div class="burger modal-call">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/burger.png'); ?>" alt="Меню">
							</div>
						</div>
					</div>
				</div>
			</div>
		</header>
	<main>			