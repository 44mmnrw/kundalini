<?php
/**
 * AJAX-обработчики: practice search.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

	add_action('wp_ajax_filter_practices', 'filter_practices_callback');
	add_action('wp_ajax_nopriv_filter_practices', 'filter_practices_callback');

	function filter_practices_callback() {
		check_ajax_referer('yoga_ajax_nonce', 'nonce');

		$filters = $_POST['filters'] ?? [];
		if (!is_array($filters)) {
			$filters = [];
		}
		$search  = sanitize_text_field($_POST['search'] ?? '');
		$term_id = intval($_POST['term_id'] ?? 0);

		$tax_query = [];
		if ($term_id) {
			$term_ids = array($term_id);
			$children = get_term_children($term_id, 'practice-type');
			if (!is_wp_error($children) && !empty($children)) {
				$term_ids = array_merge($term_ids, array_map('intval', $children));
			}
			$term_ids = array_values(array_unique(array_filter($term_ids, static function ($id) {
				return (int) $id > 0;
			})));

			$tax_query[] = [
            'taxonomy'         => 'practice-type',
            'field'            => 'term_id',
            'terms'            => $term_ids,
            'include_children' => false,
			];
		}

		foreach ($filters as $taxonomy => $terms) {
			$taxonomy = sanitize_text_field((string) $taxonomy);
			if ($taxonomy === '' || empty($terms)) {
				continue;
			}
			if (!is_array($terms)) {
				$terms = array($terms);
			}
			$terms = array_values(array_filter(array_map('sanitize_text_field', $terms)));
			if (empty($terms)) {
				continue;
			}
			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $terms,
			];
		}

		$args = [
        'post_type'      => 'practice',
        'posts_per_page' => -1,
		];
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}
		if (!empty($search)) {
			$args['s'] = $search;
		}

		$query = new WP_Query($args);

		if ($query->have_posts()) {
			while ($query->have_posts()) {
			$query->the_post();
			$card_data = yoga_get_practice_type_card_data(get_the_ID());
			$library_variant_class = $card_data['class'];
			$library_term_image_url = $card_data['image_url'];
			$practice_level_raw = function_exists('yoga_get_practice_level_raw_for_cards')
				? yoga_get_practice_level_raw_for_cards((int) get_the_ID())
				: '';
			$practice_level_label = function_exists('yoga_normalize_practice_level_label')
				? yoga_normalize_practice_level_label($practice_level_raw !== '' ? $practice_level_raw : 'новичок')
				: ($practice_level_raw !== '' ? $practice_level_raw : 'новичок');
			?>
            <div class="library-item<?php echo $library_variant_class ? ' ' . esc_attr($library_variant_class) : ''; ?>">
                <div class="library-item__bg"></div>
                <div class="library-item__cat">
                    <?php echo esc_html($practice_level_label); ?>
                    <a href="<?php the_permalink(); ?>" target="_blank"></a>
				</div>
                <p class="library-item__text"><?php echo get_the_excerpt(); ?></p>
                <div class="library-item__img">
                    <?php if ($library_term_image_url) : ?>
						<img src="<?php echo esc_url($library_term_image_url); ?>" alt="<?php the_title_attribute(); ?>">
					<?php elseif (has_post_thumbnail()) : ?>
						<?php the_post_thumbnail('medium'); ?>
					<?php endif; ?>
				</div>
                <div class="library-item__btn">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/svg/library-card-corner-icon.svg" alt="">
				</div>
                <a href="<?php the_permalink(); ?>" class="library-item__link"></a>
			</div>
			<?php }
			wp_reset_postdata();
			} else {
			echo '<div class="practice-search-empty"><p class="practice-search-empty__count">Найдено: 0</p><p class="practice-search-empty__message">По вашему запросу ничего не найдено, попробуйте другой запрос.</p></div>';
		}

		wp_die();
	}


	add_action('wp_ajax_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	add_action('wp_ajax_nopriv_filter_practices_kriyi', 'filter_practices_callback_kriyi');

	add_action('wp_ajax_search_practices_suggest', 'yoga_search_practices_suggest');
	add_action('wp_ajax_nopriv_search_practices_suggest', 'yoga_search_practices_suggest');

	function filter_practices_callback_kriyi() {

		check_ajax_referer('yoga_ajax_nonce', 'nonce');


		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => -1,
        'post_status' => 'publish'
		);

		$raw_filters = array();
		if (!empty($_POST['filters']) && is_array($_POST['filters'])) {
			$raw_filters = $_POST['filters'];
		}

		$selected_type_terms = array();
		if (!empty($raw_filters['practice-type']) && is_array($raw_filters['practice-type'])) {
			$selected_type_terms = array_values(array_unique(array_map('intval', $raw_filters['practice-type'])));
			$selected_type_terms = array_filter($selected_type_terms, static function ($term_id) {
				return $term_id > 0;
			});
		}


		if (!empty($_POST['term_id']) && empty($selected_type_terms)) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'practice-type',
					'field' => 'term_id',
					'terms' => intval($_POST['term_id'])
				)
			);
		}

		$search_term = '';
		if (!empty($_POST['search'])) {
			$search_term = sanitize_text_field($_POST['search']);
		}
		$show_all = !empty($_POST['show_all']);
		$library_results = !empty($_POST['library_results']);
		$library_page = $library_results ? max(1, (int) ($_POST['library_page'] ?? 1)) : 1;


		if (!empty($raw_filters)) {
			$filters = $raw_filters;

			if (!isset($args['tax_query'])) {
				$args['tax_query'] = array('relation' => 'AND');
				} else {
				$args['tax_query']['relation'] = 'AND';
			}

			foreach ($filters as $taxonomy => $terms) {
				if (!empty($terms)) {
					$args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => array_map('intval', $terms),
                    'operator' => 'IN'
					);
				}
			}
		}

		$args['orderby'] = 'date';
		$args['order'] = 'DESC';
		if ($library_results) {
			$args['posts_per_page'] = 10;
			$args['paged'] = $library_page;
		}

		if ($search_term !== '') {
			$search_ids = array();


			$text_search_args = $args;
			$text_search_args['fields'] = 'ids';
			$text_search_args['posts_per_page'] = -1;
			$text_search_args['s'] = $search_term;
			$text_search_query = new WP_Query($text_search_args);
			if (!empty($text_search_query->posts)) {
				$search_ids = array_merge($search_ids, $text_search_query->posts);
			}


			global $wpdb;
			$like_search = '%' . $wpdb->esc_like($search_term) . '%';
			$meta_sql = $wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				   AND p.post_status = %s
				   AND pm.meta_key NOT LIKE %s
				   AND pm.meta_value LIKE %s",
				'practice',
				'publish',
				'\_%',
				$like_search
			);
			$meta_search_ids = $wpdb->get_col($meta_sql);
			if (!empty($meta_search_ids)) {
				$search_ids = array_merge($search_ids, $meta_search_ids);
			}

			$search_ids = array_values(array_unique(array_map('intval', $search_ids)));
			$args['post__in'] = !empty($search_ids) ? $search_ids : array(0);
		}

		$query = new WP_Query($args);
		$count = $query->found_posts;


		ob_start();

		if ($query->have_posts()) :
		$user_id = get_current_user_id();
		$user_favorites = get_user_meta($user_id, 'favorite_practices', true);
		if (!is_array($user_favorites)) {
			$user_favorites = array();
		}
        $item_count = 0;
        while ($query->have_posts()) : $query->the_post();
		$item_count++;
		$practice_level_raw_k = function_exists('yoga_get_practice_level_raw_for_cards')
			? yoga_get_practice_level_raw_for_cards((int) get_the_ID())
			: '';
		$practice_level = yoga_normalize_practice_level_label($practice_level_raw_k !== '' ? $practice_level_raw_k : 'новичок');
		$practice_description = get_field('short_description') ?: get_the_excerpt();
		$practice_image = yoga_get_practice_card_image_url((int) get_the_ID(), 'large');
		$is_favorite = in_array(get_the_ID(), $user_favorites, true);
		$hidden_class = (!$show_all && !$library_results && $item_count > 10) ? 'hidden' : '';
		$tariff_lock_class = function_exists('yoga_practice_card_tariff_lock_class')
			? yoga_practice_card_tariff_lock_class((int) get_the_ID(), $user_id)
			: '';
	?>

	<div class="kriyi-item <?php echo esc_attr(trim($hidden_class . ' ' . $tariff_lock_class)); ?>">
		<div class="kriyi-item__inner">
			<a href="<?php the_permalink(); ?>"></a>
			<span class="kriya-level"><?php echo esc_html($practice_level); ?></span>
			<div class="kriya-info">
				<h3><?php the_title(); ?></h3>
				<p><?php echo esc_html($practice_description); ?></p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img">
					<?php if ($practice_image !== '') : ?>
					<img src="<?php echo esc_url($practice_image); ?>" alt="<?php the_title(); ?>">
					<?php endif; ?>
				</div>
				<div class="kriya-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo get_the_ID(); ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Убрать' : 'В избранное'); ?>">
					<span class="kriya-fav__icon" aria-hidden="true">
						<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
					</span>
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
        endwhile;


		if (!$show_all && !$library_results && $count > 10) :
		for ($i = 0; $i < 2; $i++) :
	?>
	<div class="kriyi-item kriyi-item_last hidden">
		<div class="kriyi-item__inner">
			<a href="#"></a>
			<span class="kriya-level">Начинающий</span>
			<div class="kriya-info">
				<h3>Остальные крийи</h3>
				<p>Показать все доступные практики</p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img"></div>
				<div class="kriya-fav kriya-fav--icon-only">
					<span class="kriya-fav__icon" aria-hidden="true">
						<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
					</span>
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		endfor;
        endif;

		else :
        echo '<div class="practice-search-empty"><p class="practice-search-empty__count">Найдено: 0</p><p class="practice-search-empty__message">По вашему запросу ничего не найдено, попробуйте другой запрос.</p></div>';
		endif;

		$html = ob_get_clean();


		wp_send_json_success(array(
        'html' => $html,
		'count' => $count,
		'has_more' => $library_results && ($library_page * 10 < $count)
		));

		wp_die();
	}

	function yoga_search_practices_suggest() {
		check_ajax_referer('yoga_ajax_nonce', 'nonce');

		$query = sanitize_text_field((string) ($_POST['query'] ?? ''));
		$term_id = isset($_POST['term_id']) ? (int) $_POST['term_id'] : 0;
		$query_lc = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);

		if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
			wp_send_json_success(array('items' => array()));
		}

		global $wpdb;
		$like_search = '%' . $wpdb->esc_like($query) . '%';

		$term_ids = array();
		if ($term_id > 0) {
			$term_ids[] = $term_id;
			$children = get_term_children($term_id, 'practice-type');
			if (!is_wp_error($children) && !empty($children)) {
				$term_ids = array_merge($term_ids, array_map('intval', $children));
			}
			$term_ids = array_values(array_unique(array_filter($term_ids, static function ($id) {
				return $id > 0;
			})));
		}

		$sql_params = array(
			$like_search,
			'practice',
			'publish',
		);

		$tax_join = '';
		$tax_where = '';
		if (!empty($term_ids)) {
			$placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
			$tax_join = " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			              INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ";
			$tax_where = " AND tt.taxonomy = 'practice-type' AND tt.term_id IN ({$placeholders}) ";
			$sql_params = array_merge($sql_params, $term_ids);
		}

		$sql_params[] = $like_search;
		$sql_params[] = $like_search;
		$sql_params[] = $like_search;
		$sql_params[] = 60;

		$sql = "
			SELECT
				p.ID,
				p.post_title,
				p.post_excerpt,
				p.post_content,
				MAX(CASE WHEN pm.meta_key NOT LIKE '\\\\_%' AND pm.meta_value LIKE %s THEN 1 ELSE 0 END) AS has_meta_match
			FROM {$wpdb->posts} p
			{$tax_join}
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
			WHERE p.post_type = %s
			  AND p.post_status = %s
			  {$tax_where}
			GROUP BY p.ID
			HAVING (
				p.post_title LIKE %s
				OR p.post_excerpt LIKE %s
				OR p.post_content LIKE %s
				OR has_meta_match = 1
			)
			ORDER BY p.post_date DESC
			LIMIT %d
		";

		$prepared_sql = $wpdb->prepare($sql, $sql_params);
		$rows = $wpdb->get_results($prepared_sql, ARRAY_A);
		if (empty($rows)) {
			wp_send_json_success(array('items' => array()));
		}

		$ranked = array();
		foreach ($rows as $row) {
			$post_id = isset($row['ID']) ? (int) $row['ID'] : 0;
			if ($post_id <= 0) {
				continue;
			}

			$title = isset($row['post_title']) ? (string) $row['post_title'] : '';
			$excerpt = isset($row['post_excerpt']) ? (string) $row['post_excerpt'] : '';
			$content = isset($row['post_content']) ? wp_strip_all_tags((string) $row['post_content']) : '';
			$title_lc = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
			$excerpt_lc = function_exists('mb_strtolower') ? mb_strtolower($excerpt, 'UTF-8') : strtolower($excerpt);
			$content_lc = function_exists('mb_strtolower') ? mb_strtolower($content, 'UTF-8') : strtolower($content);

			$score = 0;
			if ($title_lc === $query_lc) {
				$score += 1000;
			}
			if (strpos($title_lc, $query_lc) === 0) {
				$score += 650;
			} elseif (mb_stripos($title_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 450;
			}
			if (mb_stripos($excerpt_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 220;
			}
			if (mb_stripos($content_lc, $query_lc, 0, 'UTF-8') !== false) {
				$score += 120;
			}
			if (!empty($row['has_meta_match'])) {
				$score += 150;
			}

			$url = get_permalink($post_id);
			if ($title === '' || !$url || $score <= 0) {
				continue;
			}

			$ranked[] = array(
				'score' => $score,
				'id' => (int) $post_id,
				'title' => (string) $title,
				'url' => (string) $url,
			);
		}

		if (empty($ranked)) {
			wp_send_json_success(array('items' => array()));
		}

		usort($ranked, static function ($a, $b) {
			if ($a['score'] === $b['score']) {
				return strnatcasecmp($a['title'], $b['title']);
			}
			return ($a['score'] > $b['score']) ? -1 : 1;
		});

		$items = array();
		foreach ($ranked as $row) {
			$items[] = array(
				'id' => $row['id'],
				'title' => $row['title'],
				'url' => $row['url'],
			);
			if (count($items) >= 8) {
				break;
			}
		}

		wp_send_json_success(array('items' => $items));
	}
