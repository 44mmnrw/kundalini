<?php
/**
 * Single post template (blog posts).
 */
get_header();
get_template_part('template-parts/section', 'ways');
?>
<?php
if (have_posts()) :
    while (have_posts()) :
        the_post();

        $article_content = apply_filters('the_content', get_the_content());
        $article_content = preg_replace_callback(
            '/<blockquote([^>]*)class="([^"]*wp-block-quote[^"]*)"([^>]*)>(.*?)<\/blockquote>/si',
            static function ($matches) {
                $blockquote_inner = $matches[4];

                // If author is already provided as cite, keep original markup.
                if (stripos($blockquote_inner, '<cite') !== false) {
                    return $matches[0];
                }

                if (!preg_match('/<p[^>]*>(.*?)<\/p>/si', $blockquote_inner, $paragraph_match)) {
                    return $matches[0];
                }

                $raw_text = trim(
                    wp_strip_all_tags(
                        html_entity_decode($paragraph_match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    )
                );

                // Supported input format: "Quote text" - Author.
                if (!preg_match('/^[\"«“](.+)[\"»”]\s*[-—]\s*(.+)$/u', $raw_text, $parts)) {
                    return $matches[0];
                }

                $quote_text = trim($parts[1]);
                $author_text = trim($parts[2]);

                if ($quote_text === '' || $author_text === '') {
                    return $matches[0];
                }

                return '<blockquote' . $matches[1] . 'class="' . $matches[2] . '"' . $matches[3] . '>'
                    . '<p><em>' . esc_html($quote_text) . '</em></p>'
                    . '<cite>— ' . esc_html($author_text) . ' —</cite>'
                    . '</blockquote>';
            },
            $article_content
        );
        $has_inline_image = stripos($article_content, '<img') !== false || stripos($article_content, 'wp-block-image') !== false;

        if (has_post_thumbnail() && !$has_inline_image) {
            $featured_image = get_the_post_thumbnail(
                get_the_ID(),
                'large',
                array(
                    'class' => 'post-main__img',
                    'loading' => 'eager',
                )
            );

            if (preg_match('/<\/p>/i', $article_content)) {
                $article_content = preg_replace('/<\/p>/i', '</p>' . $featured_image, $article_content, 1);
            } else {
                $article_content = $featured_image . $article_content;
            }
        }

        $categories = get_the_category();
        $main_category = !empty($categories) ? $categories[0] : null;

        $count_reading_minutes = static function ($content) {
            $plain_text = wp_strip_all_tags((string) $content);

            // Count words in any language (including Cyrillic), not only ASCII.
            preg_match_all('/[\p{L}\p{N}\']+/u', $plain_text, $matches);
            $word_count = isset($matches[0]) ? count($matches[0]) : 0;

            return max(1, (int) ceil($word_count / 180));
        };

        if (function_exists('reading_time')) {
            $reading_minutes = (int) reading_time();
        } else {
            $reading_minutes = $count_reading_minutes(get_the_content());
        }

        $author_id = (int) get_the_author_meta('ID');
        $author_avatar_id = function_exists('get_field') ? get_field('user_avatar', 'user_' . $author_id) : 0;
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_label = 'Автор';
        $share_links = array();
        $share_links_seen = array();

        if (function_exists('get_field')) {
            $get_acf_field_with_default = static function ($field_name, $source = 'option') {
                $value = get_field($field_name, $source);
                $url = '';

                if (is_string($value)) {
                    $url = trim($value);
                } elseif (is_array($value)) {
                    $url = trim((string) ($value['url'] ?? $value['link'] ?? $value['value'] ?? ''));
                }

                if ($url !== '' || !function_exists('get_field_object')) {
                    return $url;
                }

                // For newly added fields in options page, ACF may not write option value yet.
                $field_object = get_field_object($field_name, $source, false, false);
                if (is_array($field_object)) {
                    $default_value = $field_object['default_value'] ?? '';
                    if (is_string($default_value)) {
                        return trim($default_value);
                    }
                    if (is_array($default_value)) {
                        return trim((string) ($default_value['url'] ?? $default_value['link'] ?? $default_value['value'] ?? ''));
                    }
                }

                return '';
            };

            $extract_acf_url = static function ($value) {
                if (is_string($value)) {
                    return trim($value);
                }

                if (is_array($value)) {
                    // ACF Link/Image fields often return arrays.
                    if (!empty($value['url']) && is_string($value['url'])) {
                        return trim($value['url']);
                    }
                    if (!empty($value['link']) && is_string($value['link'])) {
                        return trim($value['link']);
                    }
                    if (!empty($value['value']) && is_string($value['value'])) {
                        return trim($value['value']);
                    }
                }

                return '';
            };

            $add_share_link = static function ($url, $icon_id, $label) use (&$share_links, &$share_links_seen) {
                $url = trim((string) $url);
                if ($url === '') {
                    return;
                }

                $key = strtolower($url);
                if (isset($share_links_seen[$key])) {
                    return;
                }

                $share_links_seen[$key] = true;
                $share_links[] = array(
                    'url' => $url,
                    'icon_id' => $icon_id,
                    'label' => $label,
                );
            };

            $acf_sources = array(
                'option',
                'user_' . $author_id,
                get_the_ID(),
            );

            // Direct ACF fields from multiple possible locations.
            foreach ($acf_sources as $acf_source) {
                $add_share_link($get_acf_field_with_default('watsapp_link', $acf_source), 'social-whatsapp', 'WhatsApp');
                $add_share_link($get_acf_field_with_default('whatsapp_link', $acf_source), 'social-whatsapp', 'WhatsApp');
                $add_share_link($get_acf_field_with_default('telegram_link', $acf_source), 'social-telegram', 'Telegram');
                $add_share_link($get_acf_field_with_default('tg_link', $acf_source), 'social-telegram', 'Telegram');
                $add_share_link($get_acf_field_with_default('vk_link', $acf_source), 'social-vk', 'VK');
                $add_share_link($get_acf_field_with_default('vkontakte_link', $acf_source), 'social-vk', 'VK');
            }

            // Fallback from contacts repeater, if it is used in admin.
            $contacts_social_links = get_field('contacts_social_links', 'option');
            if (is_array($contacts_social_links)) {
                foreach ($contacts_social_links as $social_item) {
                    if (!is_array($social_item)) {
                        continue;
                    }
                    $url = $extract_acf_url($social_item['social_url'] ?? '');
                    $detector = strtolower((string) ($social_item['social_alt'] ?? '') . ' ' . $url);
                    $icon_id = 'social-link';
                    $label = 'Ссылка';

                    if (strpos($detector, 'whatsapp') !== false || strpos($detector, 'wa.me') !== false) {
                        $icon_id = 'social-whatsapp';
                        $label = 'WhatsApp';
                    } elseif (strpos($detector, 'telegram') !== false || strpos($detector, 't.me') !== false || strpos($detector, 'tg') !== false) {
                        $icon_id = 'social-telegram';
                        $label = 'Telegram';
                    } elseif (strpos($detector, 'vk.com') !== false || strpos($detector, 'vkontakte') !== false || strpos($detector, 'vk') !== false) {
                        $icon_id = 'social-vk';
                        $label = 'VK';
                    } elseif (strpos($detector, 'mailto:') !== false || strpos($detector, '@') !== false || strpos($detector, 'mail') !== false) {
                        $icon_id = 'social-mail';
                        $label = 'E-mail';
                    }

                    $add_share_link($url, $icon_id, $label);
                }
            }
        }
        ?>

        <section class="section-post" id="section-post">
            <div class="container">
                <div class="row">
                    <div class="post-intro">
                        <div class="main-article-into">
                            <?php if ($main_category) : ?>
                                <div class="article-cat"><?php echo esc_html($main_category->name); ?></div>
                            <?php endif; ?>
                            <time class="article-time">
                                <svg aria-hidden="true" focusable="false" class="article-time__icon">
                                    <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#time-read-icon'); ?>"></use>
                                </svg>
                                <?php echo esc_html(function_exists('yoga_format_minutes') ? yoga_format_minutes($reading_minutes) : ($reading_minutes . ' минут')); ?>
                            </time>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="post-layout">
                        <div class="post-layout__primary">
                            <article class="post-main" id="post-<?php the_ID(); ?>">
                                <div class="entry-content post-main__content">
                                    <?php echo $article_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            </article>

                            <?php if (comments_open() || get_comments_number()) : ?>
                                <div class="post-comments">
                                    <?php comments_template(); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <aside class="post-author">
                            <div class="post-author-fixed">
                                <div class="post-author-main">
                                    <div class="post-author-main__image">
                                        <?php
                                        if ($author_avatar_id) {
                                            echo wp_get_attachment_image($author_avatar_id, 'thumbnail');
                                        } else {
                                            echo get_avatar($author_id, 50);
                                        }
                                        ?>
                                    </div>
                                    <div class="post-author-main__info">
                                        <span class="post-author-job"><?php echo esc_html($author_label); ?></span>
                                        <span class="post-author-name"><?php echo esc_html($author_name); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($share_links)) : ?>
                                    <div class="post-author-social">
                                        <?php foreach ($share_links as $social_item) :
                                            $social_url = $social_item['url'] ?? '';
                                            $social_icon_id = $social_item['icon_id'] ?? 'social-link';
                                            $social_label = $social_item['label'] ?? 'Ссылка';

                                            if ($social_url === '') {
                                                continue;
                                            }
                                            ?>
                                            <a href="<?php echo esc_url($social_url); ?>" class="post-author-social__item" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($social_label); ?>">
                                                <svg aria-hidden="true" focusable="false">
                                                    <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . $social_icon_id); ?>"></use>
                                                </svg>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <?php
        $popular_posts = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'post__not_in' => array(get_the_ID()),
            'ignore_sticky_posts' => true,
        ));

        if ($popular_posts->have_posts()) :
            ?>
            <section class="section-popular-articles" id="section-popular-articles">
                <div class="container">
                    <div class="row">
                        <div class="popular-articles">
                            <div class="popular-articles__intro blog-articles__intro">
                                <h3>Популярное</h3>
                                <div class="arrows-slick">
                                    <div class="arrows-slick__arrow slick-prev">
                                        <svg aria-hidden="true" focusable="false">
                                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                                        </svg>
                                    </div>
                                    <div class="arrows-slick__arrow slick-next">
                                        <svg aria-hidden="true" focusable="false">
                                            <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#slick-arrow'); ?>"></use>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="popular-articles__media">
                                <div class="popular-articles-slider">
                                    <?php while ($popular_posts->have_posts()) : $popular_posts->the_post(); ?>
                                        <div class="blog-article-item">
                                            <div class="blog-article-item__image">
                                                <?php if (has_post_thumbnail()) : ?>
                                                    <?php the_post_thumbnail('medium'); ?>
                                                <?php endif; ?>
                                            </div>

                                            <div class="blog-article-item__date">
                                                <time class="article-time"><?php echo esc_html(get_the_date('j F, Y')); ?></time>
                                                <time class="article-time article-time_time">
                                                    <?php
                                                    $popular_reading_minutes = function_exists('reading_time')
                                                        ? (int) reading_time()
                                                        : $count_reading_minutes(get_the_content());

                                                    echo esc_html(function_exists('yoga_format_minutes') ? yoga_format_minutes($popular_reading_minutes, true) : ($popular_reading_minutes . ' мин'));
                                                    ?>
                                                </time>
                                            </div>

                                            <h4><?php the_title(); ?></h4>

                                            <div class="article-btn">Читать</div>
                                            <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"></a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
        endif;
        wp_reset_postdata();
        get_template_part('template-parts/section', 'subscription');
    endwhile;
else :
    ?>
    <section class="section-post" id="section-post">
        <div class="container">
            <div class="row">
                <p>Запись не найдена.</p>
            </div>
        </div>
    </section>
    <?php
endif;

get_footer();
