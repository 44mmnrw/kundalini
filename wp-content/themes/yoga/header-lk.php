<?php
	/**
		* Header for Личный кабинет template
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
	
	<body <?php body_class('body body_lk'); ?> id="body">
		<header id="header" class="header animated fadeIn slow delay-200ms">
			<div class="container">
				<div class="row">
					<div class="lk-header-main">
						<a href="<?php echo esc_url(home_url('/')); ?>" class="logo-header">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.png'); ?>" alt="<?php bloginfo('name'); ?>">
						</a>
						<div class="notification-icon">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/notification-icon.png'); ?>" alt="Уведомления">
							<span>0</span>
						</div>
						<div class="personal-status">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/personal-status-icon.png'); ?>" alt="Статус" class="personal-status__img">
							<?php
								$tariff = get_current_user_tariff();
								if ($tariff) {
									echo '<span>' . $tariff['product_name'] . '</span>';
								}
							?>
						</div>
						<div class="lk-header-main__buttons">
							<div class="lk-login-btn">
								<span>М</span>
							</div>
							<div class="lk-burger">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/burger-lk-icon.png'); ?>" alt="Меню" class="active">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/burger-lk-icon_close.png'); ?>" alt="Закрыть">
							</div>
						</div>
					</div>
					<div class="lk-header-menu">
						<nav>
							<?php
								wp_nav_menu( array(
								'theme_location' => 'primary',
								'container' => false,
								'menu_class' => '',
								'items_wrap' => '<ul>%3$s</ul>',
								) );
							?>
						</nav>
					</div>
				</div>
			</div>
		</header>
	<main>			