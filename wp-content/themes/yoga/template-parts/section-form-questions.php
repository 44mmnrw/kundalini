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
$yoga_arrow_sprite_href = esc_url(add_query_arg(
    'ver',
    (string) filemtime(get_template_directory() . '/assets/svg/sprite.svg'),
    get_template_directory_uri() . '/assets/svg/sprite.svg'
));
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
                    <svg class="contacts-form-layout__decor-svg" viewBox="0 0 51 51" fill="none" focusable="false" aria-hidden="true">
                        <path fill="#9153E1" d="M24.5056 9.34588C24.6325 8.15361 26.3675 8.15361 26.4944 9.34588L27.8668 22.2446C27.9166 22.7132 28.2868 23.0834 28.7554 23.1332L41.6541 24.5056C42.8464 24.6325 42.8464 26.3675 41.6541 26.4944L28.7554 27.8668C28.2868 27.9166 27.9166 28.2868 27.8668 28.7554L26.4944 41.6541C26.3675 42.8464 24.6325 42.8464 24.5056 41.6541L23.1332 28.7554C23.0834 28.2868 22.7132 27.9166 22.2446 27.8668L9.34588 26.4944C8.15361 26.3675 8.15361 24.6325 9.34588 24.5056L22.2446 23.1332C22.7132 23.0834 23.0834 22.7132 23.1332 22.2446L24.5056 9.34588Z"></path>
                    </svg>
                </div>
                <div class="contacts-form-layout__decor contacts-form-layout__decor--star contacts-form-layout__decor--star-eight">
                    <svg class="contacts-form-layout__decor-svg contacts-form-layout__decor-svg--star" viewBox="0 0 51 51" fill="none" focusable="false" aria-hidden="true">
                        <path fill="#9153E1" d="M24.505 9.94062C24.6251 8.74009 26.3749 8.7401 26.495 9.94063L27.4503 19.4837C27.5247 20.2277 28.3596 20.6298 28.9878 20.2241L37.0444 15.021C38.058 14.3664 39.1489 15.7344 38.2852 16.5768L31.4197 23.2736C30.8844 23.7958 31.0907 24.6992 31.7995 24.9374L40.8907 27.9922C42.0344 28.3765 41.645 30.0824 40.4478 29.9324L30.9315 28.7401C30.1896 28.6472 29.6118 29.3717 29.8675 30.0744L33.1474 39.0868C33.5601 40.2206 31.9836 40.9797 31.3545 39.9502L26.3533 31.7667C25.9633 31.1287 25.0367 31.1287 24.6467 31.7667L19.6455 39.9502C19.0164 40.9797 17.4399 40.2206 17.8526 39.0868L21.1325 30.0744C21.3882 29.3717 20.8104 28.6472 20.0685 28.7401L10.5521 29.9324C9.35497 30.0824 8.96563 28.3765 10.1093 27.9922L19.2005 24.9374C19.9093 23.7958 20.1156 24.6992 19.5803 23.2736L12.7148 16.5768C11.8511 15.7344 12.942 14.3664 13.9556 15.021L22.0122 20.2241C22.6404 20.6298 23.4753 20.2277 23.5497 19.4837L24.505 9.94062Z"></path>
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
                                   placeholder="Эл. почта">

                        </div>

                        <div class="form-questions-textarea contacts-form-layout__textarea">
                            <textarea name="contacts_message" placeholder="<?php echo esc_attr(get_field('contacts_placeholder_message', 'option') ?: 'Ваш вопрос'); ?>" required class="input"></textarea>

                            <button type="submit" class="btn contacts-form-layout__submit" aria-label="<?php esc_attr_e('Отправить сообщение', 'yoga'); ?>">
                                <svg class="contacts-form-layout__submit-arrow" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><use href="<?php echo $yoga_arrow_sprite_href; ?>#site-arrow-green"></use></svg>
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
