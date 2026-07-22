<?php
/**
 * Переиспользуемый шаблонный блок: section blog.
 *
 * @package Yoga
 */
$new_title = get_field('blog_new_title', 'option');
$posts_count = get_field('blog_posts_count', 'option') ?: 9;
$blog_category = get_category_by_slug('blog');


$get_article_category = static function (int $post_id) use ($blog_category): ?WP_Term {
    if (!$blog_category instanceof WP_Term) {
        return null;
    }

    $categories = get_the_category($post_id);

    foreach ($categories as $category) {
        if ($category instanceof WP_Term
            && (int) $category->parent === (int) $blog_category->term_id) {
            return $category;
        }
    }

    return null;
};

$search_q = '';
if (isset($_GET['s']) && is_string($_GET['s'])) {
    $search_q = sanitize_text_field(wp_unslash($_GET['s']));
}
if ($search_q === '') {
    $search_q = is_string(get_search_query()) ? get_search_query() : '';
}
$has_search = ($search_q !== '');
$category_filter_term_id = null;

if ($blog_category && !is_wp_error($blog_category) && is_category()) {
    $qobj = get_queried_object();
    if ($qobj instanceof WP_Term && $qobj->taxonomy === 'category') {
        $blog_tid = (int) $blog_category->term_id;
        $q_tid = (int) $qobj->term_id;
        if ($q_tid === $blog_tid || (int) $qobj->parent === $blog_tid || cat_is_ancestor_of($blog_tid, $q_tid)) {
            $category_filter_term_id = $q_tid;
        }
    }
}



// The explicit form filter must take priority over the base /blog/ category route.
if ($blog_category && !is_wp_error($blog_category)) {
    if (!empty($_GET['category'])) {
        $slug = sanitize_title(wp_unslash($_GET['category']));
        if ($slug !== '') {
            $child = get_category_by_slug($slug);
            if ($child && !is_wp_error($child) && (int) $child->parent === (int) $blog_category->term_id) {
                $category_filter_term_id = (int) $child->term_id;
            }
        }
    }
}

// A selected child category is also a search result, but the base `blog`
// category is the regular blog landing page.
$has_selected_blog_category = $category_filter_term_id !== null
    && (!($blog_category instanceof WP_Term) || $category_filter_term_id !== (int) $blog_category->term_id);
$category_filter_term = $has_selected_blog_category ? get_category($category_filter_term_id) : null;
$is_blog_category_listing = $has_selected_blog_category && !$has_search;
$is_blog_search_ui = ($has_search || is_search() || $has_selected_blog_category);

$query_common = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
);

if ($is_blog_search_ui) {
    $query_common['s'] = $search_q;
}

if ($category_filter_term_id) {
    $query_common['tax_query'] = array(
        array(
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $category_filter_term_id,
            'include_children' => true,
        ),
    );
}

$main_post = null;
$secondary_post = null;




if ($is_blog_search_ui) {
    $current_posts = new WP_Query(array_merge($query_common, array(
        'posts_per_page' => $posts_count,
    )));
    $blog_posts_total = (int) $current_posts->found_posts;
} else {

    $latest_posts_query = new WP_Query(array_merge($query_common, array(
        'posts_per_page' => 2,
    )));

    if (!empty($latest_posts_query->posts)) {
        $main_post = isset($latest_posts_query->posts[0]) ? $latest_posts_query->posts[0]->ID : null;
        $secondary_post = isset($latest_posts_query->posts[1]) ? $latest_posts_query->posts[1]->ID : null;
    }

    $blog_posts_total = (int) $latest_posts_query->found_posts;


    $popular_query_common = $query_common;
    unset($popular_query_common['orderby'], $popular_query_common['order']);

    $current_posts = new WP_Query(array_merge($popular_query_common, array(
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            'views_clause' => array(
                'key' => 'yoga_post_views',
                'compare' => 'EXISTS',
                'type' => 'NUMERIC',
            ),
            'no_views_clause' => array(
                'key' => 'yoga_post_views',
                'compare' => 'NOT EXISTS',
            ),
        ),
        'orderby' => array(
            'views_clause' => 'DESC',
            'date' => 'DESC',
        ),
    )));
}

$blog_count_label = 'Всего статей:';
$blog_count_value = $blog_posts_total;

if ($is_blog_search_ui) {
    $blog_count_label = 'Найдено статей:';
}
?>

<section class="section-blog<?php echo $is_blog_search_ui ? ' section-blog--search-results' : ''; ?>" id="section-blog">
    <div class="container">
        <div class="row">
            <div class="blog-result">
                <h3>
                    <?php
                    if ($is_blog_category_listing && $category_filter_term instanceof WP_Term) {
                        echo esc_html($category_filter_term->name);
                    } elseif ($is_blog_search_ui) {
                        echo esc_html($search_q !== '' ? $search_q : 'Результаты поиска');
                    } else {
                        echo esc_html($new_title ?: 'Новое');
                    }
                    ?>
                </h3>
                <?php if (!$is_blog_category_listing) : ?>
                    <b class="<?php echo $is_blog_search_ui ? 'active' : ''; ?>">
                        <?php echo esc_html($blog_count_label); ?> <?php echo $blog_count_value; ?>
                    </b>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$is_blog_search_ui) : ?>
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
                                $main_post_category = $get_article_category((int) $main_post);
                                if ($main_post_category) : ?>
                                    <div class="article-cat"><?php echo esc_html($main_post_category->name); ?></div>
                                <?php endif; ?>
                                <time class="article-time">
                                    <?php echo human_time_diff(get_the_time('U', $main_post), current_time('timestamp')) . ' назад'; ?>
                                </time>
                                <span class="article-number">01.</span>
                            </div>
                            <h3>
                                <?php echo get_the_title($main_post); ?>
                            </h3>
                            <a href="<?php echo get_permalink($main_post); ?>" class="article-btn">
                                Читать
                            </a>
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
                                    <?php $secondary_post_category = $get_article_category((int) $secondary_post); ?>
                                    <?php if ($secondary_post_category) : ?>
                                        <div class="article-cat"><?php echo esc_html($secondary_post_category->name); ?></div>
                                    <?php endif; ?>
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
        <?php endif; ?>

        <div class="row">
            <div class="blog-articles">
                <?php if (!$is_blog_search_ui) : ?>
                <div class="blog-articles__intro">
                    <h3><?php esc_html_e('Читайте также', 'yoga'); ?></h3>
                </div>
                <?php endif; ?>

                <div class="blog-articles__items">
                    <?php if ($current_posts->have_posts()) : ?>
                        <?php $counter = 1; ?>
                        <?php while ($current_posts->have_posts()) : $current_posts->the_post(); ?>
                            <div class="blog-article-item <?php echo $counter > $posts_count ? 'blog-article-item_last hidden' : ''; ?>">
                                <div class="blog-article-item__image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php endif; ?>
                                </div>

                                <div class="blog-article-item__date">
                                    <?php $post_category = $get_article_category((int) get_the_ID()); ?>
                                    <?php if ($post_category) : ?>
                                        <div class="article-cat"><?php echo esc_html($post_category->name); ?></div>
                                    <?php endif; ?>
                                    <time class="article-time">
                                        <?php echo get_the_date('j F, Y'); ?>
                                    </time>
                                </div>

                                <h4>
                                    <?php the_title(); ?>
                                </h4>

                                <a class="article-btn" href="<?php the_permalink(); ?>">
                                    Читать
                                </a>

                                <a class="blog-article-item__link" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"></a>
                            </div>
                            <?php $counter++; ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>
                </div>

                <?php if ($current_posts->found_posts > $posts_count) : ?>
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
