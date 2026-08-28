<?php
/**
 * Переиспользуемый шаблонный блок: section reviews.
 *
 * @package Yoga
 */
$reviews_title = get_field('reviews_title', get_the_ID()) ?: 'отзывы';
$reviews_items = isset($args['items']) && is_array($args['items']) ? $args['items'] : array();
$review_people = isset($args['people']) && is_array($args['people']) ? $args['people'] : array();
$show_review_people_photos = !empty($args['show_photos']);
$videos_hidden = !empty($args['videos_hidden']);
$section_classes = array('section-reviews');
if (!$show_review_people_photos) {
    $section_classes[] = 'section-reviews_photos-hidden';
}
if ($videos_hidden) {
    $section_classes[] = 'section-reviews_without-videos';
}
if (count($reviews_items) <= 1) {
    $section_classes[] = 'section-reviews_single';
}
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="section-reviews">
    <div class="container">
        <div class="row">
            <div class="reviews">
                <div class="reviews__head">
                    <button type="button" class="arrows-slick__arrow slick-prev wow fadeIn delay-200ms" aria-label="Предыдущий отзыв">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                    <h2 class="wow flipInX delay-200ms">
                        <?php echo esc_html($reviews_title); ?>
                    </h2>
                    <button type="button" class="arrows-slick__arrow slick-next wow fadeIn delay-200ms" aria-label="Следующий отзыв">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                </div>

                <div class="reviews__main">
                    <div class="reviews-slider">
                        <?php foreach ($reviews_items as $review) :
                            $review_image = $review['review_image'] ?? '';
                            $review_name = $review['review_name'] ?? '';
                            $review_age = $review['review_age'] ?? '';
                            $review_job = $review['review_job'] ?? '';
                            $review_excerpt = $review['review_excerpt'] ?? '';
                            $review_full_text = $review['review_full_text'] ?? '';
                            $review_animation = $review['review_animation'] ?? 'wow fadeIn';
                            if (is_array($review_image)) {
                                $review_image = $review_image['url'] ?? '';
                            } elseif (is_numeric($review_image)) {
                                $review_image = wp_get_attachment_image_url((int) $review_image, 'medium');
                            }
                            $review_image = is_string($review_image) ? $review_image : '';
                        ?>
                        <div class="review">
                            <div class="review-main <?php echo esc_attr($review_animation); ?> delay-200ms">
								<div class="review-main__quote" aria-hidden="true"><svg><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#faq-quote-mark'); ?>"></use></svg></div>
                                <?php if ($show_review_people_photos && $review_image) : ?>
                                    <div class="review-main__image">
                                        <img src="<?php echo esc_url($review_image); ?>" alt="<?php echo esc_attr($review_name); ?>">
                                    </div>
                                <?php endif; ?>

                                <?php if ($review_name) : ?>
                                    <span class="review-main__name"><?php echo esc_html($review_name); ?></span>
                                <?php endif; ?>

                                <?php if ($review_age) : ?>
                                    <span class="review-main__age"><?php echo esc_html($review_age); ?></span>
                                <?php endif; ?>

                                <?php if ($review_job) : ?>
                                    <div class="review-main__job"><?php echo esc_html($review_job); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="review-info <?php echo esc_attr($review_animation); ?> delay-200ms">
                                <?php if ($review_excerpt) : ?>
                                    <p><?php echo esc_html($review_excerpt); ?></p>
                                <?php endif; ?>

                                <?php if ($review_full_text) : ?>
                                    <span class="review-expand modal-call modal-call_review" data-review-name="<?php echo esc_attr($review_name); ?>" data-review-age="<?php echo esc_attr($review_age); ?>" data-review-job="<?php echo esc_attr($review_job); ?>" data-review-image="<?php echo $show_review_people_photos ? esc_url($review_image) : ''; ?>" data-review-text="<?php echo esc_attr(wp_strip_all_tags($review_full_text)); ?>">
                                        Развернуть
                                    </span>
                                <?php endif; ?>

                                <?php if ($show_review_people_photos && $review_people) : ?>
                                    <div class="review-people">
                                        <?php foreach ($review_people as $person) : ?>
                                            <?php
                                            if (is_array($person)) {
                                                $person = $person['url'] ?? '';
                                            } elseif (is_numeric($person)) {
                                                $person = wp_get_attachment_image_url((int) $person, 'thumbnail');
                                            }
                                            if (!$person) {
                                                continue;
                                            }
                                            ?>
                                            <div class="review-people__item">
                                                <img src="<?php echo esc_url($person); ?>" alt="">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <svg class="review-decor wow fadeInUp delay-600ms" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#review-decor'); ?>"></use></svg>
                </div>
            </div>
        </div>
    </div>
</section>
