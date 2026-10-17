<?php
/**
 * Goal badges in the library list and on a practice page.
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
$is_practice_page = ($args['context'] ?? '') === 'practice';
$is_library_card = ($args['context'] ?? '') === 'library';
$goals_library_url = '';
if ($is_practice_page) {
	$goals_nav_urls = yoga_lk_sidebar_secondary_nav_urls();
	$goals_library_url = (string) $goals_nav_urls['library'];
}
?>
<div class="<?php echo $is_practice_page ? 'praktika-goals' : 'kriya-goals'; ?>"<?php if ($is_practice_page) : ?> aria-label="<?php esc_attr_e('Цели практики', 'yoga'); ?>"<?php endif; ?>>
	<?php foreach ($practice_goals as $practice_goal) : ?>
		<?php
		$goal_url = $is_practice_page
			? add_query_arg('practice_goal', $practice_goal->slug, $goals_library_url)
			: $practice_url;
		$goal_class = $is_practice_page ? 'praktika-goal' : 'kriya-goal';
		?>
		<?php if ($is_library_card) : ?>
			<button type="button" class="<?php echo esc_attr($goal_class); ?>" data-goal-id="<?php echo esc_attr((string) $practice_goal->term_id); ?>" data-goal-slug="<?php echo esc_attr($practice_goal->slug); ?>"><?php echo esc_html($practice_goal->name); ?></button>
		<?php elseif (is_wp_error($goal_url)) : ?>
			<span class="<?php echo esc_attr($goal_class); ?>"><?php echo esc_html($practice_goal->name); ?></span>
		<?php else : ?>
			<a class="<?php echo esc_attr($goal_class); ?>" href="<?php echo esc_url($goal_url); ?>"><?php echo esc_html($practice_goal->name); ?></a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
