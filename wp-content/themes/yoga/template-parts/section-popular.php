<?php
/**
 * Переиспользуемый шаблонный блок: section popular.
 *
 * @package Yoga
 */
$popular_title = get_field('popular_practices_title', get_the_ID())
    ?: get_field('popular_practices_title', 'option')
    ?: 'популярные практики';

$popular_items = get_field('popular_practices_items', get_the_ID());
if (!$popular_items) {
    $popular_items = get_field('popular_practices_items', 'option');
}

$popular_items = is_array($popular_items) ? $popular_items : array();
$testimonials_hidden = !empty($args['testimonials_hidden']);
$follows_reviews = !empty($args['follows_reviews']);
$section_classes = array('section-popular-practices');
if ($testimonials_hidden) {
    $section_classes[] = 'section-popular-practices_without-testimonials';
} elseif ($follows_reviews) {
    $section_classes[] = 'section-popular-practices_after-reviews';
}
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" id="section-popular-practices">
    <div class="container">
        <div class="row">
            <div class="popular-practices">
                <h2 class="wow flipInX delay-200ms">
                    <?php echo esc_html($popular_title); ?>
                </h2>

                <div class="popular-practices-slider wow fadeIn delay-200ms">
                    <?php foreach ($popular_items as $index => $item) :
                        $selected_practice = is_array($item) ? ($item['practice'] ?? 0) : 0;
                        $practice_id = is_object($selected_practice) ? (int) ($selected_practice->ID ?? 0) : (int) $selected_practice;
                        if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice' || get_post_status($practice_id) !== 'publish') {
                            continue;
                        }
                        $item_title = get_the_title($practice_id);
                        $item_text = wp_strip_all_tags((string) (get_field('short_description', $practice_id) ?: get_the_excerpt($practice_id)));
                        $item_image = yoga_get_practice_card_image_url($practice_id, 'large');
                        $item_link = get_permalink($practice_id);
                        $item_color = strtolower((string) ($item['practice_style'] ?? ''));

                        $color_class = '';
                        if ($item_color === 'popular-practice_pink' || $item_color === 'pink' || $item_color === 'розовый') {
                            $color_class = ' popular-practice_pink';
                        } elseif ($item_color === 'popular-practice_green' || $item_color === 'green' || $item_color === 'lime' || $item_color === 'лайм' || $item_color === 'зеленый' || $item_color === 'зелёный') {
                            $color_class = ' popular-practice_green';
                        } elseif (!empty($item_color) && str_contains($item_color, 'popular-practice_pink')) {
                            $color_class = ' popular-practice_pink';
                        } elseif (!empty($item_color) && str_contains($item_color, 'popular-practice_green')) {
                            $color_class = ' popular-practice_green';
                        }
                    ?>
                    <div class="popular-practice<?php echo esc_attr($color_class); ?>">
                        <?php if ($item_image) : ?>
                            <img src="<?php echo esc_url($item_image); ?>" alt="<?php echo esc_attr($item_title); ?>">
                        <?php endif; ?>

                        <?php if ($item_title) : ?>
                            <h4><?php echo esc_html($item_title); ?></h4>
                        <?php endif; ?>

                        <?php if ($item_text) : ?>
                            <p><?php echo esc_html($item_text); ?></p>
                        <?php endif; ?>

                        <?php if ($item_link) : ?>
                            <a href="<?php echo esc_url($item_link); ?>" class="popular-practice__link" aria-label="<?php echo esc_attr($item_title ?: 'Открыть практику'); ?>"></a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="arrows-slick wow fadeIn delay-200ms">
                    <button type="button" class="arrows-slick__arrow slick-prev" aria-label="Предыдущие практики">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                    <button type="button" class="arrows-slick__arrow slick-next" aria-label="Следующие практики">
                        <svg aria-hidden="true" focusable="false">
                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
