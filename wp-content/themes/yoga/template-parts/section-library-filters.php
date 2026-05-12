<?php
/**
 * Мобильные фильтры библиотеки — Figma pop_up (node 582:10993).
 * Строки — div + явный тап в script.js; input с form= для связи с #practice-filter-form; десктоп: label[for] в section-library.
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
if (!is_array($difficulty_terms) || is_wp_error($difficulty_terms)) {
	$difficulty_terms = array();
}
if (!is_array($duration_terms) || is_wp_error($duration_terms)) {
	$duration_terms = array();
}
if (!is_array($goal_terms) || is_wp_error($goal_terms)) {
	$goal_terms = array();
}
?>
<div class="library-filters-screen" id="library-filters-screen" aria-hidden="true">
	<div class="library-filters-screen__backdrop" aria-hidden="true"></div>
	<div class="library-filters-screen__panel">
		<button type="button" class="library-filters-screen__close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
			<span class="library-filters-screen__close-lines" aria-hidden="true"></span>
		</button>
		<div class="library-filters-screen__scroll">
			<div class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По сложности', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) :
						foreach ($difficulty_terms as $index => $difficulty_term) :
							$input_id = 'library-filter-difficulty-' . ($index + 1);
							?>
							<div class="library-filters-screen__row checkbox-item">
								<input type="checkbox" class="library-filter-input" id="<?php echo esc_attr($input_id); ?>" name="practice-difficulty[]" value="<?php echo esc_attr((string) $difficulty_term->slug); ?>" form="practice-filter-form" tabindex="-1">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $difficulty_term->name); ?></span>
							</div>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</div>

			<div class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По продолжительности', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($duration_terms) && !is_wp_error($duration_terms)) :
						foreach ($duration_terms as $index => $duration_term) :
							$input_id = 'library-filter-duration-' . ($index + 1);
							?>
							<div class="library-filters-screen__row checkbox-item">
								<input type="checkbox" class="library-filter-input" id="<?php echo esc_attr($input_id); ?>" name="practice-duration[]" value="<?php echo esc_attr((string) $duration_term->slug); ?>" form="practice-filter-form" tabindex="-1">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $duration_term->name); ?></span>
							</div>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</div>

			<div class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По цели', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($goal_terms) && !is_wp_error($goal_terms)) :
						foreach ($goal_terms as $index => $goal_term) :
							$input_id = 'library-filter-goal-' . ($index + 1);
							?>
							<div class="library-filters-screen__row checkbox-item">
								<input type="checkbox" class="library-filter-input" id="<?php echo esc_attr($input_id); ?>" name="practice-goal[]" value="<?php echo esc_attr((string) $goal_term->slug); ?>" form="practice-filter-form" tabindex="-1">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $goal_term->name); ?></span>
							</div>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</div>
		</div>
	</div>
</div>
