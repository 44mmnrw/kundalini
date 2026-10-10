<?php
/** Run without WordPress/database writes: php tests/payment-success-dates.php */
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
define('WEEK_IN_SECONDS', 604800);
function add_action(...$args) {}
function add_filter(...$args) {}
function get_field($key, $id) { return $GLOBALS['periods'][$id] ?? ''; }
function wc_get_product($id) { return $GLOBALS['products'][$id] ?? false; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)); }
function yoga_product_is_tariff($id) { return true; }
function wcs_get_subscriptions_for_order(...$args) { return $GLOBALS['subscriptions'] ?? []; }
class WC_Product {
 function __construct(private $parent = 0, private $period = '') {}
 function is_type($type) { return $type === 'variation' && $this->parent > 0; }
 function get_parent_id() { return $this->parent; }
 function get_attribute($name) { return $this->period; }
}
class DateTestItem {
 function __construct(private $product, private $variation = 0) {}
 function get_product_id() { return $this->product; }
 function get_variation_id() { return $this->variation; }
}
class WC_Order {
 function __construct(private $item, private $completed, private $paid, private $created) {}
 function get_id() { return 1531; }
 function get_items() { return [$this->item]; }
 function get_date_completed() { return $this->completed; }
 function get_date_paid() { return $this->paid; }
 function get_date_created() { return $this->created; }
}
require dirname(__DIR__) . '/inc/woocommerce/cart-helpers.php';
require dirname(__DIR__) . '/inc/woocommerce/payment-success.php';
// Load the real duration function without bootstrapping the theme or WordPress.
$source = file_get_contents(dirname(__DIR__) . '/functions.php');
$start = strpos($source, 'function calculate_access_duration(');
$brace = strpos($source, '{', $start);
$depth = 1;
for ($end = $brace + 1; $depth; $end++) {
 if ($source[$end] === '{') $depth++;
 if ($source[$end] === '}') $depth--;
}
eval(substr($source, $start, $end - $start));
$base = new DateTimeImmutable('2026-09-01 19:23:19', new DateTimeZone('UTC'));
$checks = 0;
function expect_end($order, $timestamp, $message) {
 if (yoga_get_order_subscription_end_timestamp($order) !== $timestamp) throw new RuntimeException($message);
 $GLOBALS['checks']++;
}
foreach (['1day: 1 день' => 1, 'day' => 1, 'month' => 30, 'year' => 365] as $period => $days) {
 $GLOBALS['periods'] = [1528 => $period];
 expect_end(new WC_Order(new DateTestItem(1528), $base, null, $base), $base->getTimestamp() + $days * DAY_IN_SECONDS, 'Wrong duration: ' . $period);
}
$GLOBALS['periods'] = [1528 => 'month'];
$GLOBALS['products'] = [1529 => new WC_Product(1528, '1day')];
expect_end(new WC_Order(new DateTestItem(1528, 1529), $base, null, $base), $base->getTimestamp() + DAY_IN_SECONDS, 'Variation must override parent period');
$GLOBALS['products'][1529] = new WC_Product(1528);
expect_end(new WC_Order(new DateTestItem(1528, 1529), $base, null, $base), $base->getTimestamp() + 30 * DAY_IN_SECONDS, 'Variation must inherit parent period');
$GLOBALS['periods'] = [1528 => '1day: 1 день'];
$created = $base->modify('-2 days');
expect_end(new WC_Order(new DateTestItem(1528), null, $base, $created), $base->getTimestamp() + DAY_IN_SECONDS, 'Paid date must precede created date fallback');
expect_end(new WC_Order(new DateTestItem(1528), null, null, $created), $created->getTimestamp() + DAY_IN_SECONDS, 'Created date fallback');
$GLOBALS['subscriptions'] = [new class { function get_date($name) { return '2026-12-01 12:00:00'; } }];
expect_end(new WC_Order(new DateTestItem(1528), $base, null, $base), strtotime('2026-12-01 12:00:00'), 'Actual subscription end must take precedence');
echo "PASS: $checks payment success date checks.\n";
