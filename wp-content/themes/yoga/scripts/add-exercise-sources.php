<?php
/**
 * Add only the ACF sources field. Does not rewrite practice content or metadata.
 * Preview: php wp-content/themes/yoga/scripts/add-exercise-sources.php
 * Apply:   php wp-content/themes/yoga/scripts/add-exercise-sources.php --apply
 */
if (PHP_SAPI !== 'cli') {
	exit(1);
}
$arguments = array_slice($argv, 1);
if (array_diff($arguments, array('--apply')) || count($arguments) !== count(array_unique($arguments))) {
	fwrite(STDERR, "Usage: php add-exercise-sources.php [--apply]\n");
	exit(2);
}
require dirname(__DIR__, 4) . '/wp-load.php';
if (!function_exists('yoga_migrate_exercise_sources_field')) {
	fwrite(STDERR, "The Yoga theme sources migration is unavailable.\n");
	exit(1);
}
$result = yoga_migrate_exercise_sources_field(in_array('--apply', $arguments, true));
if (is_wp_error($result)) {
	fwrite(STDERR, $result->get_error_message() . "\n");
	exit(1);
}
echo 'Exercise sources migration: ' . $result . "\n";
