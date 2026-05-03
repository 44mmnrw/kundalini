<?php
/**
 * Секция "Отзывы"
 */
$reviews_title = get_field('reviews_title', get_the_ID()) ?: 'отзывы';
$reviews_decor = get_field('reviews_decor', get_the_ID());
$reviews_items = get_field('reviews_items', get_the_ID());
$review_people = get_field('review_people', get_the_ID());
?>

<section class="section-reviews" id="section-reviews">
    <div class="container">
        <div class="row">
            <div class="reviews">
                <h2 class="wow flipInX delay-200ms">
                    <?php echo esc_html($reviews_title); ?>
                </h2>
                
                <div class="reviews__main">
                    <?php if ($reviews_items) : ?>
                    <div class="reviews-slider">
                        <?php foreach ($reviews_items as $review) : 
                            $review_image = $review['review_image'] ?? '';
                            $review_name = $review['review_name'] ?? '';
                            $review_age = $review['review_age'] ?? '';
                            $review_job = $review['review_job'] ?? '';
                            $review_excerpt = $review['review_excerpt'] ?? '';
                            $review_full_text = $review['review_full_text'] ?? '';
                            $review_animation = $review['review_animation'] ?? 'wow fadeIn';
                        ?>
                        <div class="review">
                            <div class="review-main <?php echo esc_attr($review_animation); ?> delay-200ms">
                                <?php if ($review_image) : ?>
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
                                    <span class="review-expand modal-call modal-call_review" data-review-name="<?php echo esc_attr($review_name); ?>" data-review-age="<?php echo esc_attr($review_age); ?>" data-review-job="<?php echo esc_attr($review_job); ?>" data-review-image="<?php echo esc_url($review_image); ?>" data-review-text="<?php echo esc_attr(wp_strip_all_tags($review_full_text)); ?>">
                                        Развернуть
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($review_people) : ?>
                                    <div class="review-people">
                                        <?php foreach ($review_people as $person) : ?>
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
                    <?php else : ?>
                    <div class="reviews-slider">
                        <!-- Fallback контент -->
                        <div class="review">
                            <div class="review-main wow fadeIn delay-200ms">
                                <div class="review-main__image">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/review-img-min.png'); ?>" alt="">
                                </div>
                                <span class="review-main__name">Анна К.</span>
                                <span class="review-main__age">27лет</span>
                                <div class="review-main__job">Архитектор</div>
                            </div>
                            <div class="review-info wow fadeIn delay-200ms">
                                <p>«Никаких эмоциональных качелей, тяги на поесть у меня не было. Единственное что в начале я могла встать в 5 утра и сделать практику, а после начала практики по Киртан Крий свалилась на вечернее время. Я прям ощущала как в мозгу после КК начиналось такое кружение, как перезагрузка...»</p>
                                <span class="review-expand modal-call modal-call_review">Развернуть</span>
                                <?php if ($review_people) : ?>
                                    <div class="review-people">
                                        <?php foreach ($review_people as $person) : ?>
                                            <div class="review-people__item">
                                                <img src="<?php echo esc_url($person); ?>" alt="">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <div class="review-people">
                                        <?php for ($i = 1; $i <= 8; $i++) : ?>
                                            <div class="review-people__item">
                                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/review-people-item_0' . $i . '.png'); ?>" alt="">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="arrows-slick wow fadeIn delay-200ms">
                        <div class="arrows-slick__arrow slick-prev">
                            <svg aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                        </div>
                        <div class="arrows-slick__arrow slick-next">
                            <svg aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                        </div>  
                    </div>
                    
                    <?php if ($reviews_decor) : ?>
                        <img src="<?php echo esc_url($reviews_decor); ?>" alt="" class="review-decor wow fadeInUp delay-600ms">
                    <?php else : ?>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/review-decor.png'); ?>" alt="" class="review-decor wow fadeInUp delay-600ms">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>