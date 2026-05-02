<?php
	/**
		* The footer for our theme
	*/
?>
</main>
<?php if ( ! is_page('my-account') && ! is_page_template('templates-page/lk.php') ) : ?>
<footer class="footer wow fadeIn delay-200ms <?php 
    if ( empty($GLOBALS['has_subscription_section']) ) echo 'footer_big'; 
	?>" id="footer">
	<div class="container">
        <div class="row">
			<div class="footer-main">
				<a href="<?php echo esc_url(home_url('/')); ?>" class="logo-footer wow fadeIn delay-200ms">
					<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/logo/logo.svg'); ?>" alt="<?php bloginfo('name'); ?>">
				</a>
				<nav class="footer-menu wow fadeIn delay-200ms">
					<ul>
						<?php
							wp_nav_menu( array(
							'theme_location' => 'primary',
							'container'      => false,
							'items_wrap'     => '%3$s', // убираем обертку <ul>
							'link_before'    => '',
							'link_after'     => '',
							'menu_class'     => 'footer-menu',
							'walker'         => new Footer_Walker()
							) );
						?>
						
					</ul>
				</nav>
				<div class="footer-social wow fadeIn delay-200ms">
					<ul>
						<li>
							<a href="<?php echo esc_url(get_field('telegram_link', 'option')); ?>" class="footer-menu-item" target="_blank" rel="noopener">
								Telegram
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url(get_field('youtube_link', 'option')); ?>" class="footer-menu-item" target="_blank" rel="noopener">
								YouTube
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
        <div class="row">
			<div class="footer-add">
				<ul class="footer-add__links wow fadeIn delay-200ms">
					<li>
						<a href="<?php echo esc_url(get_field('privacy_policy_link', 'option')); ?>" class="footer-link" target="_blank" rel="noopener">
							Политика конфиденциальности
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url(get_field('user_agreement_link', 'option')); ?>" class="footer-link" target="_blank" rel="noopener">
							Пользовательское соглашение
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url(get_field('public_offer_link', 'option')); ?>" class="footer-link" target="_blank" rel="noopener">
							Публичная оферта
						</a>
					</li>
				</ul>
				<span class="footer-rules wow fadeIn delay-200ms">
					<?php echo date('Y'); ?> © <?php the_field('copyright_text', 'option'); ?>
				</span>
			</div>
		</div>
	</div>
</footer>
<?php endif; ?>

<!-- Модальные окна (перенесены из index.html) -->
<div class="overlay"></div>
<div class="overlay-modal"></div>
<?php get_template_part('template-parts/modal', 'menu'); ?>
<?php get_template_part('template-parts/modal', 'mobile-menu'); ?>
<?php get_template_part('template-parts/modal', 'login'); ?>
<?php get_template_part('template-parts/modal', 'review'); ?>
<div class="modal modal-default modal-default_cardsucces">
	<div class="modal-close">
        <img src="<?=get_template_directory_uri()?>/assets/img/modal-close-img.png" alt="">
	</div>
	<div class="delcomm active">
        <div class="delcomm__succes">
			<b>
				Карта добавлена
			</b>
		</div>  
	</div>
</div>

<div class="modal modal-default modal-default_formsucces">
	<div class="modal-close">
        <img src="<?=get_template_directory_uri()?>/assets/img/modal-close-img.png" alt="">
	</div>
	<div class="thanksforqw">
        <h3>
			Мы получили ваше сообщение!
			</h3>
			<p>
			Вернёмся с ответом очень скоро.
		</p>
	</div>
	
</div>

<div class="modal modal-default modal-default_favoritesucces">
	<div class="modal-close">
        <img src="<?=get_template_directory_uri()?>/assets/img/modal-close-img.png" alt="">
	</div>
	<div class="thanksforqw">
        <h3>
			Избранное обновлено
		</h3>
		<p class="favorite-modal-message">
			Практика добавлена в избранное.
		</p>
	</div>
</div>

<div class="modal modal-default modal-default_logout">
	<div class="modal-close">
        <img src="<?=get_template_directory_uri()?>/assets/img/modal-close-img.png" alt="">
	</div>
	<div class="delcomm">
        <div class="delcomm__main">
			<h3>
				Выход
			</h3>
			<p>
				Вы уверены, что хотите выйти из аккаунта? Вы всегда сможете вернуться.
			</p>
			<div class="delcomm-buttons">
				<div class="btn btn_white modal-close-logout">
					<span>
						Нет, остаться
					</span>
				</div>
				<a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="btn btn_dark">
					<span>
						Да, выйти
					</span>
				</a>
			</div>
		</div>   
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>