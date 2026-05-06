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
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/logo/logo.svg'); ?>" alt="<?php bloginfo('name'); ?>">
						</a>
						<?php
							$current_user = wp_get_current_user();
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
							$user_avatar_id = function_exists('get_field') ? (int) get_field('user_avatar', 'user_' . $current_user->ID) : 0;
							$user_avatar_html = $user_avatar_id > 0
								? wp_get_attachment_image($user_avatar_id, 'thumbnail', false, array(
									'class' => 'lk-login-btn__avatar',
									'alt' => '',
									'loading' => 'lazy',
									'decoding' => 'async',
								))
								: '';
						?>
						<div class="notification-icon" role="button" tabindex="0" aria-expanded="false" aria-controls="lk-notifications-popup">
							<svg class="notification-icon__img" aria-hidden="true">
								<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#notification-bell-icon'); ?>"></use>
							</svg>
							<span>0</span>
							<div class="lk-notifications-popup" id="lk-notifications-popup" aria-hidden="true">
								<div class="lk-notifications-popup__title">Уведомления</div>
								<div class="lk-notifications-popup__empty">Ничего нет...</div>
							</div>
						</div>
						<div class="personal-status">
							<svg class="personal-status__img" aria-hidden="true">
								<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#personal-status-crown'); ?>"></use>
							</svg>
							<?php
								$tariff = get_current_user_tariff();
								if ($tariff) {
									echo '<span>' . $tariff['product_name'] . '</span>';
								}
							?>
						</div>
						<div class="lk-header-main__buttons">
							<div class="lk-login-btn">
								<?php if ($user_avatar_html) : ?>
									<?php echo $user_avatar_html; ?>
								<?php else : ?>
									<span><?php echo esc_html($user_initial); ?></span>
								<?php endif; ?>
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