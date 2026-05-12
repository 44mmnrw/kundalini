<?php
$current_term = get_queried_object();
$category_terms = array();
$default_library_term_id = 0;

if ($current_term instanceof WP_Term && $current_term->taxonomy === 'practice-type') {
    $default_library_term_id = (int) $current_term->term_id;
    $category_terms = get_terms(array(
        'taxonomy' => 'practice-type',
        'parent' => (int) $current_term->term_id,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ));

    if (!empty($category_terms) && !is_wp_error($category_terms)) {
        usort($category_terms, static function ($a, $b) {
            $term_a_id = (int) $a->term_id;
            $term_b_id = (int) $b->term_id;
            $has_order_a = metadata_exists('term', $term_a_id, 'practice_type_card_order');
            $has_order_b = metadata_exists('term', $term_b_id, 'practice_type_card_order');

            if ($has_order_a !== $has_order_b) {
                return $has_order_a ? -1 : 1;
            }

            $order_a = $has_order_a ? (int) get_term_meta($term_a_id, 'practice_type_card_order', true) : PHP_INT_MAX;
            $order_b = $has_order_b ? (int) get_term_meta($term_b_id, 'practice_type_card_order', true) : PHP_INT_MAX;

            if ($order_a === $order_b) {
                return strcasecmp((string) $a->name, (string) $b->name);
            }

            return $order_a <=> $order_b;
        });
    }
}

$library_difficulty_terms = get_terms(array(
	'taxonomy' => 'practice-difficulty',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
$library_duration_terms = get_terms(array(
	'taxonomy' => 'practice-duration',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
$library_goal_terms = get_terms(array(
	'taxonomy' => 'practice-goal',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
if (!is_array($library_difficulty_terms) || is_wp_error($library_difficulty_terms)) {
	$library_difficulty_terms = array();
}
if (!is_array($library_duration_terms) || is_wp_error($library_duration_terms)) {
	$library_duration_terms = array();
}
if (!is_array($library_goal_terms) || is_wp_error($library_goal_terms)) {
	$library_goal_terms = array();
}
?>

<section class="section-library" id="section-library" data-default-term-id="<?php echo esc_attr((string) $default_library_term_id); ?>">
	<div class="container">
		<div class="row">
			<div class="library-form">
				<form id="practice-filter-form" action="#" method="get">
					<div class="library-form-main">
						<div class="form-search">
							<div class="form-categories">
								<div class="form-categories__value">
									<?php if ($current_term instanceof WP_Term && $current_term->taxonomy === 'practice-type') : ?>
										<span class="active" data-target="<?php echo esc_attr((string) $default_library_term_id); ?>"><?php echo esc_html($current_term->name); ?></span>
									<?php endif; ?>
									<?php if (!empty($category_terms) && !is_wp_error($category_terms)) : ?>
										<?php foreach ($category_terms as $term) : ?>
											<span data-target="<?php echo esc_attr((string) $term->term_id); ?>"><?php echo esc_html($term->name); ?></span>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							</div>
							<input type="text" class="input" name="s" placeholder="Что ищете?">
							<input type="submit" id="library-btn">
							<label for="library-btn" class="form-search__btn" aria-label="Искать">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/library-btn-arrow.png'); ?>" class="active" alt="">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/library-btn-arrow_purple.png'); ?>" alt="">
							</label>
							<div class="form-search-list"></div>

							<div class="form-cat-list">
								<?php if ($current_term instanceof WP_Term && $current_term->taxonomy === 'practice-type') : ?>
									<div class="form-cat-list__item active" data-target="<?php echo esc_attr((string) $default_library_term_id); ?>">
										<a href="<?php echo esc_url(get_term_link($current_term)); ?>">
											<span><?php echo esc_html($current_term->name); ?></span>
										</a>
									</div>
								<?php endif; ?>
								<?php if (!empty($category_terms) && !is_wp_error($category_terms)) : ?>
									<?php foreach ($category_terms as $term) : ?>
										<?php $term_link = get_term_link($term); ?>
										<?php if (!is_wp_error($term_link)) : ?>
											<div class="form-cat-list__item" data-target="<?php echo esc_attr((string) $term->term_id); ?>" data-link="<?php echo esc_url($term_link); ?>">
												<a href="<?php echo esc_url($term_link); ?>">
													<span><?php echo esc_html($term->name); ?></span>
												</a>
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>

						<div class="filter-btn">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/filter-img.png'); ?>" alt="" class="filter-btn__img filter-btn__img_main active">
							<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/filter-close.png'); ?>" alt="" class="filter-btn__img">
							<span>0</span>
						</div>
					</div>
					<div class="filter">
						<div class="filter-item">
							<div class="filter-item__main">
								<span>По сложности</span>
							</div>
							<div class="filter-item__list">
								<?php foreach ($library_difficulty_terms as $index => $difficulty_term) : ?>
									<?php $input_id = 'library-filter-difficulty-' . ($index + 1); ?>
									<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item">
										<div class="checkbox library-filter-faux-checkbox"></div>
										<span><?php echo esc_html((string) $difficulty_term->name); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="filter-item">
							<div class="filter-item__main">
								<span>По продолжительности</span>
							</div>
							<div class="filter-item__list">
								<?php foreach ($library_duration_terms as $index => $duration_term) : ?>
									<?php $input_id = 'library-filter-duration-' . ($index + 1); ?>
									<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item">
										<div class="checkbox library-filter-faux-checkbox"></div>
										<span><?php echo esc_html((string) $duration_term->name); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="filter-item">
							<div class="filter-item__main">
								<span>По цели</span>
							</div>
							<div class="filter-item__list">
								<?php foreach ($library_goal_terms as $index => $goal_term) : ?>
									<?php $input_id = 'library-filter-goal-' . ($index + 1); ?>
									<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item">
										<div class="checkbox library-filter-faux-checkbox"></div>
										<span><?php echo esc_html((string) $goal_term->name); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

					</div>

				</form>
			</div>
		</div>

		<div class="row">
			<div class="library" id="practice-list">
				<?php if (!empty($category_terms) && !is_wp_error($category_terms)) : ?>
					<?php foreach ($category_terms as $term) : ?>
						<?php
						$term_ref = 'practice-type_' . (int) $term->term_id;
						$term_color = function_exists('get_field') ? (string) get_field('practice_type_card_color', $term_ref) : '';
						$term_image = function_exists('get_field') ? get_field('practice_type_card_image', $term_ref) : '';
						$term_class = 'library-item_violet';
						$term_image_url = '';
						$term_description = wp_trim_words(wp_strip_all_tags((string) $term->description), 18, '...');

						$term_color = strtolower(trim($term_color));
						if ($term_color === '') {
							$term_color = strtolower(trim((string) get_term_meta((int) $term->term_id, 'practice_type_card_color', true)));
						}

						if ($term_color === 'green') {
							$term_class = 'library-item_green';
						} elseif ($term_color === 'violet_alt') {
							$term_class = 'library-item_violet_alt';
						} elseif ($term_color === 'pink') {
							$term_class = 'library-item_pink';
						}

						if (is_array($term_image)) {
							if (!empty($term_image['url'])) {
								$term_image_url = (string) $term_image['url'];
							} elseif (!empty($term_image['ID'])) {
								$term_image_url = (string) wp_get_attachment_image_url((int) $term_image['ID'], 'large');
							}
						} elseif (is_numeric($term_image)) {
							$term_image_url = (string) wp_get_attachment_image_url((int) $term_image, 'large');
						} elseif (is_string($term_image)) {
							$term_image_url = $term_image;
						}

						$term_link = get_term_link($term);
						if (is_wp_error($term_link)) {
							continue;
						}
						?>
						<div class="library-item <?php echo esc_attr($term_class); ?>">
							<div class="library-item__bg"></div>
							<div class="library-item__cat">
								<?php echo esc_html($term->name); ?>
							</div>
							<p class="library-item__text"><?php echo esc_html($term_description); ?></p>
							<div class="library-item__img">
								<?php if ($term_image_url) : ?>
									<img src="<?php echo esc_url($term_image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
								<?php endif; ?>
							</div>
							<div class="library-item__btn">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/library-card-corner-icon.svg'); ?>" alt="">
							</div>
							<a href="<?php echo esc_url($term_link); ?>" class="library-item__link" aria-label="<?php echo esc_attr($term->name); ?>"></a>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p>Категории практик не найдены.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php
get_template_part('template-parts/section', 'library-filters', array(
	'difficulty_terms' => $library_difficulty_terms,
	'duration_terms' => $library_duration_terms,
	'goal_terms' => $library_goal_terms,
));
?>
