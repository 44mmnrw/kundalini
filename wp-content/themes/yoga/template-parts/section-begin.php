<?php
/**
 * Секция "С чего начать?"
 */
$begin_title = get_field('begin_title', get_the_ID()) ?: 'С чего начать?';
$begin_items = get_field('begin_items', get_the_ID());
?>

<section class="section-begin" id="section-begin">
    <div class="container">
        <div class="row">
            <div class="begin">
                <h2 class="wow flipInX delay-200ms">
                    <?php echo esc_html($begin_title); ?>
                </h2>
                
                <?php if ($begin_items) : ?>
                <div class="begin-items">
                    <?php 
                    $delay_classes = ['delay-200ms', 'delay-400ms', 'delay-600ms', 'delay-800ms', 'delay-1s', 'delay-1200ms'];
                    $index = 0;
                    
                    foreach ($begin_items as $item) : 
                        $item_number = $item['item_number'] ?? '01.';
                        $item_image = $item['item_image'] ?? '';
                        $item_title = $item['item_title'] ?? '';
                        $item_button = $item['item_button'] ?? '';
                        $item_animation = $item['item_animation'] ?? 'wow slideInLeft';
                        $item_delay = $item['item_delay'] ?? $delay_classes[$index] ?? 'delay-200ms';
                        
                        // Для последнего элемента используем special кнопку если не указана своя
                        $is_last = ($index === count($begin_items) - 1);
                        $button_src = $item_button ?: (
                            $is_last ? 
                            get_template_directory_uri() . '/assets/img/begin-button_last.png' :
                            get_template_directory_uri() . '/assets/img/begin-button.png'
                        );
                    ?>
                    <div class="begin-item <?php echo esc_attr($item_animation); ?> <?php echo esc_attr($item_delay); ?>">
                        <?php if ($item_image) : ?>
                            <img src="<?php echo esc_url($item_image); ?>" alt="<?php echo esc_attr($item_title); ?>" class="begin-item__image">
                        <?php endif; ?>
                        
                        <div class="begin-item__info">
                            <?php if ($item_title) : ?>
                                <h5>
                                    <span><?php echo esc_html($item_number); ?></span>
                                    <?php echo esc_html($item_title); ?>
                                </h5>
                            <?php endif; ?>
                            
                            <?php if ($button_src) : ?>
                                <div class="begin-button">
                                    <img src="<?php echo esc_url($button_src); ?>" alt="<?php echo esc_attr($item_title); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                    $index++;
                    endforeach; 
                    ?>
                </div>
                <?php else : ?>
                    <!-- Fallback контент если поля не заполнены -->
                    <div class="begin-items">
                        <div class="begin-item wow slideInLeft delay-200ms">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-img_01-min.png'); ?>" alt="" class="begin-item__image"> 
                            <div class="begin-item__info">
                                <h5>
                                    <span>01.</span>
                                    Выберите нужную практику
                                </h5>
                                <div class="begin-button">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-button.png'); ?>" alt="">
                                </div>
                            </div> 
                        </div>
                        <div class="begin-item wow slideInLeft delay-400ms">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-img_02-min.png'); ?>" alt="" class="begin-item__image"> 
                            <div class="begin-item__info">
                                <h5>
                                    <span>02.</span>
                                    Установите таймер
                                </h5>
                                <div class="begin-button">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-button.png'); ?>" alt="">
                                </div>
                            </div>  
                        </div>
                        <div class="begin-item wow slideInLeft delay-600ms">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-img_03-min.png'); ?>" alt="" class="begin-item__image"> 
                            <div class="begin-item__info">
                                <h5>
                                    <span>03.</span>
                                    Начните заниматься
                                </h5>
                                <div class="begin-button">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/begin-button_last.png'); ?>" alt="">
                                </div>
                            </div> 
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>