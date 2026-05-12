<?php
/**
 * Мобильный полноэкранный экран фильтров библиотеки (макет Figma, ≤991px).
 * Секции «сложность / длительность / цель» используют те же id полей, что и .filter — дубликатов input нет.
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

$blog_url = home_url('/blog/');
$home_url = home_url('/');
$tariffs_url = home_url('/tariffs/');

$goal_slice = 4;
if (!empty($goal_terms) && !is_wp_error($goal_terms)) {
	$goal_visible = array_slice($goal_terms, 0, $goal_slice);
	$goal_extra = array_slice($goal_terms, $goal_slice);
} else {
	$goal_visible = array();
	$goal_extra = array();
}
?>
<div class="library-filters-screen" id="library-filters-screen" aria-hidden="true">
	<div class="library-filters-screen__inner">
		<header class="library-filters-screen__header">
			<h2 class="library-filters-screen__title"><?php esc_html_e('Фильтры', 'yoga'); ?></h2>
			<button type="button" class="library-filters-screen__close" aria-label="<?php esc_attr_e('Закрыть', 'yoga'); ?>">
				<span class="library-filters-screen__close-icon" aria-hidden="true"></span>
			</button>
		</header>

		<div class="library-filters-screen__scroll">
			<section class="library-filters-screen__section">
				<h3 class="library-filters-screen__label"><?php esc_html_e('Тип контента', 'yoga'); ?></h3>
				<ul class="library-filters-screen__content-types">
					<li>
						<a href="<?php echo esc_url($blog_url); ?>" class="library-filters-screen__content-link"><?php esc_html_e('Статьи', 'yoga'); ?></a>
					</li>
					<li>
						<a href="<?php echo esc_url($home_url); ?>#section-videos" class="library-filters-screen__content-link"><?php esc_html_e('Видео', 'yoga'); ?></a>
					</li>
					<li>
						<a href="<?php echo esc_url($tariffs_url); ?>" class="library-filters-screen__content-link"><?php esc_html_e('Курсы', 'yoga'); ?></a>
					</li>
					<li>
						<span class="library-filters-screen__content-link library-filters-screen__content-link_current" aria-current="page"><?php esc_html_e('Практики', 'yoga'); ?></span>
					</li>
				</ul>
			</section>

			<section class="library-filters-screen__section">
				<h3 class="library-filters-screen__label"><?php esc_html_e('Уровень сложности', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) :
						foreach ($difficulty_terms as $index => $difficulty_term) :
							$input_id = 'library-filter-difficulty-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item library-filters-screen__row">
								<div class="checkbox"></div>
								<span><?php echo esc_html((string) $difficulty_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</section>

			<section class="library-filters-screen__section">
				<h3 class="library-filters-screen__label"><?php esc_html_e('Продолжительность', 'yoga'); ?></h3>
				<div class="library-filters-screen__options">
					<?php
					if (!empty($duration_terms) && !is_wp_error($duration_terms)) :
						foreach ($duration_terms as $index => $duration_term) :
							$input_id = 'library-filter-duration-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item library-filters-screen__row">
								<div class="checkbox"></div>
								<span><?php echo esc_html((string) $duration_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
			</section>

			<section class="library-filters-screen__section">
				<h3 class="library-filters-screen__label"><?php esc_html_e('Цель практики', 'yoga'); ?></h3>
				<div class="library-filters-screen__options library-filters-screen__options_goals">
					<?php
					if (!empty($goal_visible)) :
						foreach ($goal_visible as $index => $goal_term) :
							$input_id = 'library-filter-goal-' . ($index + 1);
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item library-filters-screen__row">
								<div class="checkbox"></div>
								<span><?php echo esc_html((string) $goal_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					if (!empty($goal_extra)) :
						foreach ($goal_extra as $index_extra => $goal_term) :
							$real_index = $goal_slice + $index_extra + 1;
							$input_id = 'library-filter-goal-' . $real_index;
							?>
							<label for="<?php echo esc_attr($input_id); ?>" class="checkbox-item library-filters-screen__row library-filters-screen__row_extra">
								<div class="checkbox"></div>
								<span><?php echo esc_html((string) $goal_term->name); ?></span>
							</label>
						<?php
						endforeach;
					endif;
					?>
				</div>
				<?php if (!empty($goal_extra)) : ?>
					<button type="button" class="library-filters-screen__more js-library-filters-more-goals">
						<?php esc_html_e('Показать еще', 'yoga'); ?>
					</button>
				<?php endif; ?>
			</section>
		</div>

		<footer class="library-filters-screen__footer">
			<button type="button" class="library-filters-screen__reset js-library-filters-reset">
				<?php esc_html_e('Сбросить', 'yoga'); ?>
			</button>
			<button type="button" class="library-filters-screen__apply btn js-library-filters-apply">
				<?php esc_html_e('Применить', 'yoga'); ?> (<span class="library-filters-apply-count">0</span>)
			</button>
		</footer>
	</div>
</div>
