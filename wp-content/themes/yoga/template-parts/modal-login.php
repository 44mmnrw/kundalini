<?php
/**
 * Login Modal Template — вход и регистрация по почте
 */
$legal_option = static function($key) {
    return function_exists('get_field') ? trim((string) get_field($key, 'option')) : '';
};
$terms_url = function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url('user_agreement', $legal_option('user_agreement_link')) : $legal_option('user_agreement_link');
$privacy_url = function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url('privacy_policy', $legal_option('privacy_policy_link')) : $legal_option('privacy_policy_link');
if ($terms_url === '') $terms_url = get_permalink(get_page_by_path('terms')) ?: home_url('/terms/');
if ($privacy_url === '') $privacy_url = get_permalink(get_page_by_path('privacy')) ?: home_url('/privacy/');
$public_offer_url = function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url('public_offer', $legal_option('public_offer_link') ?: $terms_url) : ($legal_option('public_offer_link') ?: $terms_url);
$personal_data_url = function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url('personal_data', $legal_option('personal_data_processing_link') ?: $legal_option('personal_data_link') ?: $privacy_url) : ($legal_option('personal_data_processing_link') ?: $legal_option('personal_data_link') ?: $privacy_url);
$contraindications_url = function_exists('yoga_get_legal_document_url') ? yoga_get_legal_document_url('contraindications', $legal_option('contraindications_link') ?: $legal_option('disclaimer_link') ?: $privacy_url) : ($legal_option('contraindications_link') ?: $legal_option('disclaimer_link') ?: $privacy_url);
$img_uri = get_template_directory_uri() . '/assets/img';
$yoga_smart_captcha = function_exists('yoga_smartcaptcha_is_enforced') && yoga_smartcaptcha_is_enforced();
$yoga_sc_sitekey = ($yoga_smart_captcha && function_exists('yoga_smartcaptcha_client_key')) ? yoga_smartcaptcha_client_key() : '';
?><div class="modal-login">
    <button type="button" class="password-recovery-back ml-sl-switch" data-target="1" aria-label="Вернуться ко входу">
        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20">
            <path d="M7 18L1 10.5L7 3M1 10.5H19" fill="none" stroke="#1F1F1F" stroke-width="1.5" stroke-linecap="square"></path>
        </svg>
    </button>
    <div class="modal-login-inner">
        <div class="modal-close">
			<svg class="modal-close__icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use></svg>
        </div>
        <div class="modal-login-inner__slide active" data-target="1">
            <div class="login-slide-switches login-slide-switches_login">
                <h3>
                    Вход
                </h3>
            </div>
            <form action="#" class="form yoga-form-login" method="post">
                <?php wp_nonce_field('yoga_login_nonce', 'yoga_login_nonce'); ?>
                <input type="hidden" name="action" value="yoga_email_login">
                <input type="email" name="log" class="input" required placeholder="эл. почта">
                <p class="yoga-form-login-message" role="alert" aria-live="polite"></p>
                <div class="input-password">
                    <input type="password" name="pwd" class="input" required placeholder="Пароль">
                        <div class="input-password__btn input-password__btn_show active">
                            <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-open'); ?>"></use>
                            </svg>
						</div>
                        <div class="input-password__btn input-password__btn_hide">
                            <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-closed'); ?>"></use>
                            </svg>
						</div>
                </div>
                <?php if ($yoga_smart_captcha) : ?>
                <div class="login-smartcaptcha yoga-smart-captcha-mount smart-captcha" data-sitekey="<?php echo esc_attr($yoga_sc_sitekey); ?>" data-hl="ru"></div>
                <?php endif; ?>
                <button type="submit" id="login-ent-btn"></button>
                <label for="login-ent-btn" class="btn">
                    <span>
                        войти
                    </span>
                </label>
                <div class="login-form-links">
                    <a href="#" class="ml-sl-switch" data-target="3">Забыли пароль?</a>
                    <span class="login-switch-link ml-sl-switch" data-target="2">Регистрация</span>
                </div>
            </form>
            <a href="#" class="ref form-link form-link_vk login-vk-link vkid-login-trigger">
                <span class="vkid-login-trigger__text">Войти через VK</span>
                <svg class="vkid-login-trigger__icon" aria-hidden="true" focusable="false">
                    <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#vk-login-logo'); ?>"></use>
                </svg>
            </a>
        </div>
        <div class="modal-login-inner__slide" data-target="2">
            <div class="login-slide-switches">
                <h3>
                    Регистрация
                </h3>
                <span class="login-switch-link ml-sl-switch" data-target="1">
                    Вход
                </span>
            </div>
            <form action="#" class="form yoga-form-register" method="post">
                <?php wp_nonce_field('yoga_register_nonce', 'yoga_register_nonce'); ?>
                <input type="hidden" name="action" value="yoga_email_register">
                <input type="text" name="user_name" class="input" required placeholder="Ваше имя">
                <input type="email" name="user_email" class="input" required placeholder="эл. почта">
                <div class="input-password">
                    <input type="password" name="user_pass" class="input" required placeholder="Пароль">
                        <div class="input-password__btn input-password__btn_show active">
                            <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-open'); ?>"></use>
                            </svg>
						</div>
                        <div class="input-password__btn input-password__btn_hide">
                            <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-closed'); ?>"></use>
                            </svg>
						</div>
                </div>
                <div class="registration-consents">
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_terms" value="1" required checked>
                        <span>Я ознакомлен(а) с <a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener">Пользовательским соглашением</a> и <a href="<?php echo esc_url($public_offer_url); ?>" target="_blank" rel="noopener">публичной офертой</a>, <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener">Политикой конфиденциальности</a></span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_personal_data" value="1" required checked>
                        <span>Я даю согласие на <a href="<?php echo esc_url($personal_data_url); ?>" target="_blank" rel="noopener">обработку персональных данных</a></span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_contraindications" value="1" required checked>
                        <span>Я подтверждаю, что ознакомлен(а) с <a href="<?php echo esc_url($contraindications_url); ?>" target="_blank" rel="noopener">информацией о противопоказаниях и отказом от ответственности</a>.</span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_marketing" value="1" checked>
                        <span>Согласен(а) на получение рекламы и информации. Отказаться можно в любой момент.</span>
                    </label>
                </div>
                <button type="submit" id="login-reg-btn"></button>
                <?php if ($yoga_smart_captcha) : ?>
                <div class="login-smartcaptcha yoga-smart-captcha-mount smart-captcha" data-sitekey="<?php echo esc_attr($yoga_sc_sitekey); ?>" data-hl="ru"></div>
                <?php endif; ?>
                <label for="login-reg-btn" class="btn">
                    <span>
                        зарегистрироваться
                    </span>
                </label>
            </form>
            <span class="login-switch-link login-switch-link--mobile ml-sl-switch" data-target="1">Войти</span>
            <a href="#" class="ref form-link form-link_vk registration-vk-link vkid-login-trigger">
                <span class="vkid-login-trigger__text">Войти через VK</span>
                <svg class="vkid-login-trigger__icon" aria-hidden="true" focusable="false">
                    <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#vk-login-logo'); ?>"></use>
                </svg>
            </a>
        </div>
        <div class="modal-login-inner__slide" data-target="3">
            <div class="login-slide-switches">
                <h3>
                    Восстановление пароля
                </h3>
            </div>
            <p>
                Введите эл. почту, которую вы использовали при регистрации
            </p>
            <form action="#" class="form form_recovery yoga-form-recovery" method="post">
                <?php wp_nonce_field('yoga_recovery_nonce', 'yoga_recovery_nonce'); ?>
                <input type="hidden" name="action" value="yoga_lost_password">
                <input type="email" name="user_login" class="input" required placeholder="эл. почта">
                <?php if ($yoga_smart_captcha) : ?>
                <div class="login-smartcaptcha yoga-smart-captcha-mount smart-captcha" data-sitekey="<?php echo esc_attr($yoga_sc_sitekey); ?>" data-hl="ru"></div>
                <?php endif; ?>
                <button type="submit" id="recovery-btn"></button>
                <label for="recovery-btn" class="btn">
                    <span>
                        Восстановить
                    </span>
                </label>
                <div class="loggin-back">
                    <span class="loggin-back__link ml-sl-switch" data-target="2">
                        Регистрация
                    </span>
                    <span class="loggin-back__link ml-sl-switch" data-target="1">
                        Вход
                    </span>
                </div>
            </form>
        </div>
        <div class="modal-login-inner__slide modal-login-inner__slide_succes" data-target="4">
            <p>
                Мы отправили подтверждение сброса пароля на вашу эл. почту. Перейдите по ссылке в письме, чтобы продолжить.
            </p>
            <b class="login-notification">
                Нет письма? Проверьте папку Спам.
            </b>
            <form action="#" class="form">
                <div class="btn ml-sl-switch" data-target="1">
                    <span>
                        Войти
                    </span>
                </div>
                <div class="loggin-back">
                    <span class="loggin-back__link ml-sl-switch" data-target="2">
                        Регистрация
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="email-confirmation-overlay" aria-hidden="true">
    <div class="email-confirmation-modal" role="dialog" aria-modal="true" aria-labelledby="email-confirmation-title">
        <button type="button" class="email-confirmation-modal__close" aria-label="Закрыть">
            <svg aria-hidden="true" focusable="false">
				<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-modal-close'); ?>"></use>
            </svg>
        </button>
                <h3 id="email-confirmation-title">Подтверждение<br>электронной почты</h3>
                <div class="email-confirmation-modal__description">
                    <p>На указанный вами email <strong class="email-confirmation-modal__email"></strong> отправлено письмо с кодом подтверждения.</p>
                    <p>Введите код ниже, чтобы завершить проверку доступа к электронной почте.</p>
                </div>
                <form class="email-confirmation-modal__form" action="#" method="post">
                    <label for="email-confirmation-code">Код подтверждения:</label>
                    <input id="email-confirmation-code" class="email-confirmation-modal__code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" aria-describedby="email-confirmation-message">
                    <button type="button" class="email-confirmation-modal__resend">Отправить код повторно</button>
                    <p id="email-confirmation-message" class="email-confirmation-modal__message" role="status" aria-live="polite"></p>
                    <div class="email-confirmation-modal__buttons">
                        <button type="button" class="email-confirmation-modal__cancel">Отменить</button>
                        <button type="submit" class="email-confirmation-modal__confirm">Подтвердить</button>
                    </div>
                </form>
    </div>
</div>
