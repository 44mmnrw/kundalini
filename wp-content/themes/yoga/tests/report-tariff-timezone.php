<?php
/** Compare the real tariff selector with UTC and shifted WP timezone fixtures. */
define('DAY_IN_SECONDS', 86400);
function get_current_user_id() { return 7; }
function yoga_has_woocommerce() { return true; }
function wc_get_orders($args) { return array(new TestTariffOrder()); }
function yoga_product_is_tariff($id) { return true; }
function yoga_get_product_price_period($id) { return 'month'; }
function calculate_access_duration($period) { return 30 * DAY_IN_SECONDS; }
function current_time($type) { return time() + $GLOBALS['offset'] * 3600; }
function wp_date($format, $timestamp) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone($GLOBALS['zone']))->format($format); }
class TestTariffProduct {
    function get_id() { return 10; }
    function is_type($type) { return false; }
    function get_name() { return 'Test tariff'; }
}
class TestTariffOrder {
    function get_id() { return 12; }
    function get_items() { return array(new class { function get_product() { return new TestTariffProduct(); } }); }
    function get_date_completed() { return new DateTimeImmutable('@' . ($GLOBALS['expires'] - 30 * DAY_IN_SECONDS)); }
}
$source = file_get_contents(dirname(__DIR__) . '/functions.php');
$start = strpos($source, 'function get_current_user_tariff(');
$brace = strpos($source, '{', $start); $depth = 1;
for ($end = $brace + 1; $depth; $end++) { if ($source[$end] === '{') $depth++; if ($source[$end] === '}') $depth--; }
eval(substr($source, $start, $end - $start));
foreach (array('UTC' => 0, 'Europe/Moscow' => 3, 'America/New_York' => -4) as $zone => $offset) {
    $GLOBALS['zone'] = $zone; $GLOBALS['offset'] = $offset;
    $GLOBALS['expires'] = time() + 7200;
    $tariff = get_current_user_tariff(7);
    if (!$tariff || abs($tariff['remaining_time'] - 7200) > 1 || $tariff['access_end_date'] !== wp_date('d.m.Y H:i', $GLOBALS['expires'])) {
        throw new RuntimeException('Wrong expiry in ' . $zone);
    }
    $GLOBALS['expires'] = time() - 7200;
    if (get_current_user_tariff(7) !== null) throw new RuntimeException('Expired tariff remained active in ' . $zone);
}
echo "PASS: active and expired tariff in UTC, Moscow and New York; localized date labels.\n";
