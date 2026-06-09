<?php
/**
 * Универсальный шаблон дочерней категории практик (practice-type).
 */
if (!defined('ABSPATH')) {
	exit;
}

$sprite_href = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');

$current_term = get_queried_object();
if (!($current_term instanceof WP_Term) || $current_term->taxonomy !== 'practice-type') {
	return;
}

$parent_term = get_term((int) $current_term->parent, 'practice-type');
if (!($parent_term instanceof WP_Term) || is_wp_error($parent_term)) {
	$parent_term = $current_term;
}

$sibling_terms = get_terms(array(
	'taxonomy' => 'practice-type',
	'parent' => (int) $current_term->parent,
	'hide_empty' => false,
	'exclude' => array((int) $current_term->term_id),
	'orderby' => 'name',
	'order' => 'ASC',
));

$practices = new WP_Query(array(
	'post_type' => 'practice',
	'tax_query' => array(
		array(
			'taxonomy' => 'practice-type',
			'field' => 'term_id',
			'terms' => (int) $current_term->term_id,
		),
	),
	'posts_per_page' => -1,
));

$practices_count = (int) $practices->found_posts;

$parent_term_archive_url = get_term_link($parent_term);
$parent_term_link_attr = ($parent_term instanceof WP_Term && ! is_wp_error($parent_term_archive_url))
	? ' data-link="' . esc_attr($parent_term_archive_url) . '"'
	: '';

$current_term_archive_url = get_term_link($current_term);
$current_term_link_attr = (! is_wp_error($current_term_archive_url))
	? ' data-link="' . esc_attr($current_term_archive_url) . '"'
	: '';
?>

<section class="section-kriyi section-practice-category" id="section-kriyi">
	<div class="container">
		<div class="row">
			<div class="kriyi-form">
				<form action="#">
					<div class="kriyi-form-main">
						<div class="form-search">
							<div class="form-categories">
								<div class="form-categories__value">
									<span<?php echo $parent_term_link_attr; ?> data-target="<?php echo esc_attr((string) $parent_term->term_id); ?>">
										<?php echo esc_html($parent_term->name); ?>
									</span>
									<span class="active"<?php echo $current_term_link_attr; ?> data-target="<?php echo esc_attr((string) $current_term->term_id); ?>">
										<?php echo esc_html($current_term->name); ?>
									</span>
									<?php if (!empty($sibling_terms) && !is_wp_error($sibling_terms)) : ?>
										<?php foreach ($sibling_terms as $sibling_term) : ?>
											<?php
											$sibling_archive_url = get_term_link($sibling_term);
											$sibling_link_attr = (! is_wp_error($sibling_archive_url))
												? ' data-link="' . esc_attr($sibling_archive_url) . '"'
												: '';
											?>
											<span<?php echo $sibling_link_attr; ?> data-target="<?php echo esc_attr((string) $sibling_term->term_id); ?>">
												<?php echo esc_html($sibling_term->name); ?>
											</span>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							</div>
							<input type="text" class="input" placeholder="Что ищете?" required>
							<input type="submit" id="library-btn">
							<label for="library-btn" class="form-search__btn" aria-label="Искать">
								<svg class="form-search__btn-icon active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url($sprite_href); ?>#slick-arrow"></use>
								</svg>
								<svg class="form-search__btn-icon form-search__btn-icon_hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false">
									<use href="<?php echo esc_url($sprite_href); ?>#slick-arrow"></use>
								</svg>
							</label>
							<div class="form-search-list"></div>
							<div class="form-cat-list">
								<div class="form-cat-list__item"<?php echo $parent_term_link_attr; ?> data-target="<?php echo esc_attr((string) $parent_term->term_id); ?>">
									<span><?php echo esc_html($parent_term->name); ?></span>
								</div>
								<div class="form-cat-list__item active"<?php echo $current_term_link_attr; ?> data-target="<?php echo esc_attr((string) $current_term->term_id); ?>">
									<span><?php echo esc_html($current_term->name); ?></span>
								</div>
								<?php if (!empty($sibling_terms) && !is_wp_error($sibling_terms)) : ?>
									<?php foreach ($sibling_terms as $sibling_term) : ?>
										<?php
										$sibling_archive_url = get_term_link($sibling_term);
										$sibling_item_link_attr = (! is_wp_error($sibling_archive_url))
											? ' data-link="' . esc_attr($sibling_archive_url) . '"'
											: '';
										?>
										<div class="form-cat-list__item"<?php echo $sibling_item_link_attr; ?> data-target="<?php echo esc_attr((string) $sibling_term->term_id); ?>">
											<span><?php echo esc_html($sibling_term->name); ?></span>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="filter-btn">
							<svg class="filter-btn__icon filter-btn__icon--filter active" viewBox="0 0 28 28" width="28" height="28" aria-hidden="true" focusable="false">
								<use href="<?php echo esc_url($sprite_href); ?>#library-filter-icon"></use>
							</svg>
							<svg class="filter-btn__icon filter-btn__icon--close" viewBox="0 0 18 18" width="22" height="22" aria-hidden="true" focusable="false">
								<use href="<?php echo esc_url($sprite_href); ?>#lk-modal-close"></use>
							</svg>
							<span>1</span>
						</div>
					</div>

					<div class="filter">
						<div class="filter-item">
							<div class="filter-item__main">
								<span>По сложности</span>
							</div>
							<div class="filter-item__list">
								<?php
								$difficulty_terms = get_terms(array(
									'taxonomy' => 'practice-difficulty',
									'hide_empty' => false,
								));
								if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) {
									$i = 1;
									foreach ($difficulty_terms as $term) {
										$level_label = function_exists('yoga_get_practice_difficulty_label')
											? yoga_get_practice_difficulty_label($term)
											: (string) $term->name;
										$level_slug = function_exists('yoga_get_practice_level_slug')
											? yoga_get_practice_level_slug($level_label)
											: '';
										echo '<input type="checkbox" id="filter-dif_' . sprintf('%02d', $i) . '" name="practice-difficulty" value="' . esc_attr((string) $term->term_id) . '">';
										echo '<label for="filter-dif_' . sprintf('%02d', $i) . '" class="checkbox-item" data-level-key="' . esc_attr($level_slug) . '">';
										echo '<div class="checkbox"></div>';
										echo '<span>' . esc_html($level_label) . '</span>';
										echo '</label>';
										$i++;
									}
								}
								?>
							</div>
						</div>

						<div class="filter-item">
							<div class="filter-item__main">
								<span>По продолжительности</span>
							</div>
							<div class="filter-item__list">
								<?php
								$duration_terms = get_terms(array(
									'taxonomy' => 'practice-duration',
									'hide_empty' => false,
									'orderby' => 'name',
									'order' => 'ASC',
								));
								if (!empty($duration_terms) && !is_wp_error($duration_terms)) {
									$i = 1;
									foreach ($duration_terms as $term) {
										echo '<input type="checkbox" id="filter-time_' . sprintf('%02d', $i) . '" name="practice-duration" value="' . esc_attr((string) $term->term_id) . '">';
										echo '<label for="filter-time_' . sprintf('%02d', $i) . '" class="checkbox-item">';
										echo '<div class="checkbox"></div>';
										echo '<span>' . esc_html($term->name) . '</span>';
										echo '</label>';
										$i++;
									}
								}
								?>
							</div>
						</div>

						<div class="filter-item">
							<div class="filter-item__main">
								<span>По цели</span>
							</div>
							<div class="filter-item__list">
								<?php
								$goal_terms = get_terms(array(
									'taxonomy' => 'practice-goal',
									'hide_empty' => false,
								));
								if (!empty($goal_terms) && !is_wp_error($goal_terms)) {
									$i = 1;
									foreach ($goal_terms as $term) {
										echo '<input type="checkbox" id="filter-goal_' . sprintf('%02d', $i) . '" name="practice-goal" value="' . esc_attr((string) $term->term_id) . '">';
										echo '<label for="filter-goal_' . sprintf('%02d', $i) . '" class="checkbox-item">';
										echo '<div class="checkbox"></div>';
										echo '<span>' . esc_html($term->name) . '</span>';
										echo '</label>';
										$i++;
									}
								}
								?>
							</div>
						</div>

						<input type="reset" id="filt-reset">
						<label for="filt-reset" class="form-reset">
							<div class="form-reset__icon">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/form-reset-icon.png'); ?>" alt="" class="active">
								<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/form-reset-icon_active.png'); ?>" alt="">
							</div>
							<span>Очистить</span>
						</label>
					</div>

					<div class="sorting">
						<span class="sorting__result">Найдено: <?php echo esc_html((string) $practices_count); ?></span>
						<div class="sorting-item">
							<div class="sorting-item__main">
								<span>По популярности</span>
							</div>
							<div class="sorting-item__list">
								<div class="sorting-item__list-item active" data-target="popularity"><span>По популярности</span></div>
								<div class="sorting-item__list-item" data-target="newness"><span>По новизне</span></div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="row">
			<div class="kriyi">
				<div class="kriyi__items">
					<?php if ($practices->have_posts()) : ?>
						<?php
						$count = 0;
						while ($practices->have_posts()) :
							$practices->the_post();
							$count++;
							$practice_level_raw = function_exists('yoga_get_practice_level_raw_for_cards')
								? yoga_get_practice_level_raw_for_cards((int) get_the_ID())
								: '';
							$practice_level = function_exists('yoga_normalize_practice_level_label')
								? yoga_normalize_practice_level_label($practice_level_raw !== '' ? $practice_level_raw : 'новичок')
								: ($practice_level_raw !== '' ? $practice_level_raw : 'новичок');
							$practice_description = get_field('short_description') ?: get_the_excerpt();
							$practice_image = yoga_get_practice_card_image_url((int) get_the_ID(), 'large');
							$user_id = get_current_user_id();
							$is_favorite = in_array(get_the_ID(), get_user_meta($user_id, 'favorite_practices', true) ?: array(), true);
							$hidden_class = ($count > 10) ? 'hidden' : '';
							$practice_id = (int) get_the_ID();
							$can_access = function_exists('yoga_user_can_access_practice')
								? yoga_user_can_access_practice($practice_id)
								: true;
							$card_url = function_exists('yoga_get_practice_card_url')
								? yoga_get_practice_card_url($practice_id)
								: get_permalink();
							?>
							<div class="kriyi-item <?php echo esc_attr($hidden_class); ?><?php echo $can_access ? '' : ' kriyi-item--locked'; ?>">
								<div class="kriyi-item__inner">
									<a href="<?php echo esc_url($card_url); ?>"></a>
									<span class="kriya-level"><?php echo esc_html($practice_level); ?></span>
									<div class="kriya-info">
										<h3><?php the_title(); ?></h3>
										<p><?php echo esc_html($practice_description); ?></p>
									</div>
									<div class="kriya-media">
										<div class="kriya-img">
											<?php if ($practice_image !== '') : ?>
											<img src="<?php echo esc_url($practice_image); ?>" alt="<?php the_title_attribute(); ?>">
											<?php endif; ?>
										</div>
										<div class="kriya-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo esc_attr((string) get_the_ID()); ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Убрать' : 'В избранное'); ?>">
											<span class="kriya-fav__icon" aria-hidden="true">
												<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#noun-heart'); ?>"></use></svg>
												<svg class="<?php echo $is_favorite ? 'active' : ''; ?>"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#noun-heart-filled'); ?>"></use></svg>
											</span>
											<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
											<span class="kriya-fav__text kriya-fav__text--remove">Убрать</span>
										</div>
										<div class="kriya-btn">
											<div class="kriya-btn__arrow">
												<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/kriya-btn-arrow.png'); ?>" alt="" class="active">
												<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/kriya-btn-arrow_active.png'); ?>" alt="">
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endwhile; ?>
					<?php else : ?>
						<p class="no-practices">В этой категории пока нет практик.</p>
					<?php endif; ?>
					<?php wp_reset_postdata(); ?>
				</div>

				<?php if ($practices_count > 10) : ?>
					<div class="btn">
						<span class="active">Показать еще</span>
						<span>Свернуть</span>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
