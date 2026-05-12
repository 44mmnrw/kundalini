<?php
// Проверяем, есть ли данные для этой секции
// Можно проверять через get_field() или через гибкий контент (если решите его использовать)

$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
$tariffs_url = home_url('/product-category/tariffs/');
if ($tariffs_term && !is_wp_error($tariffs_term)) {
	$term_link = get_term_link($tariffs_term);
	if (!is_wp_error($term_link)) {
		$tariffs_url = $term_link;
	}
}
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
                    <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url($tariffs_url); ?>" class="btn btn_alt btn_icon">
                        <span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
                        <div class="btn-icon">
                            <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                            <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                        </div>
                    </a>
                    <?php else : ?>
                    <div class="btn btn_alt btn_icon modal-call_login">
                        <span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
                        <div class="btn-icon">
                            <svg class="btn-icon-arrow btn-icon-arrow_black active" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                            <svg class="btn-icon-arrow btn-icon-arrow_green" aria-hidden="true" focusable="false">
                                <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                            </svg>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php
                    $main_info_decor_path = get_template_directory() . '/assets/svg/arrow_hand_drawn.svg';
                    if (is_readable($main_info_decor_path)) {
                        $main_info_decor_svg = file_get_contents($main_info_decor_path);
                        if (false !== $main_info_decor_svg) {
                            $main_info_decor_svg = preg_replace(
                                '/<svg\s+/',
                                '<svg class="main__info-decor main__info-decor--snake" aria-hidden="true" focusable="false" ',
                                $main_info_decor_svg,
                                1
                            );
                            echo $main_info_decor_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- локальный статический SVG из темы
                        }
                    }
                    ?>
                </div>

                <?php
                $hero_image = get_field('hero_image');
                if ($hero_image) : ?>
                    <img src="<?php echo esc_url($hero_image['url']); ?>" alt="<?php echo esc_attr($hero_image['alt']); ?>" class="main__img animated slower fadeIn delay-400ms">
                <?php endif; ?>

                <div class="main__practices">
                    <div class="hundreds-practices">
                        <svg class="hundreds-practices__card" aria-hidden="true">
                            <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-card'); ?>"></use>
                        </svg>
                        <svg class="hundreds-practices__oval" aria-hidden="true">
                            <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-oval'); ?>"></use>
                        </svg>
                        <svg class="hundreds-practices__star" aria-hidden="true">
                            <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-star'); ?>"></use>
                        </svg>
                        <svg class="hundreds-practices__bg-mobile" aria-hidden="true">
                            <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-mobile-bg'); ?>"></use>
                        </svg>
                        <div class="hundreds-practices__content">
                            <strong><?php the_field('hero_count'); ?></strong>
                            <p><?php the_field('hero_count_text'); ?></p>
                        </div>
                        <a class="hundreds-practices__arrow" href="<?php echo esc_url($tariffs_url); ?>" aria-label="Перейти к тарифам">
                            <svg class="hundreds-practices__arrow-icon hundreds-practices__arrow-icon_default" aria-hidden="true">
                                <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-arrow'); ?>"></use>
                            </svg>
                            <svg class="hundreds-practices__arrow-icon hundreds-practices__arrow-icon_hover" aria-hidden="true">
                                <use xlink:href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#hundreds-practices-arrow-hover'); ?>"></use>
                            </svg>
                        </a>
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