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
                    
                    // Если это страница записи или постоянной страницы
                    if (is_single() || is_page()) {
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
                    // Если это таксономия (категория, метка или пользовательская таксономия)
                    elseif (is_tax() || is_category() || is_tag()) {
                        $current_term = get_queried_object();
                        $taxonomy = get_taxonomy($current_term->taxonomy);
                        $hide_current_term_in_breadcrumbs = is_tax('product_cat', 'tariffs');
                        
                        // Если это категория записи и у записи есть архив
                        if (is_category() && $taxonomy->object_type[0] == 'post') {
                            echo '<li><a href="' . esc_url(get_post_type_archive_link('post')) . '" class="ways-item ref">Блог</a></li>';
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
                <h2>
                    <?php
                    if (is_home() || is_front_page()) {
                        echo 'Главная';
                    } elseif (is_single() || is_page()) {
                        echo esc_html(get_the_title());
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
            </div>
        </div>
    </div>
</section>