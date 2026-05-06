<section class="section-faq" id="section-faq">
    <?php
    $faq_prefill_name = '';
    $faq_prefill_email = '';

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $faq_prefill_name = (string) ($current_user->display_name ?? '');
        $faq_prefill_email = (string) ($current_user->user_email ?? '');
    }
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
                                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next.png" alt="" class="active">
                                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next_active.png" alt="">
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
                        <div class="form-questions__main">
                            <div class="form-questions__main-text">
                                <h4>
                                    <?php echo esc_html(get_field('faq_form_title') ?: 'Не нашли ответа на интересующий вас вопрос?'); ?>
                                </h4>
                                <p>
                                    <?php echo esc_html(get_field('faq_form_description') ?: 'Вы всегда можете задать нам ваш вопрос и получить ответ на e-mail'); ?>
                                </p>
                            </div>
                            <form action="#" class="form-questions__main-form" id="faqContactForm">
                                <?php wp_nonce_field('faq_contact_nonce', 'faq_nonce'); ?>
                                
                                <input type="text" name="name" class="input" required value="<?php echo esc_attr($faq_prefill_name); ?>"
                                       placeholder="<?php echo esc_attr(get_field('faq_form_placeholder_name') ?: 'Имя'); ?>">
                                
                                <input type="email" name="email" class="input" required value="<?php echo esc_attr($faq_prefill_email); ?>"
                                       placeholder="<?php echo esc_attr(get_field('faq_form_placeholder_email') ?: 'E-mail'); ?>">
                                
                                <div class="form-questions-textarea">
                                    <textarea name="message" placeholder="<?php echo esc_attr(get_field('faq_form_placeholder_question') ?: 'Ваш вопрос'); ?>" required class="input"></textarea>
                                    
                                    <input type="submit" id="faq-form-submit" style="display: none;">
                                    
                                    <?php 
                                    $btn_icon = get_field('faq_form_btn_icon');
                                    if ($btn_icon) : ?>
                                        <label for="faq-form-submit" class="btn">
                                            <img src="<?php echo esc_url($btn_icon); ?>" alt="Отправить">
                                        </label>
                                    <?php else : ?>
                                        <label for="faq-form-submit" class="btn">
                                            <span>→</span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>