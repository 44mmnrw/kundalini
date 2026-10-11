<?php
/**
 * Goal badges for a practice in the library list.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

$practice_id = !empty($args['practice_id']) ? (int) $args['practice_id'] : (int) get_the_ID();
$practice_goals = get_the_terms($practice_id, 'practice-goal');
if (empty($practice_goals) || is_wp_error($practice_goals)) {
	return;
}

$practice_url = get_permalink($practice_id);
?>
<div class="kriya-goals">
	<?php foreach ($practice_goals as $practice_goal) : ?>
		<a class="kriya-goal" href="<?php echo esc_url($practice_url); ?>"><?php echo esc_html($practice_goal->name); ?></a>
	<?php endforeach; ?>
</div>
