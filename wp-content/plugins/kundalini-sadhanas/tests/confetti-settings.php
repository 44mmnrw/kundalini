<?php
/**
 * Run with: php tests/confetti-settings.php
 */

define('ABSPATH', __DIR__ . '/');

function __($message): string { return (string) $message; }
function add_action(): void {}
function add_filter(): void {}
function absint($value): int { return abs((int) $value); }
function get_option($name, $default = null) { return $GLOBALS['test_settings'] ?? $default; }

require_once dirname(__DIR__) . '/includes/settings.php';

function check($condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$default = kundalini_sadhanas_confetti_config();
check($default['enabled'] === true, 'Confetti should be enabled by default.');
check($default['duration'] === 5400 && $default['intensity'] === 4, 'Existing effect timing and intensity should be preserved.');
check($default['direction'] === 'both' && count($default['colors']) === 4, 'Existing direction and palette should be preserved.');

$saved = kundalini_sadhanas_sanitize_settings(array(
	'confetti_enabled' => '1',
	'confetti_duration' => '99',
	'confetti_intensity' => '0',
	'confetti_colors' => array('#123456', 'invalid', '#ABCDEF', '#fedcba'),
	'confetti_size' => '0.1',
	'confetti_speed' => '100',
	'confetti_direction' => 'unknown',
));
check($saved['confetti_duration'] === 15.0, 'Duration should be limited to 15 seconds.');
check($saved['confetti_intensity'] === 1, 'Intensity should be limited to the slider range.');
check($saved['confetti_colors'][0] === '#123456' && $saved['confetti_colors'][1] === '#f8bdf6' && $saved['confetti_colors'][2] === '#abcdef', 'Colors should be normalized with fallback for invalid values.');
check($saved['confetti_size'] === 0.5 && $saved['confetti_speed'] === 70, 'Particle size and speed should be limited.');
check($saved['confetti_direction'] === 'both', 'Unknown direction should fall back to both sides.');

$GLOBALS['test_settings'] = $saved;
$configured = kundalini_sadhanas_confetti_config();
check($configured['duration'] === 15000 && $configured['intensity'] === 1, 'Saved settings should reach the frontend configuration.');

unset($saved['confetti_enabled']);
$disabled = kundalini_sadhanas_sanitize_settings($saved);
check($disabled['confetti_enabled'] === false, 'An unchecked checkbox should disable confetti.');

echo "Confetti settings checks passed.\n";
