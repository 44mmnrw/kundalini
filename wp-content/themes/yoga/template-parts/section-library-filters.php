<?php
/**
 * Reusable practice library filters panel.
 *
 * @package Yoga
 */
if (!isset($difficulty_terms) || !is_array($difficulty_terms)) {
	$difficulty_terms = get_terms(array(
		'taxonomy' => 'practice-difficulty',
		'hide_empty' => false,
		'orderby' => 'name',
		'order' => 'ASC',
	));
}
if (!isset($duration_terms) || !is_array($duration_terms)) {
	$duration_terms = get_terms(array(
		'taxonomy' => 'practice-duration',
		'hide_empty' => false,
		'orderby' => 'name',
		'order' => 'ASC',
	));
}
if (!isset($goal_terms) || !is_array($goal_terms)) {
	$goal_terms = get_terms(array(
		'taxonomy' => 'practice-goal',
		'hide_empty' => false,
		'orderby' => 'name',
		'order' => 'ASC',
	));
}

$filter_form_id = isset($args['form_id']) ? sanitize_html_class((string) $args['form_id']) : 'practice-filter-form';
$filter_value_field = isset($args['value_field']) && $args['value_field'] === 'term_id' ? 'term_id' : 'slug';
$difficulty_terms = is_array($difficulty_terms) && !is_wp_error($difficulty_terms) ? $difficulty_terms : array();
$duration_terms = is_array($duration_terms) && !is_wp_error($duration_terms) ? $duration_terms : array();
$goal_terms = is_array($goal_terms) && !is_wp_error($goal_terms) ? $goal_terms : array();

$sprite_file = get_template_directory() . '/assets/svg/sprite.svg';
$sprite_version = file_exists($sprite_file) ? (string) filemtime($sprite_file) : wp_get_theme()->get('Version');
$sprite_href = add_query_arg('ver', rawurlencode($sprite_version), get_template_directory_uri() . '/assets/svg/sprite.svg');

$render_filter_rows = static function ($terms, $taxonomy, $id_prefix) use ($filter_form_id, $filter_value_field) {
	foreach (array_values($terms) as $index => $term) {
		if (!($term instanceof WP_Term)) {
			continue;
		}
		$input_id = $id_prefix . '-' . ((int) $index + 1);
		?>
		<div class="library-filters-screen__row checkbox-item" role="checkbox" aria-checked="false" tabindex="0">
			<input type="checkbox" class="library-filter-input" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($taxonomy); ?>[]" value="<?php echo esc_attr((string) $term->{$filter_value_field}); ?>" form="<?php echo esc_attr($filter_form_id); ?>" tabindex="-1">
			<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
			<span class="library-filters-screen__text"><?php echo esc_html((string) $term->name); ?></span>
		</div>
		<?php
	}
};

$goal_terms_by_parent = array();
foreach ($goal_terms as $goal_term) {
	if ($goal_term instanceof WP_Term) {
		$goal_terms_by_parent[(int) $goal_term->parent][] = $goal_term;
	}
}
$goal_root_terms = $goal_terms_by_parent[0] ?? array();
$has_goal_groups = false;
$ungrouped_goal_terms = array();
foreach ($goal_root_terms as $goal_root_term) {
	if (!empty($goal_terms_by_parent[(int) $goal_root_term->term_id])) {
		$has_goal_groups = true;
	} else {
		$ungrouped_goal_terms[] = $goal_root_term;
	}
}
?>
<div class="library-filters-screen" id="library-filters-screen" aria-hidden="true">
	<div class="library-filters-screen__backdrop" aria-hidden="true"></div>
	<aside class="library-filters-screen__panel" aria-label="<?php esc_attr_e('Фильтры практик', 'yoga'); ?>">
		<div class="library-filters-screen__header">
			<h2 class="library-filters-screen__title"><?php esc_html_e('Фильтры', 'yoga'); ?></h2>
			<button type="button" class="library-filters-screen__close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
				<span class="library-filters-screen__close-lines" aria-hidden="true"></span>
			</button>
		</div>

		<div class="library-filters-screen__scroll">
			<section class="library-filters-screen__block">
				<button type="button" class="library-filters-screen__block-toggle" aria-expanded="true">
					<span class="library-filters-screen__heading"><?php esc_html_e('Сложность', 'yoga'); ?></span>
					<svg class="library-filters-screen__arrow" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($sprite_href); ?>#menu-dropdown"></use></svg>
				</button>
				<div class="library-filters-screen__block-content">
					<div class="library-filters-screen__options">
						<?php $render_filter_rows($difficulty_terms, 'practice-difficulty', 'library-filter-difficulty'); ?>
					</div>
				</div>
			</section>

			<section class="library-filters-screen__block library-filters-screen__block--goals">
				<button type="button" class="library-filters-screen__block-toggle" aria-expanded="true">
					<span class="library-filters-screen__heading"><?php esc_html_e('Цели', 'yoga'); ?></span>
					<svg class="library-filters-screen__arrow" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($sprite_href); ?>#menu-dropdown"></use></svg>
				</button>
				<div class="library-filters-screen__block-content library-filters-screen__goal-content">
					<?php if ($has_goal_groups) : ?>
						<?php foreach ($goal_root_terms as $goal_root_term) : ?>
							<?php $goal_children = $goal_terms_by_parent[(int) $goal_root_term->term_id] ?? array(); ?>
							<?php if (!empty($goal_children)) : ?>
								<div class="library-filters-screen__goal-group">
									<h3 class="library-filters-screen__group-heading"><?php echo esc_html((string) $goal_root_term->name); ?></h3>
									<div class="library-filters-screen__options">
										<?php $render_filter_rows($goal_children, 'practice-goal', 'library-filter-goal-' . (int) $goal_root_term->term_id); ?>
									</div>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php if (!empty($ungrouped_goal_terms)) : ?>
							<div class="library-filters-screen__goal-group">
								<div class="library-filters-screen__options">
									<?php $render_filter_rows($ungrouped_goal_terms, 'practice-goal', 'library-filter-goal-other'); ?>
								</div>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<div class="library-filters-screen__options">
							<?php $render_filter_rows($goal_terms, 'practice-goal', 'library-filter-goal'); ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="library-filters-screen__block">
				<button type="button" class="library-filters-screen__block-toggle" aria-expanded="true">
					<span class="library-filters-screen__heading"><?php esc_html_e('Продолжительность', 'yoga'); ?></span>
					<svg class="library-filters-screen__arrow" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($sprite_href); ?>#menu-dropdown"></use></svg>
				</button>
				<div class="library-filters-screen__block-content">
					<div class="library-filters-screen__options">
						<?php $render_filter_rows($duration_terms, 'practice-duration', 'library-filter-duration'); ?>
					</div>
				</div>
			</section>
		</div>

		<div class="library-filters-screen__actions">
			<button type="button" class="library-filters-screen__apply js-library-filters-apply">
				<?php esc_html_e('Показать', 'yoga'); ?>
			</button>
			<button type="button" class="library-filters-screen__reset js-library-filters-reset">
				<span class="library-filters-screen__reset-icon" aria-hidden="true"></span>
				<?php esc_html_e('Очистить', 'yoga'); ?>
			</button>
		</div>
	</aside>
</div>
