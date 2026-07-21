<?php
/**
 * Переиспользуемый шаблонный блок: section faq.
 *
 * @package Yoga
 */
?>
<section class="section-faq" id="section-faq">
    <?php
    $faq_prefill_name = '';
    $faq_prefill_email = '';

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $faq_prefill_name = (string) ($current_user->display_name ?? '');
        $faq_prefill_email = (string) ($current_user->user_email ?? '');
    }
    $faq_sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
    $faq_arrow_sprite_href = esc_url(add_query_arg(
        'ver',
        (string) filemtime(get_template_directory() . '/assets/svg/sprite.svg'),
        get_template_directory_uri() . '/assets/svg/sprite.svg'
    ));
    ?>
    <div class="container">
        <div class="row">
            <div class="faq">
                <div class="faq__questions">
                    <div class="questions-answers">
                        <div class="questions-items">
                            <?php
                            $faq_items = get_field('faq_items');
                            if ($faq_items) :
                                $counter = 1;
                                foreach ($faq_items as $item) :
                                    $question = $item['question'];
                                    $answer = $item['answer'];
                                    ?>
                                    <div class="question animated fadeIn delay-200ms">
                                        <div class="question__main">
                                            <span class="question__main-text">
                                                <span><?php echo str_pad($counter, 2, '0', STR_PAD_LEFT); ?>.</span>
                                                <?php echo esc_html($question); ?>
                                            </span>
                                            <div class="question-icon">
                                                <svg class="question-icon__arrow" aria-hidden="true" focusable="false"><use href="<?php echo $faq_sprite_href; ?>#site-arrow"></use></svg>
                                            </div>
                                        </div>
                                        <div class="question__sub">
                                            <?php echo apply_filters('the_content', $answer); ?>
                                        </div>
                                    </div>
                                    <?php
                                    $counter++;
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
                <div class="faq__form">
                    <div class="form-questions animated fadeIn delay-200ms">
                        <div class="faq__form-card-bg" aria-hidden="true"></div>
                        <div class="form-questions__badge faq__form-badge" aria-hidden="true">
                            <svg class="form-questions__badge-svg faq__form-badge-svg" viewBox="0 0 48 48" focusable="false">
                                <use href="<?php echo $faq_sprite_href; ?>#contacts-form-badge"></use>
                            </svg>
                        </div>
                        <div class="form-questions__main">
                            <div class="form-questions__main-text">
                                <h4>
                                    <?php echo esc_html(get_field('faq_form_title') ?: 'Не нашли ответа на интересующий вас вопрос?'); ?>
                                </h4>
                                <p>
                                    <?php echo esc_html(get_field('faq_form_description') ?: 'Вы всегда можете задать нам ваш вопрос и получить ответ на эл. почту'); ?>
                                </p>
                            </div>
                            <form action="#" class="form-questions__main-form" id="faqContactForm">
                                <?php wp_nonce_field('faq_contact_nonce', 'faq_nonce'); ?>

                                <input type="text" name="name" class="input" required value="<?php echo esc_attr($faq_prefill_name); ?>"<?php echo $faq_prefill_name !== '' ? ' readonly' : ''; ?>
                                       placeholder="<?php echo esc_attr(get_field('faq_form_placeholder_name') ?: 'Имя'); ?>">

                                <input type="email" name="email" class="input" required value="<?php echo esc_attr($faq_prefill_email); ?>"<?php echo $faq_prefill_email !== '' ? ' readonly' : ''; ?>
                                       placeholder="эл. почта">

                                <div class="form-questions-textarea">
                                    <textarea name="message" placeholder="<?php echo esc_attr(get_field('faq_form_placeholder_question') ?: 'Ваш вопрос'); ?>" required class="input"></textarea>

                                    <input type="submit" id="faq-form-submit" style="display: none;">

                                    <label for="faq-form-submit" class="btn" aria-label="<?php esc_attr_e('Отправить вопрос', 'yoga'); ?>">
                                        <svg class="faq__form-submit-arrow" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                                            <use href="<?php echo $faq_arrow_sprite_href; ?>#site-arrow-green"></use>
                                        </svg>
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
