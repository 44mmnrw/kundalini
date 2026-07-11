<?php
$sprite_url = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
$faq_pages = get_posts(array(
    'post_type'      => 'page',
    'posts_per_page' => 1,
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'templates-page/faq.php',
));
$faq_url = !empty($faq_pages) ? get_permalink($faq_pages[0]) : home_url('/faq/');
$account_url = get_permalink(get_page_by_path('my-account')) ?: home_url('/my-account/');
?>
<section class="section-question-success" aria-labelledby="question-success-title">
    <div class="container">
        <div class="question-success">
            <div class="question-success__decor question-success__decor--oval" aria-hidden="true">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/question-success-ellipse.svg'); ?>" alt="">
            </div>
            <div class="question-success__decor question-success__decor--star-four" aria-hidden="true">
                <svg viewBox="0 0 51 51" focusable="false"><use href="<?php echo $sprite_url; ?>#contacts-star-four"></use></svg>
            </div>
            <div class="question-success__decor question-success__decor--star-eight" aria-hidden="true">
                <svg viewBox="0 0 51 51" focusable="false"><use href="<?php echo $sprite_url; ?>#contacts-star-eight"></use></svg>
            </div>
            <div class="question-success__envelope" aria-hidden="true">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/question-success-envelope.svg'); ?>" alt="">
            </div>

            <div class="question-success__content">
                <div class="question-success__text">
                    <h1 id="question-success-title">Мы получили ваш вопрос!</h1>
                    <p>Ответим в ближайшее время на указанный e-mail, так же вы найдёте ответ в <a href="<?php echo esc_url($account_url); ?>">личном кабинете</a>.</p>
                </div>
                <a class="question-success__button btn" href="<?php echo esc_url($faq_url); ?>">Спросить ещё</a>
            </div>
        </div>
    </div>
</section>
