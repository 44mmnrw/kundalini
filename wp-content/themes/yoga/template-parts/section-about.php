<section class="section-about" id="section-about">
    <div class="container">
        <div class="row">
            <div class="about">
                <div class="about__main about__main_start">
                    <h3 class="animated fadeInUp slow delay-200ms">
                        <?php echo nl2br(get_field('about_quote')); ?>
                    </h3>
                    
                    <div class="about-text animated fadeInUp slow delay-200ms">
                        <?php echo apply_filters('the_content', get_field('about_intro_text')); ?>
                    </div>
                    
                    <div class="about-img animated fadeInDown slow delay-500ms">
                        <?php 
                        $main_image = get_field('about_main_image');
                        $mobile_image = get_field('about_main_image_mobile');
                        
                        if ($main_image) : ?>
                            <img src="<?php echo esc_url($main_image['url']); ?>" 
                                 alt="<?php echo esc_attr($main_image['alt']); ?>" 
                                 class="about-img__img">
                        <?php endif; ?>
                        
                        <?php if ($mobile_image) : ?>
                            <img src="<?php echo esc_url($mobile_image['url']); ?>" 
                                 alt="<?php echo esc_attr($mobile_image['alt']); ?>" 
                                 class="about-img__img about-img__img_mob">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="about__years">
                    <?php 
                    $years = get_field('about_years');
                    if ($years) : 
                        foreach ($years as $index => $year) : ?>
                            <div class="about-year wow fadeInUp slow delay-200ms">
                                <span><?php echo esc_html($year['year']); ?></span>
                                <div class="about-year__text">
                                    <p><?php echo esc_html($year['year_text']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                </div>
                
                <div class="about__main about__main_sec">
                    <?php 
                    $secondary_texts = get_field('about_secondary_texts');
                    if ($secondary_texts) : 
                        foreach ($secondary_texts as $text) : ?>
                            <div class="about-text wow fadeInUp slow delay-200ms">
                                <?php echo apply_filters('the_content', $text['secondary_text']); ?>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                    
                    <?php 
                    $secondary_image = get_field('about_secondary_image');
                    if ($secondary_image) : ?>
                        <div class="about-img wow fadeInUp slow delay-200ms">
                            <img src="<?php echo esc_url($secondary_image['url']); ?>" 
                                 alt="<?php echo esc_attr($secondary_image['alt']); ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="about__last">
                    <?php 
                    $final_texts = get_field('about_final_texts');
                    if ($final_texts) : 
                        foreach ($final_texts as $index => $text) : 
                            $class = ($index == 1) ? 'about-text about-text_custright' : 'about-text'; ?>
                            <div class="<?php echo $class; ?> wow fadeInUp slow delay-200ms">
                                <?php echo apply_filters('the_content', $text['final_text']); ?>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>