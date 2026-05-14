<?php
/**
 * Секция "Почему мы?"
 */
$whyme_title = get_field('whyme_title', get_the_ID()) ?: 'почему мы?';
$whyme_items = get_field('whyme_items', get_the_ID());

$whyme_shape_by_class = [
    'whyme-item_green'  => 'wyme_1.svg',
    'whyme-item_grey'   => 'wyme_2.svg',
    'whyme-item_purple' => 'wyme_3.svg',
];
$whyme_star_by_class = [
    'whyme-item_green'  => 'wyme_star_1.svg',
    'whyme-item_grey'   => 'wyme_star_2.svg',
    'whyme-item_purple' => 'wyme_star_3.svg',
];
$whyme_shape_base_uri = get_template_directory_uri() . '/assets/svg/';
?>

<section class="section-whyme" id="section-whyme">
    <div class="container">
        <div class="row">
            <div class="whyme">
                <h2 class="wow flipInX delay-200ms">
                    <?php echo esc_html($whyme_title); ?>
                </h2>
                
                <?php if ($whyme_items) : ?>
                <div class="whyme__items">
                    <?php foreach ($whyme_items as $item) : 
                        $item_class = $item['item_class'] ?? 'whyme-item_green';
                        $item_number = $item['item_number'] ?? '01.';
                        $item_image = $item['item_image'] ?? '';
                        $item_title = $item['item_title'] ?? '';
                        $item_text = $item['item_text'] ?? '';
                        $item_animation = $item['item_animation'] ?? 'wow rollIn';
                    ?>
                                       
                    <div class="whyme-item <?php echo esc_attr($item_class); ?> <?php echo esc_attr($item_animation); ?> delay-200ms slow">
                        <?php
                        $shape_file = $whyme_shape_by_class[ $item_class ] ?? $whyme_shape_by_class['whyme-item_green'];
                        $shape_url  = $whyme_shape_base_uri . $shape_file;
                        $star_file  = $whyme_star_by_class[ $item_class ] ?? $whyme_star_by_class['whyme-item_green'];
                        $star_url   = $whyme_shape_base_uri . $star_file;
                        ?>
                        <div class="whyme-item__plate">
                            <img src="<?php echo esc_url( $shape_url ); ?>" alt="" class="whyme-item__shape" loading="lazy" decoding="async" aria-hidden="true">
                            <span class="whyme-item__star" aria-hidden="true">
                                <img src="<?php echo esc_url( $star_url ); ?>" alt="" class="whyme-item__star-img" width="42" height="42" loading="lazy" decoding="async">
                            </span>
                            <span class="whyme-item__number"><?php echo esc_html($item_number); ?></span>
                        </div>
                        
                        <?php if ($item_image) : ?>
                            <img src="<?php echo esc_url($item_image); ?>" alt="<?php echo esc_attr($item_title); ?>" class="whyme-item__image">
                        <?php endif; ?>
                        
                        <?php if ($item_title) : ?>
                            <h4><?php echo esc_html($item_title); ?></h4>
                        <?php endif; ?>
                        
                        <?php if ($item_text) : ?>
                            <p><?php echo esc_html($item_text); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>