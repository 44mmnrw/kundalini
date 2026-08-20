<?php
/**
 * Подписи рабочих разделов в админке WordPress.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_rename_admin_sections(): void {
	global $menu, $submenu;

	foreach ($menu as &$menu_item) {
		if (($menu_item[2] ?? '') === 'edit.php') {
			$menu_item[0] = 'Блог';
		}
	}
	unset($menu_item);

	if (isset($submenu['edit.php'][5][0])) {
		$submenu['edit.php'][5][0] = 'Все записи блога';
	}
}
add_action('admin_menu', 'yoga_rename_admin_sections', 999);

function yoga_rename_practice_admin_labels(array $args, string $post_type): array {
	if ($post_type !== 'practice') {
		return $args;
	}

	$labels = isset($args['labels']) && is_array($args['labels']) ? $args['labels'] : array();
	$labels['name'] = 'Библиотека практик';
	$labels['menu_name'] = 'Библиотека практик';
	$labels['all_items'] = 'Все практики';
	$args['labels'] = $labels;

	return $args;
}
add_filter('register_post_type_args', 'yoga_rename_practice_admin_labels', 200, 2);
