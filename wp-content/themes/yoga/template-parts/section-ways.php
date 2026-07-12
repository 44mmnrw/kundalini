<?php
/**
 * Универсальная секция "Хлебные крошки и заголовок"
 * Работает на всех типах страниц WordPress
 */
?>
<section class="section-ways" id="section-ways">
    <div class="container">
        <div class="row">
            <div class="ways">
                <ul>
                    <?php
                    // Главная страница
                    echo '<li><a href="' . esc_url(home_url('/')) . '" class="ways-item ref">Главная</a></li>';
                    
                    // Для практики выводим полный путь по иерархии practice-type.
                    if (is_singular('practice')) {
                        $practice_terms = get_the_terms(get_the_ID(), 'practice-type');
                        $primary_term = null;
                        $primary_ancestors = array();

                        if (is_array($practice_terms) && !is_wp_error($practice_terms)) {
                            foreach ($practice_terms as $practice_term) {
                                $term_ancestors = get_ancestors((int) $practice_term->term_id, 'practice-type', 'taxonomy');
                                if ($primary_term === null || count($term_ancestors) > count($primary_ancestors)) {
                                    $primary_term = $practice_term;
                                    $primary_ancestors = $term_ancestors;
                                }
                            }
                        }

                        if ($primary_term instanceof WP_Term) {
                            $breadcrumb_term_ids = array_reverse(array_map('intval', $primary_ancestors));
                            $breadcrumb_term_ids[] = (int) $primary_term->term_id;

                            foreach ($breadcrumb_term_ids as $breadcrumb_term_id) {
                                $breadcrumb_term = get_term($breadcrumb_term_id, 'practice-type');
                                if (!($breadcrumb_term instanceof WP_Term) || is_wp_error($breadcrumb_term)) {
                                    continue;
                                }

                                $breadcrumb_term_link = get_term_link($breadcrumb_term);
                                if (is_wp_error($breadcrumb_term_link)) {
                                    echo '<li><span class="ways-item">' . esc_html($breadcrumb_term->name) . '</span></li>';
                                    continue;
                                }

                                echo '<li><a href="' . esc_url($breadcrumb_term_link) . '" class="ways-item ref">' . esc_html($breadcrumb_term->name) . '</a></li>';
                            }
                        }

                        echo '<li><span class="ways-item">' . esc_html(get_the_title()) . '</span></li>';
                    }
                    // Если это страница записи или постоянной страницы
                    elseif (is_single() || is_page()) {
                        $post_type = get_post_type_object(get_post_type());
                        
                        // Если это запись и у нее есть архив
                        if (is_single() && $post_type->has_archive) {
                            echo '<li><a href="' . esc_url(get_post_type_archive_link(get_post_type())) . '" class="ways-item ref">' . esc_html($post_type->labels->name) . '</a></li>';
                        }
                        
                        // Если у записи есть категории
                        if (is_single() && has_category()) {
                            $categories = get_the_category();
                            $category = $categories[0];
                            echo '<li><a href="' . esc_url(get_category_link($category->term_id)) . '" class="ways-item ref">' . esc_html($category->name) . '</a></li>';
                        }
                        
                        // Текущая страница/запись
                        echo '<li><span class="ways-item">' . esc_html(get_the_title()) . '</span></li>';
                    } 
                    // Если это архив записей
                    elseif (is_post_type_archive()) {
                        $post_type = get_post_type_object(get_post_type());
                        echo '<li><span class="ways-item">' . esc_html($post_type->labels->name) . '</span></li>';
                    }
                    // Для категории практик выводим всю иерархию practice-type.
                    elseif (is_tax('practice-type')) {
                        $current_term = get_queried_object();

                        if ($current_term instanceof WP_Term) {
                            $term_ancestor_ids = array_reverse(
                                array_map(
                                    'intval',
                                    get_ancestors((int) $current_term->term_id, 'practice-type', 'taxonomy')
                                )
                            );

                            foreach ($term_ancestor_ids as $term_ancestor_id) {
                                $term_ancestor = get_term($term_ancestor_id, 'practice-type');
                                if (!($term_ancestor instanceof WP_Term) || is_wp_error($term_ancestor)) {
                                    continue;
                                }

                                $term_ancestor_link = get_term_link($term_ancestor);
                                if (is_wp_error($term_ancestor_link)) {
                                    echo '<li><span class="ways-item">' . esc_html($term_ancestor->name) . '</span></li>';
                                    continue;
                                }

                                echo '<li><a href="' . esc_url($term_ancestor_link) . '" class="ways-item ref">' . esc_html($term_ancestor->name) . '</a></li>';
                            }

                            echo '<li><span class="ways-item">' . esc_html($current_term->name) . '</span></li>';
                        }
                    }
                    // Если это таксономия (категория, метка или пользовательская таксономия)
                    elseif (is_tax() || is_category() || is_tag()) {
                        $current_term = get_queried_object();
                        $taxonomy = get_taxonomy($current_term->taxonomy);
                        $hide_current_term_in_breadcrumbs = is_tax('product_cat', 'tariffs');
                        
                        // Если это категория записи и у записи есть архив
                        if (is_category() && $taxonomy->object_type[0] == 'post') {
                            $is_blog_root_category = isset($current_term->slug) && $current_term->slug === 'blog';
                            if (!$is_blog_root_category) {
                                echo '<li><a href="' . esc_url(get_post_type_archive_link('post')) . '" class="ways-item ref">Блог</a></li>';
                            }
                        } 
                        // Если это пользовательская таксономия
                        elseif (!empty($taxonomy->object_type)) {
                            $post_type = get_post_type_object($taxonomy->object_type[0]);
                            if ($post_type->has_archive) {
                                echo '<li><a href="' . esc_url(get_post_type_archive_link($post_type->name)) . '" class="ways-item ref">' . esc_html($post_type->labels->name) . '</a></li>';
                            }
                        }
                        
                        // Текущий термин (для тарифов скрываем подпись в хлебных крошках)
                        if (!$hide_current_term_in_breadcrumbs) {
                            echo '<li><span class="ways-item">' . esc_html($current_term->name) . '</span></li>';
                        }
                    }
                    // Если это страница поиска
                    elseif (is_search()) {
                        echo '<li><span class="ways-item">Результаты поиска: ' . esc_html(get_search_query()) . '</span></li>';
                    }
                    // Если это страница 404
                    elseif (is_404()) {
                        echo '<li><span class="ways-item">Страница не найдена</span></li>';
                    }
                    // Если это авторский архив
                    elseif (is_author()) {
                        echo '<li><span class="ways-item">Автор: ' . esc_html(get_the_author()) . '</span></li>';
                    }
                    ?>
                </ul>
                <?php
                /** На странице «Контакты» заголовок под крошками не показываем */
                $yoga_hide_ways_heading = is_page_template('templates-page/contacts.php');
                ?>
                <?php if (!$yoga_hide_ways_heading) : ?>
                <h2 class="ways-heading">
                    <?php
                    if (is_home() || is_front_page()) {
                        echo 'Главная';
                    } elseif (is_single() || is_page()) {
                        $custom_main_title = '';
                        if (function_exists('get_field') && is_page()) {
                            $custom_main_title = trim((string) get_field('about_main_title', get_the_ID()));
                        }
                        echo esc_html($custom_main_title !== '' ? $custom_main_title : get_the_title());
                    } elseif (is_post_type_archive()) {
                        $post_type = get_post_type_object(get_post_type());
                        echo esc_html($post_type->labels->name);
                    } elseif (is_tax() || is_category() || is_tag()) {
                        $current_term = get_queried_object();
                        echo esc_html($current_term->name);
                    } elseif (is_search()) {
                        echo 'Результаты поиска: ' . esc_html(get_search_query());
                    } elseif (is_404()) {
                        echo '';
                    } elseif (is_author()) {
                        echo 'Автор: ' . esc_html(get_the_author());
                    } else {
                        echo 'Библиотека практик';
                    }
                    ?>
                </h2>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
