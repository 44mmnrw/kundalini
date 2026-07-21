<?php
/**
 * Переиспользуемый шаблонный блок: section form questions.
 *
 * @package Yoga
 */
$contacts_prefill_name = '';
$contacts_prefill_email = '';

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $contacts_prefill_name = (string) ($current_user->display_name ?? '');
    $contacts_prefill_email = (string) ($current_user->user_email ?? '');
}

$yoga_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
?>
<section class="section-form-questions section-form-questions_contacts" id="section-form-questions">
    <div class="container">
        <div class="contacts-form-layout">
            <div class="contacts-form-layout__intro form-questions__main-text">
                <h3><?php echo esc_html(get_field('contacts_title', 'option') ?: 'Мы на связи!'); ?></h3>
                <p><?php echo esc_html(get_field('contacts_description', 'option') ?: 'Если у вас есть вопросы или нужна помощь, оставьте сообщение, мы вам ответим в ближайшее время.'); ?></p>
            </div>

            <div class="contacts-form-layout__scene form-questions" aria-hidden="false">
                <div class="contacts-form-layout__decor contacts-form-layout__decor--star-four">
                    <svg class="contacts-form-layout__decor-svg" focusable="false" aria-hidden="true">
                        <use href="<?php echo $yoga_sprite_href; ?>#contacts-star-four"></use>
                    </svg>
                </div>
                <div class="contacts-form-layout__decor contacts-form-layout__decor--star contacts-form-layout__decor--star-eight">
                    <svg class="contacts-form-layout__decor-svg contacts-form-layout__decor-svg--star" focusable="false" aria-hidden="true">
                        <use href="<?php echo $yoga_sprite_href; ?>#contacts-star-eight"></use>
                    </svg>
                </div>
                <div class="contacts-form-layout__decor contacts-form-layout__decor--oval">
                    <svg class="contacts-form-layout__decor-svg" focusable="false" aria-hidden="true">
                        <use href="<?php echo $yoga_sprite_href; ?>#contacts-decor-oval"></use>
                    </svg>
                </div>

                <div class="contacts-form-layout__badge" aria-hidden="true">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/contacts-form-envelope.svg'); ?>" alt="">
                </div>

                <div class="contacts-form-layout__panel form-questions__main">
                    <form action="#" class="form-questions__main-form contacts-form contacts-form-layout__form" method="post">
                        <?php wp_nonce_field('contacts_nonce', 'contacts_nonce_field'); ?>

                        <div class="contacts-form-layout__row contacts-form-layout__row--inputs">
                            <input type="text" name="contacts_name" class="input" required value="<?php echo esc_attr($contacts_prefill_name); ?>"<?php echo $contacts_prefill_name !== '' ? ' readonly aria-readonly="true"' : ''; ?>
                                   placeholder="<?php echo esc_attr(get_field('contacts_placeholder_name', 'option') ?: 'Имя'); ?>">

                            <input type="email" name="contacts_email" class="input" required value="<?php echo esc_attr($contacts_prefill_email); ?>"<?php echo $contacts_prefill_email !== '' ? ' readonly aria-readonly="true"' : ''; ?>
                                   placeholder="эл. почта">

                        </div>

                        <div class="form-questions-textarea contacts-form-layout__textarea">
                            <textarea name="contacts_message" placeholder="<?php echo esc_attr(get_field('contacts_placeholder_message', 'option') ?: 'Ваш вопрос'); ?>" required class="input"></textarea>

                            <button type="submit" class="btn contacts-form-layout__submit" aria-label="<?php esc_attr_e('Отправить сообщение', 'yoga'); ?>">
                                <svg class="contacts-form-layout__submit-arrow" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="contacts-social contacts-form-layout__social">
                <?php
                $social_links = get_field('contacts_social_links', 'option');
                if ($social_links) :
                    foreach ($social_links as $social) :
                        $social_url_raw = isset($social['social_url']) ? trim((string) $social['social_url']) : '';
                        $social_alt_raw = isset($social['social_alt']) ? strtolower((string) $social['social_alt']) : '';
                        $is_mail_item = stripos($social_url_raw, 'mailto:') === 0
                            || strpos($social_alt_raw, 'mail') !== false
                            || strpos($social_alt_raw, 'почт') !== false
                            || strpos($social_alt_raw, 'e-mail') !== false
                            || strpos($social_alt_raw, 'email') !== false;
                        $item_modifier = $is_mail_item ? ' contacts-social__item--mail' : '';
                        ?>
                        <a href="<?php echo esc_url($social['social_url']); ?>" class="contacts-social__item<?php echo esc_attr($item_modifier); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($social['social_icon']); ?>" alt="<?php echo esc_attr($social['social_alt']); ?>">
                        </a>
                    <?php
                    endforeach;
                else :
                    ?>
                    <a href="https://t.me/" class="contacts-social__item contacts-social__item--sprite" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
                        <svg viewBox="0 0 36 36" focusable="false" aria-hidden="true">
                            <use href="<?php echo $yoga_sprite_href; ?>#social-telegram"></use>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/" class="contacts-social__item contacts-social__item--sprite" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                        <svg viewBox="0 0 36 36" focusable="false" aria-hidden="true">
                            <use href="<?php echo $yoga_sprite_href; ?>#social-youtube"></use>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
