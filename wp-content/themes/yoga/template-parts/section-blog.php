<?php
// Получаем настройки из ACF
$new_title = get_field('blog_new_title', 'option');
$posts_count = get_field('blog_posts_count', 'option') ?: 9;
$blog_category = get_category_by_slug('blog');

// Верхний блок "Новое": автоматически берём 2 последних поста по дате.
$latest_posts_query = new WP_Query(array(
    'post_type' => 'post',
    'posts_per_page' => 2,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
));

$main_post = null;
$secondary_post = null;
$exclude_ids = array();

if (!empty($latest_posts_query->posts)) {
    $main_post = isset($latest_posts_query->posts[0]) ? $latest_posts_query->posts[0]->ID : null;
    $secondary_post = isset($latest_posts_query->posts[1]) ? $latest_posts_query->posts[1]->ID : null;
    $exclude_ids = array_filter(array($main_post, $secondary_post));
}

// Нижний список: продолжаем после первых 2 постов.
$current_posts = new WP_Query(array(
    'post_type' => 'post',
    'posts_per_page' => $posts_count,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'post__not_in' => $exclude_ids,
));
?>

<section class="section-blog" id="section-blog">
    <div class="container">
        <div class="row">
            <div class="blog-result">
                <h3>
                    <?php echo esc_html($new_title ?: 'Новое'); ?>
                </h3>
                <b>
                    Найдено практик: <?php echo (int) $latest_posts_query->found_posts; ?>
                </b>
            </div>
        </div>
        
        <div class="row">
            <div class="blog-main">
                <?php if ($main_post) : ?>
                    <div class="blog-main__image">
                        <?php echo get_the_post_thumbnail($main_post, 'large'); ?>
                        <a href="<?php echo get_permalink($main_post); ?>"></a>
                    </div>
                <?php endif; ?>
                
                <div class="blog-main__articles">
                    <?php if ($main_post) : ?>
                        <div class="blog-main-article-main">
                            <div class="main-article-into">
                                <?php
    // Получаем категории для главного поста
    $main_post_categories = get_the_category($main_post);
    
    if (!empty($main_post_categories)) {
        foreach ($main_post_categories as $category) {
            // Показываем только дочерние категории рубрики "blog"
            if ($category->parent == $blog_category->term_id) {
                echo '<div class="article-cat">' . esc_html($category->name) . '</div>';
            }
        }
    }
    ?>
                                <time class="article-time">
                                    <?php echo human_time_diff(get_the_time('U', $main_post), current_time('timestamp')) . ' назад'; ?>
                                </time>
                            </div>
                            <h3>
                                <?php echo get_the_title($main_post); ?>
                            </h3>
                            <a href="<?php echo get_permalink($main_post); ?>" class="article-btn">
                                Читать
                            </a>
                            <span class="article-number">01.</span>
                            <a href="<?php echo get_permalink($main_post); ?>" class="blog-main-article-main__link"></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($secondary_post) : ?>
                        <div class="blog-main-article-sub">
                            <div class="blog-main-article-sub__image">
                                <?php echo get_the_post_thumbnail($secondary_post, 'medium'); ?>
                            </div>
                            <div class="blog-main-article-sub__info">
                                <div class="main-article-into">
                                    <div class="article-cat">
                                        <?php
                                        // Получаем категории для второстепенного поста
                                        $secondary_post_categories = get_the_category($secondary_post);
                                        $category_names = array();
                                        
                                        if (!empty($secondary_post_categories)) {
                                            foreach ($secondary_post_categories as $category) {
                                                // Показываем только дочерние категории рубрики "blog"
                                                if ($category->parent == $blog_category->term_id) {
                                                    $category_names[] = $category->name;
                                                }
                                            }
                                            
                                            if (!empty($category_names)) {
                                                echo esc_html(implode(', ', $category_names));
                                            }
                                        }
                                        ?>
                                    </div>
                                    <time class="article-time">
                                        <?php echo human_time_diff(get_the_time('U', $secondary_post), current_time('timestamp')) . ' назад'; ?>
                                    </time>
                                </div>
                                <h4>
                                    <?php echo get_the_title($secondary_post); ?>
                                </h4>
                            </div>
                            <span class="article-number">02.</span>
                            <a href="<?php echo get_permalink($secondary_post); ?>" class="blog-main-article-sub__link"></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="blog-articles">
                <?php
                /*
                <div class="blog-articles__intro">
                    <h3>Популярное</h3>
                </div>
                */
                ?>
                
                <div class="blog-articles__items">
                    <?php if ($current_posts->have_posts()) : ?>
                        <?php $counter = 1; ?>
                        <?php while ($current_posts->have_posts()) : $current_posts->the_post(); ?>
                            <div class="blog-article-item <?php echo $counter > 9 ? 'blog-article-item_last hidden' : ''; ?>">
                                <div class="blog-article-item__image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="blog-article-item__date">
                                    <time class="article-time">
                                        <?php echo get_the_date('j F, Y'); ?>
                                    </time>
                                    <?php
                                    // Axecode: временно скрываем время чтения по задаче.
                                    /*
                                    <time class="article-time article-time_time">
                                        <?php echo reading_time(); ?> мин
                                    </time>
                                    */
                                    ?>
                                </div>
                                
                                <h4>
                                    <?php the_title(); ?>
                                </h4>
                                
                                <div class="article-btn">
                                    Читать
                                </div>
                                
                                <a href="<?php the_permalink(); ?>"></a>
                            </div>
                            <?php $counter++; ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($current_posts->found_posts > 9) : ?>
                    <div class="blog-articles__more">
                        <div class="btn">
                            <span class="active">Показать еще</span>
                            <span>Свернуть</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php wp_reset_postdata(); ?>