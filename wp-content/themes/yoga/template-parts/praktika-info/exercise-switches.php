<?php
/** Shared desktop tabs and mobile variant dropdown. @package Yoga */
$exercise_switch_options = array_merge(array(array('version' => 'main', 'label' => $execution_label)), $modification_tabs);
$exercise_switch_label = $execution_label;
foreach ($exercise_switch_options as $option) {
	if ($option['version'] === $exercise_switch_version) {
		$exercise_switch_label = $option['label'];
	}
}
$exercise_switch_id = wp_unique_id('exercise-variants-');
?>
<div class="exercise-switches">
	<button class="exercise-switches__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($exercise_switch_id); ?>">
		<span><?php echo esc_html($exercise_switch_label); ?></span>
		<img class="exercise-switches__arrow exercise-switches__arrow--down" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/practice-variant-arrow-down.svg'); ?>" alt="">
		<img class="exercise-switches__arrow exercise-switches__arrow--up" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/practice-variant-arrow-up.svg'); ?>" alt="">
	</button>
	<div class="exercise-switches__options" id="<?php echo esc_attr($exercise_switch_id); ?>">
		<?php foreach ($exercise_switch_options as $option): ?>
		<button type="button" class="exercise-switches__item<?php echo $option['version'] === $exercise_switch_version ? ' active' : ''; ?>" data-target="<?php echo esc_attr($option['version']); ?>" aria-pressed="<?php echo $option['version'] === $exercise_switch_version ? 'true' : 'false'; ?>">
			<b><?php echo esc_html($option['label']); ?></b>
		</button>
		<?php endforeach; ?>
	</div>
</div>
