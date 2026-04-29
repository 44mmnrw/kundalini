<?php
/**
 * Секция "Почему мы?"
 */
$whyme_title = get_field('whyme_title', get_the_ID()) ?: 'почему мы вооще';
$whyme_items = get_field('whyme_items', get_the_ID());
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
                        $item_bg = $item['item_bg'] ?? '';
                        $item_image = $item['item_image'] ?? '';
                        $item_title = $item['item_title'] ?? '';
                        $item_text = $item['item_text'] ?? '';
                        $item_animation = $item['item_animation'] ?? 'wow rollIn';
                    ?>
                    <p>test</p>
                    <div class="whyme-item <?php echo esc_attr($item_class); ?> <?php echo esc_attr($item_animation); ?> delay-200ms slow">
                        <?php if ($item_bg) : ?>
                            <img src="<?php echo esc_url($item_bg); ?>" alt="" class="whyme-item__bg">
                        <?php endif; ?>
                        
                        <span class="whyme-item__number">
                            <?php echo esc_html($item_number); ?>
                        </span>
                        
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
                <?php else : ?>
                    <!-- Fallback контент если поля не заполнены -->
                    <div class="whyme__items">
                        <div class="whyme-item whyme-item_green wow rollIn delay-200ms slow">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-bg_01.png'); ?>" alt="" class="whyme-item__bg">
                            <span class="whyme-item__number">01.</span>
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-img_01-min.png'); ?>" alt="" class="whyme-item__image">
                            <h4>Удобный формат</h4>
                            <p>понятные иллюстрации и подробные инструкции помогут легко освоить крийи и медитации</p>
                        </div>
                        <div class="whyme-item whyme-item_grey wow zoomIn delay-200ms slow">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-bg_02.png'); ?>" alt="" class="whyme-item__bg">
                            <span class="whyme-item__number">02.</span>
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-img_02-min.png'); ?>" alt="" class="whyme-item__image">
                            <h4>Комфортный ритм</h4>
                            <p>доступ к платформе 24/7 позволяет заниматься в удобное время, без привязки к расписанию</p>
                        </div>
                        <div class="whyme-item whyme-item_purple wow rollInAlt delay-200ms slow">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-bg_03.png'); ?>" alt="" class="whyme-item__bg">
                            <span class="whyme-item__number">03.</span>
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/whyme-item-img_03-min.png'); ?>" alt="" class="whyme-item__image">
                            <h4>Глубокое погружение</h4>
                            <p>изучайте не только практику, но и философию Кундалини, чтобы лучше понимать её принципы и влияние</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>