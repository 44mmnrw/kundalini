<?php
/**
 * Login Modal Template — вход и регистрация по почте
 */
$terms_url = get_permalink(get_page_by_path('terms'));
$privacy_url = get_permalink(get_page_by_path('privacy'));
if (!$terms_url) $terms_url = home_url('/terms/');
if (!$privacy_url) $privacy_url = home_url('/privacy/');
$img_uri = get_template_directory_uri() . '/assets/img';
$recaptcha_key = get_option('recaptcha_site_key', '');
?>
<div class="modal-login">
    <div class="modal-close">
        <img src="<?php echo esc_url($img_uri . '/modal-close-img.png'); ?>" alt="">
    </div>
    <div class="modal-login-inner">
        <div class="modal-login-inner__slide active" data-target="1">
            <div class="login-slide-switches">
                <h3>
                    Вход
                </h3>
                <span class="login-switch-link ml-sl-switch" data-target="2">
                    Регистрация
                </span>
            </div>
            <form action="#" class="form yoga-form-login" method="post">
                <?php wp_nonce_field('yoga_login_nonce', 'yoga_login_nonce'); ?>
                <input type="hidden" name="action" value="yoga_email_login">
                <input type="email" name="log" class="input" required placeholder="Электронная почта">
                <input type="password" name="pwd" class="input" required placeholder="Пароль">
                <button type="submit" id="login-ent-btn"></button>
                <label for="login-ent-btn" class="btn">
                    <span>
                        войти
                    </span>
                </label>
                <?php if (shortcode_exists('vkid_login')) : ?>
                    <?php echo do_shortcode('[vkid_login]'); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/?oauth=vk')); ?>" class="ref form-link form-link_vk">
                        Войти через VK
                    </a>
                <?php endif; ?>
                <div class="loggin-additional">
                    <div class="loggin-additional__item ml-sl-switch" data-target="3">
                        <span>
                            Забыли пароль?
                        </span>
                    </div>
                </div>
                <div class="loggin-back loggin-back_mob">
                    <span class="loggin-back__link ml-sl-switch" data-target="2">
                        Регистрация
                    </span>
                </div>
            </form>
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
                <input type="password" name="user_pass" class="input" required placeholder="Пароль">
                <button type="submit" id="login-reg-btn"></button>
                <p class="login-rules">
                    Нажимая «Зарегистрироваться», вы принимаете условия <a href="<?php echo esc_url($terms_url); ?>">пользовательского соглашения</a> и <a href="<?php echo esc_url($privacy_url); ?>">политики конфиденциальности</a>
                </p>
                <?php /* Axecode.tech: reCAPTCHA disabled by request. Author: ChatGPT Codex. Date: 2026-05-01. */
                /*
                <div class="login-captcha">
                    <?php if ($recaptcha_key) : ?>
                        <div id="recaptcha-register" class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_key); ?>"></div>
                    <?php else : ?>
                        <img src="<?php echo esc_url($img_uri . '/login-captcha.png'); ?>" alt="" class="login-captcha__plug">
                    <?php endif; ?>
                </div>
                */ ?>
                <label for="login-reg-btn" class="btn">
                    <span>
                        зарегистрироваться
                    </span>
                </label>
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
                <button type="submit" id="recovery-btn"></button>
                <?php /* Axecode.tech: reCAPTCHA disabled by request. Author: ChatGPT Codex. Date: 2026-05-01. */
                /*
                <div class="login-captcha">
                    <?php if ($recaptcha_key) : ?>
                        <div id="recaptcha-recovery" class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_key); ?>"></div>
                    <?php else : ?>
                        <img src="<?php echo esc_url($img_uri . '/login-captcha.png'); ?>" alt="" class="login-captcha__plug">
                    <?php endif; ?>
                </div>
                */ ?>
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
                Мы отправили подтверждение сброса пароля на ваш e-mail. Перейдите по ссылке в письме, чтобы продолжить.
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
