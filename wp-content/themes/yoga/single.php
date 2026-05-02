<?php
/**
 * Single post template (blog posts).
 */
get_header();
get_template_part('template-parts/section', 'ways');
?>

<section class="section-post" id="section-post">
    <div class="container">
        <div class="row">
            <div class="post">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <article <?php post_class('post-article'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-article__thumb">
                                    <?php the_post_thumbnail('large'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="post-article__content">
                                <?php the_content(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p>Запись не найдена.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
