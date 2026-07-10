<?php
/**
 * Login Modal Template — вход и регистрация по почте
 */
$terms_url = get_permalink(get_page_by_path('terms'));
$privacy_url = get_permalink(get_page_by_path('privacy'));
if (!$terms_url) $terms_url = home_url('/terms/');
if (!$privacy_url) $privacy_url = home_url('/privacy/');
$legal_option = static function($key) {
    return function_exists('get_field') ? trim((string) get_field($key, 'option')) : '';
};
$public_offer_url = $legal_option('public_offer_link') ?: $terms_url;
$personal_data_url = $legal_option('personal_data_processing_link') ?: $legal_option('personal_data_link') ?: $privacy_url;
$contraindications_url = $legal_option('contraindications_link') ?: $legal_option('disclaimer_link') ?: $privacy_url;
$img_uri = get_template_directory_uri() . '/assets/img';
$yoga_smart_captcha = function_exists('yoga_smartcaptcha_is_enforced') && yoga_smartcaptcha_is_enforced();
$yoga_sc_sitekey = ($yoga_smart_captcha && function_exists('yoga_smartcaptcha_client_key')) ? yoga_smartcaptcha_client_key() : '';
?><div class="modal-login">
    <div class="modal-close">
        <img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
    </div>
    <button type="button" class="password-recovery-back ml-sl-switch" data-target="1" aria-label="Вернуться ко входу">
        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20">
            <path d="M7 18L1 10.5L7 3M1 10.5H19" fill="none" stroke="#1F1F1F" stroke-width="1.5" stroke-linecap="square"></path>
        </svg>
    </button>
    <div class="modal-login-inner">
        <div class="modal-login-inner__slide active" data-target="1">
            <div class="login-slide-switches login-slide-switches_login">
                <h3>
                    Вход
                </h3>
            </div>
            <form action="#" class="form yoga-form-login" method="post">
                <?php wp_nonce_field('yoga_login_nonce', 'yoga_login_nonce'); ?>
                <input type="hidden" name="action" value="yoga_email_login">
                <input type="email" name="log" class="input" required placeholder="Электронная почта">
                <p class="yoga-form-login-message" role="alert" aria-live="polite"></p>
                <div class="input-password">
                    <input type="password" name="pwd" class="input" required placeholder="Пароль">
                        <div class="input-password__btn input-password__btn_show active">
                            <svg aria-hidden="true" focusable="false">
                            <use href="#password-eye-open"></use>
                            </svg>
						</div>
                        <div class="input-password__btn input-password__btn_hide">
                            <svg aria-hidden="true" focusable="false">
                            <use href="#password-eye-closed"></use>
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
            <a href="<?php echo esc_url(home_url('/?oauth=vk')); ?>" class="ref form-link form-link_vk login-vk-link">
                Войти через VK
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
                <input type="email" name="user_email" class="input" required placeholder="Электронная почта">
                <div class="input-password">
                    <input type="password" name="user_pass" class="input" required placeholder="Пароль">
                        <div class="input-password__btn input-password__btn_show active">
                            <svg aria-hidden="true" focusable="false">
                            <use href="#password-eye-open"></use>
                            </svg>
						</div>
                        <div class="input-password__btn input-password__btn_hide">
                            <svg aria-hidden="true" focusable="false">
                            <use href="#password-eye-closed"></use>
                            </svg>
						</div>
                </div>
                <div class="registration-consents">
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_terms" value="1" required>
                        <span>Я ознакомлен(а) с <a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener">Пользовательским соглашением</a> и <a href="<?php echo esc_url($public_offer_url); ?>" target="_blank" rel="noopener">публичной офертой</a>, <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener">Политикой конфиденциальности</a></span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_personal_data" value="1" required>
                        <span>Я даю согласие на <a href="<?php echo esc_url($personal_data_url); ?>" target="_blank" rel="noopener">обработку персональных данных</a></span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_contraindications" value="1" required>
                        <span>Я подтверждаю, что ознакомлен(а) с <a href="<?php echo esc_url($contraindications_url); ?>" target="_blank" rel="noopener">информацией о противопоказаниях и отказом от ответственности</a>.</span>
                    </label>
                    <label class="registration-consent">
                        <input type="checkbox" name="accept_marketing" value="1">
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
                <a href="<?php echo esc_url(home_url('/?oauth=vk')); ?>" class="ref form-link form-link_vk registration-vk-link">
                    Войти через VK
                </a>
                <div class="loggin-back loggin-back_mob loggin-back_grey">
                    <span class="loggin-back__link ml-sl-switch" data-target="1">
                        Войти
                    </span>
                </div>
            </form>
        </div>
        <div class="modal-login-inner__slide" data-target="3">
            <div class="login-slide-switches">
                <h3>
                    Восстановление пароля
                </h3>
            </div>
            <p>
                Введите e-mail, который вы использовали при регистрации
            </p>
            <form action="#" class="form form_recovery yoga-form-recovery" method="post">
                <?php wp_nonce_field('yoga_recovery_nonce', 'yoga_recovery_nonce'); ?>
                <input type="hidden" name="action" value="yoga_lost_password">
                <input type="email" name="user_login" class="input" required placeholder="Электронная почта">
                <?php if ($yoga_smart_captcha) : ?>
                <div class="login-smartcaptcha yoga-smart-captcha-mount smart-captcha" data-sitekey="<?php echo esc_attr($yoga_sc_sitekey); ?>" data-hl="ru"></div>
                <?php endif; ?>
                <button type="submit" id="recovery-btn"></button>
                <label for="recovery-btn" class="btn">
                    <span>
                        Восстановить пароль
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
                Мы отправили подтверждение сброса пароля на вашу электронную почту. Перейдите по ссылке в письме, чтобы продолжить.
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
