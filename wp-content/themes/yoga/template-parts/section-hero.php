<?php
// Проверяем, есть ли данные для этой секции
// Можно проверять через get_field() или через гибкий контент (если решите его использовать)
?>
<section class="section-main animated fadeIn delay-200ms" id="section-main">
    <div class="container">
        <div class="row">
            <div class="main">
                <div class="main__info">
                    <h2 class="">
                        <p class="animation-title delay-400ms"><?php the_field('hero_title_line_1'); ?></p>
                        <p class="animation-title delay-1s"><u><?php the_field('hero_title_line_2'); ?></u> <span>йога</span>-</p>
                        <p class="animation-title delay-2s"><?php the_field('hero_title_line_3'); ?></p>
                    </h2>
                    <p class="main__info-text">
                        <?php the_field('hero_subtitle'); ?>
                    </p>
                    <div class="btn btn_alt btn_icon modal-call_login">
                        <span>Попробовать бесплатно</span>
                        <div class="btn-icon">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/btn-arrow_black.png'); ?>" alt="" class="active">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/btn-arrow_green.png'); ?>" alt="">
                        </div>
                    </div>
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/main-decor.png'); ?>" alt="" class="main__info-decor animated rollIn slower delay-300ms">
                </div>

                <?php
                $hero_image = get_field('hero_image');
                if ($hero_image) : ?>
                    <img src="<?php echo esc_url($hero_image['url']); ?>" alt="<?php echo esc_attr($hero_image['alt']); ?>" class="main__img animated slower fadeIn delay-400ms">
                <?php endif; ?>

                <div class="main__practices">
                    <div class="hundreds-practices">
                        <strong><?php the_field('hero_count'); ?></strong>
                        <p><?php the_field('hero_count_text'); ?></p>
                    </div>
                    <?php if (have_rows('hero_categories')) : ?>
                        <div class="practices-categories">
                            <?php while (have_rows('hero_categories')) : the_row(); ?>
                                <span class="practices-categories__item">
                                    <?php the_sub_field('category_name'); ?>
                                </span>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>