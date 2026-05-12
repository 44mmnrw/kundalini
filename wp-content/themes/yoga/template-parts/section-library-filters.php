<?php
/**
 * Мобильные фильтры библиотеки — макет Figma pop_up (node 582:10993).
 * Те же input, что в .filter (label[for] без дублирования полей).
 */
$theme_uri = get_template_directory_uri();

$difficulty_terms = get_terms(array(
	'taxonomy' => 'practice-difficulty',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
$duration_terms = get_terms(array(
	'taxonomy' => 'practice-duration',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
$goal_terms = get_terms(array(
	'taxonomy' => 'practice-goal',
	'hide_empty' => false,
	'orderby' => 'name',
	'order' => 'ASC',
));
?>
<div class="library-filters-screen" id="library-filters-screen" aria-hidden="true">
	<div class="library-filters-screen__backdrop" aria-hidden="true"></div>
	<div class="library-filters-screen__panel">
		<button type="button" class="library-filters-screen__close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
			<span class="library-filters-screen__close-lines" aria-hidden="true"></span>
		</button>
		<div class="library-filters-screen__scroll">
			<section class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По сложности', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) :
						foreach ($difficulty_terms as $index => $difficulty_term) :
							$input_id = 'library-filter-difficulty-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="library-filters-screen__row checkbox-item">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $difficulty_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</section>

			<section class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По продолжительности', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($duration_terms) && !is_wp_error($duration_terms)) :
						foreach ($duration_terms as $index => $duration_term) :
							$input_id = 'library-filter-duration-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="library-filters-screen__row checkbox-item">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $duration_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</section>

			<section class="library-filters-screen__block">
				<h3 class="library-filters-screen__heading"><?php esc_html_e('По цели', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($goal_terms) && !is_wp_error($goal_terms)) :
						foreach ($goal_terms as $index => $goal_term) :
							$input_id = 'library-filter-goal-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="library-filters-screen__row checkbox-item">
								<span class="library-filters-screen__box checkbox" aria-hidden="true"></span>
								<span class="library-filters-screen__text"><?php echo esc_html((string) $goal_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</section>
		</div>
	</div>
</div>
