<?php
/**
 * Секция "Видео"
 */
$videos_items = get_field('videos_items', get_the_ID());
$videos_button_text = yoga_get_purchase_cta_text();
?>

<section class="section-videos" id="section-videos">
    <div class="container">
        <div class="row">
            <div class="videos">
                <?php if ($videos_items) : ?>
                <div class="videos-slider wow fadeIn delay-200ms">
                    <?php foreach ($videos_items as $video) : 
                        $video_bg = $video['video_bg_image'] ?? '';
                        $video_url = $video['video_url'] ?? '';
                        $video_type = $video['video_type'] ?? 'mp4';
                        $video_person = $video['video_person_image'] ?? '';
                        $video_animation = $video['video_animation'] ?? 'wow fadeIn';
                        
                        // Формируем URL в зависимости от типа видео
                        $video_fancybox_url = $video_url;
                        if ($video_type === 'youtube') {
                            $video_fancybox_url = 'https://www.youtube.com/watch?v=' . basename(parse_url($video_url, PHP_URL_PATH));
                        } elseif ($video_type === 'vimeo') {
                            $video_fancybox_url = 'https://vimeo.com/' . basename(parse_url($video_url, PHP_URL_PATH));
                        }
                    ?>
                    <a data-fancybox="videos"  href="<?php echo $video_fancybox_url; ?>" class="videos-item">
                        <?php if ($video_bg) : ?>
                            <img src="<?php echo esc_url($video_bg); ?>" alt="" class="videos-item__bg">
                        <?php endif; ?>
                        
                        <div class="videos-item__btn">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/play-btn-icon.png'); ?>" alt="<?php esc_attr_e('Воспроизвести видео', 'yoga'); ?>">
                        </div>
                        
                        <div class="videos-person">
                            <div class="videos-person-img">
                                <?php if ($video_person) : ?>
                                    <img src="<?php echo esc_url($video_person); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="videos-slider wow fadeIn delay-200ms">
                    <!-- Fallback контент -->
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <a data-fancybox="videos" href="<?php echo esc_url(get_template_directory_uri() . '/assets/videos/test-video.mp4'); ?>" class="videos-item">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/video-item_0' . min($i, 4) . '-min.png'); ?>" alt="" class="videos-item__bg">
                        <div class="videos-item__btn">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/play-btn-icon.png'); ?>" alt="<?php esc_attr_e('Воспроизвести видео', 'yoga'); ?>">
                        </div>
                        <div class="videos-person">
                            <div class="videos-person-img">
                                <?php if ($i >= 3 && $i <= 5) : ?>
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/videos-person-img_0' . ($i - 2) . '.png'); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endfor; ?>
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
                
                <div class="videos__try wow fadeIn delay-200ms">
                    <div class="btn btn_icon modal-call_login">
                        <span><?php echo esc_html($videos_button_text); ?></span>
                        <div class="btn-icon">
                            <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                            <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </div>
</section>