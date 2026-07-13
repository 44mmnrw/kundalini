<?php
	/**
		* The footer for our theme
	*/
?>
</main>
<?php
$footer_option = static function($key) {
	return function_exists('get_field') ? trim((string) get_field($key, 'option')) : '';
};

$footer_nav_urls = function_exists('yoga_lk_sidebar_secondary_nav_urls')
	? yoga_lk_sidebar_secondary_nav_urls()
	: array(
		'faq'      => home_url('/'),
		'contacts' => home_url('/'),
		'blog'     => home_url('/blog/'),
		'about'    => home_url('/'),
		'tariffs'  => home_url('/'),
		'library'  => home_url('/'),
	);

$footer_main_links = array(
	array('label' => 'FAQ', 'url' => $footer_nav_urls['faq'] ?? home_url('/')),
	array('label' => 'Контакты', 'url' => $footer_nav_urls['contacts'] ?? home_url('/')),
	array('label' => 'Блог', 'url' => $footer_nav_urls['blog'] ?? home_url('/blog/')),
	array('label' => 'О нас', 'url' => $footer_nav_urls['about'] ?? home_url('/')),
	array('label' => 'Тарифы и подписка', 'url' => $footer_nav_urls['tariffs'] ?? home_url('/')),
	array('label' => 'Библиотека практик', 'url' => $footer_nav_urls['library'] ?? home_url('/')),
);

$footer_privacy_url = $footer_option('privacy_policy_link');
$legal_url = static function($type, $fallback = '') {
	return function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url($type, $fallback) : $fallback;
};
$footer_privacy_url = $legal_url('privacy_policy', $footer_privacy_url);
$footer_legal_links = array(
	array('label' => 'Публичная оферта', 'url' => $legal_url('public_offer', $footer_option('public_offer_link'))),
	array('label' => 'Политика конфиденциальности', 'url' => $footer_privacy_url),
	array('label' => 'Политика куки', 'url' => $legal_url('cookie_policy', $footer_option('cookie_policy_link') ?: $footer_privacy_url)),
	array('label' => 'Согласие на обработку персональных данных', 'url' => $legal_url('personal_data', $footer_option('personal_data_processing_link') ?: $footer_option('personal_data_link') ?: $footer_privacy_url)),
	array('label' => 'Противопоказания и отказ от ответственности', 'url' => $legal_url('contraindications', $footer_option('contraindications_link') ?: $footer_option('disclaimer_link') ?: $footer_privacy_url)),
	array('label' => 'Пользовательское соглашение', 'url' => $legal_url('user_agreement', $footer_option('user_agreement_link'))),
);

$footer_socials = array(
	array('label' => 'YouTube', 'url' => $footer_option('youtube_link'), 'icon' => 'footer-social-youtube'),
	array('label' => 'Telegram', 'url' => $footer_option('telegram_link'), 'icon' => 'footer-social-telegram'),
	array('label' => 'Rutube', 'url' => $footer_option('rutube_link'), 'icon' => 'footer-social-rutube'),
	array('label' => 'Дзен', 'url' => $footer_option('yandex_zen_link') ?: $footer_option('zen_link'), 'icon' => 'footer-social-zen'),
	array('label' => 'GemSpace', 'url' => $footer_option('gemspace_link'), 'icon' => 'footer-social-gemspace'),
	array('label' => 'VK', 'url' => $footer_option('vk_link') ?: $footer_option('vkontakte_link'), 'icon' => 'footer-social-vk'),
);

$footer_copyright = $footer_option('copyright_text') ?: 'Все права защищены.';
$footer_requisites = $footer_option('footer_requisites') ?: "ИП КСЕНОФОНТОВА МАРИНА ЕВГЕНЬЕВНА\nИНН 632200860531\nОГРНИП 319631300101827";
$footer_requisites_lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $footer_requisites))));
$is_blog_footer = is_home()
	|| is_category()
	|| is_tag()
	|| is_date()
	|| is_author();
$is_home_footer = is_front_page()
	|| is_page_template('templates-page/homepage.php')
	|| $is_blog_footer;
?>
<?php if ($is_home_footer) : ?>
<section class="home-footer-subscribe" aria-labelledby="home-footer-subscribe-title">
	<p class="home-footer-subscribe__eyebrow">Оставайтесь вместе с нами</p>
	<h2 class="home-footer-subscribe__title" id="home-footer-subscribe-title"><span class="home-footer-subscribe__mark">Подпишитесь,</span> чтобы <span class="home-footer-subscribe__always">всег<span class="home-footer-subscribe__star" aria-hidden="true"></span>да</span> быть в курсе <span class="home-footer-subscribe__green">новых материалов</span>,<br>акций и <span class="home-footer-subscribe__thumb" aria-hidden="true"></span> спецпредложений!</h2>
	<form class="footer-subscribe home-footer-subscribe__form" action="<?php echo esc_url(home_url('/')); ?>" method="post">
		<?php wp_nonce_field('subscription_nonce', 'subscription_nonce_field'); ?>
		<div class="footer-subscribe__field"><input id="home-footer-subscribe-email" name="footer_email" type="email" placeholder="E-mail" aria-label="E-mail"><button class="footer-subscribe__submit" type="submit" aria-label="Подписаться на новости"><svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#footer-arrow-up-right'); ?>"></use></svg></button></div>
		<label class="footer-subscribe__agree"><input class="footer-subscribe__checkbox" type="checkbox" name="footer_subscribe_agree" checked><span>Я соглашаюсь на <a href="<?php echo esc_url(($footer_legal_links[3]['url'] ?? '') ?: $footer_privacy_url ?: home_url('/')); ?>">обработку персональных данных</a> и получение рассылок</span></label>
	</form>
</section>
<?php endif; ?>
<footer class="footer" id="footer">
	<div class="footer__inner">
		<div class="footer__top">
			<nav class="footer__nav" aria-label="<?php echo esc_attr__('Навигация в футере', 'yoga'); ?>">
				<ul class="footer__list">
					<?php foreach ($footer_main_links as $link) : ?>
						<li class="footer__item">
							<a class="footer__link" href="<?php echo esc_url($link['url']); ?>">
								<?php echo esc_html($link['label']); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<nav class="footer__legal" aria-label="<?php echo esc_attr__('Юридическая информация', 'yoga'); ?>">
				<ul class="footer__list">
					<?php foreach ($footer_legal_links as $link) : ?>
						<li class="footer__item">
							<a class="footer__link" href="<?php echo esc_url($link['url'] ?: home_url('/')); ?>">
								<?php echo esc_html($link['label']); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="footer__info">
				<div class="footer__company">
					<div class="footer__requisites">
						<?php foreach ($footer_requisites_lines as $requisite_line) : ?>
							<p><?php echo esc_html($requisite_line); ?></p>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if (!$is_home_footer) : ?><form class="footer-subscribe" action="<?php echo esc_url(home_url('/')); ?>" method="post">
					<?php wp_nonce_field('subscription_nonce', 'subscription_nonce_field'); ?>
					<div class="footer-subscribe__main">
						<label class="footer-subscribe__label" for="footer-subscribe-email">
							Подписаться на новости:
						</label>
						<div class="footer-subscribe__field">
							<input id="footer-subscribe-email" name="footer_email" type="email" placeholder="Электронная почта">
							<button class="footer-subscribe__submit" type="submit" aria-label="<?php echo esc_attr__('Подписаться на новости', 'yoga'); ?>">
								<svg aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#footer-arrow-up-right'); ?>"></use>
								</svg>
							</button>
						</div>
					</div>
					<label class="footer-subscribe__agree">
						<input class="footer-subscribe__checkbox" type="checkbox" name="footer_subscribe_agree" checked>
						<span>
							Я соглашаюсь на
							<a href="<?php echo esc_url(($footer_legal_links[3]['url'] ?? '') ?: $footer_privacy_url ?: home_url('/')); ?>">
								обработку персональных данных
							</a>
							и получение рассылок
						</span>
					</label>
				</form><?php endif; ?>
			</div>
		</div>

		<div class="footer__bottom">
			<a href="<?php echo esc_url(home_url('/')); ?>" class="footer__logo" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
				<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/logo/logo.svg'); ?>" alt="">
			</a>
			<ul class="footer__socials footer__socials_desktop" aria-label="<?php echo esc_attr__('Социальные сети', 'yoga'); ?>">
				<?php foreach ($footer_socials as $social) : ?>
					<?php if ($social['url'] === '') continue; ?>
					<li>
						<a class="footer__social" href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr($social['label']); ?>">
							<svg aria-hidden="true" focusable="false">
								<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . $social['icon']); ?>"></use>
							</svg>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="footer__copyright">
				<?php echo esc_html(wp_date('Y')); ?> © <?php echo esc_html($footer_copyright); ?>
			</p>
		</div>
	</div>
</footer><?php get_template_part('template-parts/modal', 'cookie'); ?><div class="overlay"></div><div class="overlay-modal"></div><?php get_template_part('template-parts/modal', 'menu'); ?><?php get_template_part('template-parts/modal', 'mobile-menu'); ?><?php
$is_lk_shell = is_page_template('templates-page/lk.php')
	|| is_page('my-account')
	|| (function_exists('is_account_page') && is_account_page());
if ($is_lk_shell) {
	get_template_part('template-parts/modal', 'mobile-menu-lk');
	get_template_part('template-parts/modal', 'subscription-cancel');
	get_template_part('template-parts/modal', 'card-binding');
}
?><?php
$comment_modal_post_type = get_post_type();
if (is_singular() && function_exists('yoga_ajax_comment_supported_post_types') && in_array($comment_modal_post_type, yoga_ajax_comment_supported_post_types(), true)) {
	get_template_part('template-parts/modal', 'comment-delete');
}
?><?php get_template_part('template-parts/modal', 'login'); ?><?php get_template_part('template-parts/modal', 'review'); ?><div class="modal modal-default modal-default_cardsucces">
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
</div><div class="modal modal-default yoga-subscription-success-modal" id="yoga-subscription-success-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="yoga-subscription-success-title">
	<button class="modal-close yoga-subscription-success-modal__close" type="button" aria-label="Закрыть"></button>
	<div class="yoga-subscription-success-modal__content">
		<img class="yoga-subscription-success-modal__icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/subscription-success-check.svg'); ?>" alt="">
		<h3 id="yoga-subscription-success-title">Подписка оформлена!<br>Обещаем отсутствие спама :)</h3>
	</div>
</div><div class="modal modal-default modal-default_formsucces">
	<button class="modal-close" type="button" aria-label="Закрыть">
        <img src="<?=get_template_directory_uri()?>/assets/img/modal-close-img.png" alt="">
	</button>
	<div class="thanksforqw">
		<img class="thanksforqw__icon" src="<?=get_template_directory_uri()?>/assets/svg/comment-delete-success.svg" alt="">
        <h3>
			Мы получили ваше сообщение!
		</h3>
		<p>
			Вернёмся с ответом очень скоро.
		</p>
	</div>
</div><div class="modal modal-default modal-default_logout">
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
</div><?php
if (function_exists('yoga_is_theme_checkout_context') && yoga_is_theme_checkout_context()) {
	get_template_part('template-parts/modal', 'cart-clear');
}
?><?php wp_footer(); ?></body>
</html>
