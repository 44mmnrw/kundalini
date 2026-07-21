<?php
/**
 * Переиспользуемый шаблонный блок: section question success.
 *
 * @package Yoga
 */
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
			<div class="question-success__shape">
				<div class="question-success__envelope">
					<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/question-success-envelope.svg'); ?>" alt="">
				</div>
				<div class="question-success__decor question-success__decor--oval">
					<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/question-success-ellipse.svg'); ?>" alt="">
				</div>
				<div class="question-success__decor question-success__decor--star-four">
					<svg viewBox="0 0 51 51" fill="none" focusable="false"><path fill="#9153E1" d="M24.5056 9.34588C24.6325 8.15361 26.3675 8.15361 26.4944 9.34588L27.8668 22.2446C27.9166 22.7132 28.2868 23.0834 28.7554 23.1332L41.6541 24.5056C42.8464 24.6325 42.8464 26.3675 41.6541 26.4944L28.7554 27.8668C28.2868 27.9166 27.9166 28.2868 27.8668 28.7554L26.4944 41.6541C26.3675 42.8464 24.6325 42.8464 24.5056 41.6541L23.1332 28.7554C23.0834 28.2868 22.7132 27.9166 22.2446 27.8668L9.34588 26.4944C8.15361 26.3675 8.15361 24.6325 9.34588 24.5056L22.2446 23.1332C22.7132 23.0834 23.0834 22.7132 23.1332 22.2446L24.5056 9.34588Z"></path></svg>
				</div>
				<div class="question-success__decor question-success__decor--star-eight">
					<svg viewBox="0 0 51 51" focusable="false"><use href="<?php echo $sprite_url; ?>#contacts-star-eight"></use></svg>
				</div>
				<div class="question-success__content">
					<div class="question-success__text">
						<h1 id="question-success-title">Мы получили ваш вопрос!</h1>
						<p>Ответим в ближайшее время на указанную эл. почту, так же вы найдёте ответ в <a href="<?php echo esc_url($account_url); ?>">личном кабинете</a>.</p>
					</div>
					<a class="question-success__button btn" href="<?php echo esc_url($faq_url); ?>">Спросить ещё</a>
                </div>
            </div>
        </div>
    </div>
</section>
