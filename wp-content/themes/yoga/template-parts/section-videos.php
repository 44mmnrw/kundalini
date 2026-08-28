<?php
/**
 * Переиспользуемый шаблонный блок: section videos.
 *
 * @package Yoga
 */
$videos_items = isset($args['items']) && is_array($args['items']) ? $args['items'] : array();
$videos_button_text = yoga_get_purchase_cta_text();
$reviews_hidden = !empty($args['reviews_hidden']);
$video_count = count($videos_items);
$section_classes = array('section-videos');
if ($reviews_hidden) {
    $section_classes[] = 'section-videos_without-reviews';
}
if ($video_count === 1) {
    $section_classes[] = 'section-videos_single';
}
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="section-videos">
    <div class="container">
        <div class="row">
            <div class="videos">
                <div class="videos-slider wow fadeIn delay-200ms" data-video-count="<?php echo esc_attr($video_count); ?>">
                    <?php foreach ($videos_items as $video) :
                        $video_bg = $video['_bg_url'] ?? '';
                        $video_fancybox_url = $video['_fancybox_url'] ?? '';
                        $video_person = $video['_person_url'] ?? '';
                        $video_animation = $video['video_animation'] ?? 'wow fadeIn';
                    ?>
                    <a data-fancybox="videos" href="<?php echo esc_url($video_fancybox_url); ?>" class="videos-item <?php echo esc_attr($video_animation); ?>" aria-label="<?php esc_attr_e('Воспроизвести видео', 'yoga'); ?>">
                        <?php if ($video_bg) : ?>
                            <img src="<?php echo esc_url($video_bg); ?>" alt="" class="videos-item__bg">
                        <?php endif; ?>

                        <div class="videos-item__btn">
                            <svg class="videos-item__play-icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#video-play'); ?>"></use></svg>
                        </div>

                        <div class="videos-person">
                            <div class="videos-person-img">
								<svg class="videos-person-placeholder" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-meditation'); ?>"></use></svg>
                                <?php if ($video_person) : ?>
                                    <img src="<?php echo esc_url($video_person); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="arrows-slick wow fadeIn delay-200ms">
                    <button type="button" class="arrows-slick__arrow slick-prev" aria-label="Предыдущие видео">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                    <button type="button" class="arrows-slick__arrow slick-next" aria-label="Следующие видео">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                </div>

                <div class="videos__try wow fadeIn delay-200ms">
                    <div class="btn btn_icon modal-call_login">
                        <span><?php echo esc_html($videos_button_text); ?></span>
                        <div class="btn-icon">
                            <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                            </svg>
                            <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
