<?php
/**
 * Шаблон общего подвала сайта и формы подписки.
 *
 * @package Yoga
 */
?>
</main>
<?php
$footer_option = static function($key) {
	return function_exists('get_field') ? trim((string) get_field($key, 'option')) : '';
};

$footer_site_navigation = function_exists('yoga_get_secondary_site_navigation')
	? yoga_get_secondary_site_navigation()
	: array();
$footer_main_links = array(
	$footer_site_navigation['faq'] ?? array('label' => 'FAQ', 'url' => home_url('/')),
	$footer_site_navigation['contacts'] ?? array('label' => 'Контакты', 'url' => home_url('/')),
	$footer_site_navigation['blog'] ?? array('label' => 'Блог', 'url' => home_url('/blog/')),
	$footer_site_navigation['about'] ?? array('label' => 'О нас', 'url' => home_url('/')),
	$footer_site_navigation['tariffs'] ?? array('label' => 'Тарифы и подписка', 'url' => home_url('/')),
	$footer_site_navigation['library'] ?? array('label' => 'Библиотека практик', 'url' => home_url('/')),
);

$footer_privacy_url = $footer_option('privacy_policy_link');
$legal_url = static function($type, $fallback = '') {
	return function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url($type, $fallback) : $fallback;
};
$legal_label = static function($type, $fallback) {
	return function_exists('yoga_get_legal_document_title') ? yoga_get_legal_document_title($type, $fallback) : $fallback;
};
$footer_privacy_url = $legal_url('privacy_policy', $footer_privacy_url);
$footer_legal_links = array(
	array('label' => $legal_label('public_offer', 'Публичная оферта'), 'url' => $legal_url('public_offer', $footer_option('public_offer_link'))),
	array('label' => $legal_label('privacy_policy', 'Политика конфиденциальности'), 'url' => $footer_privacy_url),
	array('label' => $legal_label('cookie_policy', 'Политика куки'), 'url' => $legal_url('cookie_policy', $footer_option('cookie_policy_link') ?: $footer_privacy_url)),
	array('label' => $legal_label('personal_data', 'Согласие на обработку персональных данных'), 'url' => $legal_url('personal_data', $footer_option('personal_data_processing_link') ?: $footer_option('personal_data_link') ?: $footer_privacy_url)),
	array('label' => $legal_label('contraindications', 'Противопоказания и отказ от ответственности'), 'url' => $legal_url('contraindications', $footer_option('contraindications_link') ?: $footer_option('disclaimer_link') ?: $footer_privacy_url)),
	array('label' => $legal_label('user_agreement', 'Пользовательское соглашение'), 'url' => $legal_url('user_agreement', $footer_option('user_agreement_link'))),
);

$footer_socials = array(
	array('label' => 'YouTube', 'url' => $footer_option('youtube_link'), 'icon' => 'footer-social-youtube'),
	array('label' => 'Telegram', 'url' => $footer_option('telegram_link'), 'icon' => 'footer-social-telegram'),
	array('label' => 'Rutube', 'url' => $footer_option('rutube_link'), 'icon' => 'footer-social-rutube'),
	array('label' => 'Дзен', 'url' => $footer_option('dzen_link'), 'icon' => 'footer-social-zen'),
	array('label' => 'GemSpace', 'url' => $footer_option('gem_link'), 'icon' => 'footer-social-gemspace'),
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
$footer_current_user = wp_get_current_user();
$footer_subscription_complete = $footer_current_user->exists()
	&& function_exists('yoga_is_subscription_email_subscribed')
	&& yoga_is_subscription_email_subscribed((string) $footer_current_user->user_email);
?>
<?php if ($is_home_footer) : ?>
<section class="home-footer-subscribe" aria-labelledby="home-footer-subscribe-title">
	<p class="home-footer-subscribe__eyebrow">Оставайтесь вместе с нами</p>
	<h2 class="home-footer-subscribe__title" id="home-footer-subscribe-title"><span class="home-footer-subscribe__mark">Подпишитесь,</span> чтобы <span class="home-footer-subscribe__always">всег<span class="home-footer-subscribe__star" aria-hidden="true"></span>да</span><br>быть в курсе <span class="home-footer-subscribe__green">новых материалов</span>,<br><span class="home-footer-subscribe__offers">акций и <span class="home-footer-subscribe__thumb" aria-hidden="true"></span> спецпредложений!</span></h2>
	<form class="footer-subscribe home-footer-subscribe__form<?php echo $footer_subscription_complete ? ' is-subscribed' : ''; ?>" action="<?php echo esc_url(home_url('/')); ?>" method="post"<?php echo $footer_subscription_complete ? ' aria-label="Подписка оформлена"' : ''; ?>>
		<?php wp_nonce_field('subscription_nonce', 'subscription_nonce_field'); ?>
		<div class="footer-subscribe__field"><input id="home-footer-subscribe-email" name="footer_email" type="email" placeholder="эл. почта" aria-label="эл. почта"><button class="footer-subscribe__submit" type="submit" aria-label="Подписаться на новости"><svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#footer-subscribe-arrow'); ?>"></use></svg></button></div>
		<label class="footer-subscribe__agree"><input class="footer-subscribe__checkbox" type="checkbox" name="footer_subscribe_agree" checked><span>Я соглашаюсь на <a href="<?php echo esc_url(($footer_legal_links[3]['url'] ?? '') ?: $footer_privacy_url ?: home_url('/')); ?>">обработку персональных данных</a> и получение рассылок</span></label>
		<div class="footer-subscribe__success" role="status" aria-live="polite">
			<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/smile.png'); ?>" alt="">
			<strong>Подписка оформлена!</strong>
			<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/smile.png'); ?>" alt="">
		</div>
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
					<ul class="footer__socials footer__socials_mobile" aria-label="<?php echo esc_attr__('Социальные сети', 'yoga'); ?>">
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
				</div>

				<?php if (!$is_home_footer) : ?><form class="footer-subscribe<?php echo $footer_subscription_complete ? ' is-subscribed' : ''; ?>" action="<?php echo esc_url(home_url('/')); ?>" method="post"<?php echo $footer_subscription_complete ? ' aria-label="Подписка оформлена"' : ''; ?>>
					<?php wp_nonce_field('subscription_nonce', 'subscription_nonce_field'); ?>
					<div class="footer-subscribe__main">
						<label class="footer-subscribe__label" for="footer-subscribe-email">
							Подписаться на новости:
						</label>
						<div class="footer-subscribe__field">
							<input id="footer-subscribe-email" name="footer_email" type="email" placeholder="эл. почта">
							<button class="footer-subscribe__submit" type="submit" aria-label="<?php echo esc_attr__('Подписаться на новости', 'yoga'); ?>">
								<svg aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#footer-subscribe-arrow'); ?>"></use>
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
					<div class="footer-subscribe__success" role="status" aria-live="polite">
						<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/smile.png'); ?>" alt="">
						<strong>Подписка оформлена!</strong>
						<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/smile.png'); ?>" alt="">
					</div>
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
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg>
	</div>
	<div class="delcomm active">
        <div class="delcomm__succes">
			<b>
				Карта добавлена
			</b>
		</div>
	</div>
</div><div class="modal modal-default yoga-subscription-success-modal" id="yoga-subscription-success-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="yoga-subscription-success-title">
	<button class="modal-close yoga-subscription-success-modal__close" type="button" aria-label="Закрыть"><svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg></button>
	<div class="yoga-subscription-success-modal__content">
		<img class="yoga-subscription-success-modal__icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/subscription-success-check.svg'); ?>" alt="">
		<h3 id="yoga-subscription-success-title">Подписка оформлена!<br>Обещаем отсутствие спама :)</h3>
	</div>
</div><div class="modal modal-default modal-default_formsucces">
	<button class="modal-close" type="button" aria-label="Закрыть">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg>
	</button>
	<div class="thanksforqw">
        <h3>
			Спасибо за Ваш вопрос
		</h3>
		<p>
			Вопрос будет опубликован в личном кабинете вместе с&nbsp;ответом
		</p>
	</div>
</div><?php if (is_page_template('templates-page/contacts.php')) : ?><div class="modal modal-default yoga-contact-success-modal" id="yoga-contact-success-modal" role="dialog" aria-modal="true" aria-labelledby="yoga-contact-success-title" aria-hidden="true">
	<button class="modal-close" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg>
	</button>
	<div class="yoga-contact-success-modal__content">
		<div class="yoga-contact-success-modal__heading">
			<img class="yoga-contact-success-modal__icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/comment-delete-success.svg'); ?>" width="77" height="77" alt="">
			<h3 id="yoga-contact-success-title"><?php esc_html_e('Мы получили ваше сообщение!', 'yoga'); ?></h3>
		</div>
		<p><?php esc_html_e('Вернёмся с ответом очень скоро.', 'yoga'); ?></p>
	</div>
</div><?php endif; ?><?php if (is_user_logged_in()) : ?>
<div class="modal modal-default lk-unsaved-changes-modal" id="lk-unsaved-changes-modal" role="dialog" aria-modal="true" aria-labelledby="lk-unsaved-changes-title" aria-describedby="lk-unsaved-changes-description" aria-hidden="true">
	<button class="modal-close lk-unsaved-changes-modal__cancel" type="button" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg>
	</button>
	<div class="lk-unsaved-changes-modal__content">
		<div class="lk-unsaved-changes-modal__copy">
			<h3 id="lk-unsaved-changes-title"><?php esc_html_e('Есть несохранённые изменения', 'yoga'); ?></h3>
			<p id="lk-unsaved-changes-description"><?php esc_html_e('Если закрыть страницу сейчас, изменения не сохранятся', 'yoga'); ?></p>
		</div>
		<div class="lk-unsaved-changes-modal__actions">
			<button class="lk-unsaved-changes-modal__leave" type="button"><?php esc_html_e('Закрыть', 'yoga'); ?></button>
			<button class="lk-unsaved-changes-modal__cancel" type="button"><?php esc_html_e('Отмена', 'yoga'); ?></button>
		</div>
	</div>
</div>
<?php endif; ?><div class="modal modal-default modal-default_logout">
	<div class="modal-close">
		<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?=get_template_directory_uri()?>/assets/svg/sprite.svg#lk-modal-close"></use></svg>
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
