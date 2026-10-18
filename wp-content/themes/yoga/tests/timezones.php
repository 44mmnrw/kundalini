<?php
/** Isolated timezone checks: no WordPress bootstrap, database or remote writes. */
define('ABSPATH', __DIR__ . '/');

function add_action(): void {}
function get_user_meta($user_id, $key, $single): string { return $GLOBALS['test_timezone']; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_email($value) { return trim($value); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
class WP_User { public $ID = 7; public $user_pass = ''; }
class WP_Error { public function __construct(public $code, public $message) {} }

// Load the real option provider without executing the theme's startup hooks.
$source = file_get_contents(dirname(__DIR__) . '/functions.php');
$start = strpos($source, 'function yoga_get_russian_timezone_options()');
$end = strpos($source, 'function yoga_update_profile_ajax()', $start);
if ($start === false || $end === false) {
	throw new RuntimeException('Timezone option provider not found');
}
eval(substr($source, $start, $end - $start));
require dirname(__DIR__) . '/inc/auth/profile-validation.php';
require dirname(__DIR__, 3) . '/plugins/kundalini-sadhanas/includes/core.php';

$checks = 0;
function check(bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
	$GLOBALS['checks']++;
}

$options = yoga_get_russian_timezone_options();
$account = new WP_User();
foreach ($options as $zone => $label) {
	$GLOBALS['test_timezone'] = $zone;
	check(yoga_sadhana_user_timezone(7)->getName() === $zone, "Zone fell back to Moscow: $zone");
	$result = yoga_validate_profile_changes(array(
		'first_name' => 'Test', 'email' => 'test@example.com', 'timezone' => $zone,
	), $account);
	check(is_array($result) && $result['timezone'] === $zone, "Profile rejected zone: $zone");
}

// Old stored values must remain selectable and pass profile validation.
foreach (array(
	'America/Los_Angeles', 'America/Denver', 'America/Chicago', 'America/New_York',
	'America/Argentina/Buenos_Aires', 'Europe/London', 'Europe/Berlin',
	'Europe/Kaliningrad', 'Europe/Moscow', 'Europe/Samara', 'Asia/Yekaterinburg',
	'Asia/Omsk', 'Asia/Novosibirsk', 'Asia/Irkutsk', 'Asia/Yakutsk',
	'Asia/Vladivostok', 'Asia/Magadan', 'Asia/Kamchatka',
) as $zone) {
	check(isset($options[$zone]), "Lost saved timezone: $zone");
}

// Exercise the actual saved zones used by Sadhana calculations across seasons.
foreach (array(
	array('Тель-Авив', 'Asia/Jerusalem', '+03:00', '+02:00'),
	array('Калининград', 'Europe/Kaliningrad', '+02:00', '+02:00'),
	array('Мехико', 'America/Mexico_City', '-06:00', '-06:00'),
	array('Чикаго', 'America/Chicago', '-05:00', '-06:00'),
	array('Берлин', 'Europe/Berlin', '+02:00', '+01:00'),
) as [$city, $zone, $summer, $winter]) {
	check(($options[$zone] ?? '') === $city, "Missing separate option: $city");
	$GLOBALS['test_timezone'] = $zone;
	$timezone = yoga_sadhana_user_timezone(7);
	check((new DateTimeImmutable('2026-07-15 12:00', $timezone))->format('P') === $summer, "$city summer offset");
	check((new DateTimeImmutable('2026-12-15 12:00', $timezone))->format('P') === $winter, "$city winter offset");
}

$GLOBALS['test_timezone'] = 'Europe/Berlin';
$timezone = yoga_sadhana_user_timezone(7);
foreach (array('2026-03-29' => 23, '2026-10-25' => 25) as $date => $hours) {
	$start = new DateTimeImmutable($date, $timezone);
	$next = $start->modify('+1 day');
	check(($next->getTimestamp() - $start->getTimestamp()) === $hours * 3600, "Wrong day length: $date");
	check($next->format('H:i') === '00:00', "Day must end at local midnight: $date");
	check($next->modify('-1 day')->format('Y-m-d') === $date, "Calendar day roundtrip: $date");
}

echo "PASS: $checks timezone / profile / seasonal calendar checks; no database or remote writes.\n";
