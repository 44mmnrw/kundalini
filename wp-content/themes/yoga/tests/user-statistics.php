<?php
/** php tests/user-statistics.php — без WordPress, реальных оплат и отправки писем. */
define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('ARRAY_A', 'ARRAY_A');
$GLOBALS['stats_hooks'] = array();
$GLOBALS['stats_cache'] = array();
$GLOBALS['stats_allowed'] = true;
$GLOBALS['stats_order_queries'] = array();
function add_action($name, $callback, ...$args) { $GLOBALS['stats_hooks'][$name][] = $callback; }
function add_filter(...$args) {}
function get_transient($name) { return $GLOBALS['stats_cache'][$name] ?? false; }
function set_transient($name, $value, $ttl) { $GLOBALS['stats_cache'][$name] = $value; }
function delete_transient($name) { unset($GLOBALS['stats_cache'][$name]); }
function get_option($name, $default = false) { return $default; }
function current_user_can(...$args) { return $GLOBALS['stats_allowed']; }
function absint($value) { return abs((int) $value); }
function get_user_meta($id, $key, $single = true) { return $key === 'timezone' ? ($GLOBALS['stats_timezone'] ?? 'Europe/Moscow') : array(10, 11); }
function wc_get_price_decimals() { return 2; }
function wc_get_is_paid_statuses() { return array('processing', 'completed'); }
function yoga_product_is_tariff($id) { return in_array($id, array(10, 12), true); }
function wp_kses_post($text) { return $text; }
function wc_price($amount, $args) { return number_format($amount, 2, '.', ' ') . ' ' . $args['currency']; }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($text) { return esc_html($text); }
function get_the_title($id) { return '<script>Title</script>'; }
function get_edit_post_link($id, $context = '') { return '/edit-post.php?post=' . (int) $id; }
function get_edit_user_link($id) { return '/user-edit.php?user_id=' . (int) $id; }
function get_userdata($id) { return (object) array('display_name' => '<script>User</script>'); }
function wp_date($format, $timestamp) { return gmdate($format, $timestamp); }
function wc_get_order_status_name($status) { return $status; }
function admin_url($path) { return '/wp-admin/' . $path; }
function wp_die($message) { throw new RuntimeException($message); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_query_arg($key, $value, $url) { return $url . '&' . $key . '=' . $value; }
function yoga_sadhana_storage_exists() { return !empty($GLOBALS['stats_sadhana_available']); }
class WP_Error { function __construct(public $code, public $message) {} function get_error_message() { return $this->message; } }
class WC_Order_Item_Product {
	public array $meta = array();
	function __construct(public int $pid, public float $total, public float $tax = 0, public int $quantity = 1) {}
	function get_product_id() { return $this->pid; }
	function get_name() { return 'Тариф <script>alert(1)</script>'; }
	function get_quantity() { return $this->quantity; }
	function get_total() { return $this->total; }
	function get_total_tax() { return $this->tax; }
	function get_taxes() { return array('total' => $this->tax ? array(1 => $this->tax) : array()); }
	function get_meta($key) { return $this->meta[$key] ?? ''; }
	function add_meta_data($key, $value, $unique) { $this->meta[$key] = $value; }
	function save_meta_data() {}
}
class WC_Order {
	public array $meta = array();
	public array $refund_items = array();
	public array $refund_taxes = array();
	public array $refund_quantities = array();
	function __construct(public int $id, public int $uid, public float $total, public float $refund, public string $currency, public array $items, public string $status = 'completed') {}
	function get_id() { return $this->id; }
	function get_order_number() { return $this->id; }
	function get_customer_id() { return $this->uid; }
	function get_total() { return $this->total; }
	function get_total_refunded() { return $this->refund; }
	function get_currency() { return $this->currency; }
	function get_status() { return $this->status; }
	function get_meta($key) { return $this->meta[$key] ?? ''; }
	function get_items($type = 'line_item') { return $type === 'line_item' ? $this->items : array(); }
	function get_total_refunded_for_item($id, $type = '') { return $this->refund_items[$id] ?? 0; }
	function get_tax_refunded_for_item($id, $tax_id, $type = '') { return $this->refund_taxes[$id] ?? 0; }
	function get_qty_refunded_for_item($id) { return $this->refund_quantities[$id] ?? 0; }
	function get_date_created() { return new DateTimeImmutable('2026-10-01'); }
	function get_edit_order_url() { return '/order/' . $this->id; }
}
function wc_get_orders($args) {
	$GLOBALS['stats_order_queries'][] = $args;
	$orders = array_values(array_filter($GLOBALS['stats_orders'], static function ($order) use ($args) {
		return (!isset($args['customer_id']) || $order->uid === $args['customer_id']) && (!isset($args['status']) || in_array($order->status, $args['status'], true));
	}));
	return (object) array('orders' => array_slice($orders, ($args['page'] - 1) * $args['limit'], $args['limit']), 'max_num_pages' => (int) ceil(count($orders) / $args['limit']));
}
class Statistics_Empty_DB {
	public string $prefix = 'wp_';
	function get_var($query) { return null; }
	function prepare($query, ...$args) { return $query; }
	function esc_like($name) { return $name; }
}
$GLOBALS['wpdb'] = new Statistics_Empty_DB();
require dirname(__DIR__) . '/inc/statistics/finances.php';
require dirname(__DIR__) . '/inc/statistics/activity.php';
require dirname(__DIR__) . '/inc/admin/user-statistics.php';
function statistics_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }

$partial = new WC_Order(1, 1, 1200, 400, 'RUB', array(101 => new WC_Order_Item_Product(10, 1000, 200, 2)));
$partial->refund_items[101] = 300; $partial->refund_taxes[101] = 100; $partial->refund_quantities[101] = -1;
$full = new WC_Order(2, 1, 500, 500, 'RUB', array(102 => new WC_Order_Item_Product(10, 500)), 'refunded');
$deleted_tariff = new WC_Order_Item_Product(11, 20); $deleted_tariff->meta['_yoga_statistics_tariff'] = 'yes';
$dollars = new WC_Order(3, 2, 20, 0, 'USD', array(103 => $deleted_tariff));
$guest = new WC_Order(4, 0, 1000, 100, 'RUB', array(104 => new WC_Order_Item_Product(10, 1000)));
$binding = new WC_Order(5, 1, 1, 0, 'RUB', array()); $binding->meta['_ytr_card_binding'] = 'yes';
$failed = new WC_Order(6, 1, 999, 0, 'RUB', array(), 'failed');
$mixed = new WC_Order(7, 2, 300, 150, 'RUB', array(107 => new WC_Order_Item_Product(10, 200), 108 => new WC_Order_Item_Product(99, 100)));
$free = new WC_Order(8, 1, 0, 0, 'RUB', array(109 => new WC_Order_Item_Product(12, 0)));
$GLOBALS['stats_orders'] = array($partial, $full, $dollars, $guest, $binding, $failed, $mixed, $free);
for ($id = 100; $id < 305; $id++) { $GLOBALS['stats_orders'][] = new WC_Order($id, 3, 0, 0, 'RUB', array()); }
$report = yoga_statistics_finances();
statistics_assert(count($GLOBALS['stats_order_queries']) === 3, 'all order pages are read in bounded batches');
statistics_assert($report['currencies']['RUB']['paid'] === 3000.0, 'paid total excludes unpaid orders and card binding');
statistics_assert($report['currencies']['RUB']['refunded'] === 1150.0 && $report['currencies']['RUB']['net'] === 1850.0, 'net revenue includes partial, full and unallocated refunds');
statistics_assert($report['currencies']['USD']['net'] === 20.0, 'currencies are not combined');
statistics_assert($report['users']['RUB:1']['net'] === 800.0 && $report['users']['RUB:2']['net'] === 150.0, 'revenue assigned to account ID');
statistics_assert($report['currencies']['RUB']['guest_net'] === 900.0, 'guest revenue kept separate');
statistics_assert($report['tariffs']['RUB:10']['quantity'] === 3 && $report['tariffs']['RUB:10']['net'] === 1800.0, 'bestsellers count net quantities and proportionally allocated refunds');
statistics_assert($report['tariffs']['RUB:12']['quantity'] === 1, 'free tariff purchases retained');
statistics_assert(isset($report['tariffs']['USD:11']), 'saved tariff classification survives deleted product');
$queries = count($GLOBALS['stats_order_queries']); yoga_statistics_finances();
statistics_assert(count($GLOBALS['stats_order_queries']) === $queries, 'overview uses five-minute cache');
yoga_statistics_clear_finance_cache(); yoga_statistics_finances();
statistics_assert(count($GLOBALS['stats_order_queries']) > $queries, 'order change invalidates financial cache');
$personal = yoga_statistics_finances(1);
statistics_assert($personal['currencies']['RUB']['net'] === 800.0 && !isset($personal['currencies']['USD']), 'profile totals isolated by user');
$item = new WC_Order_Item_Product(10, 10); yoga_statistics_snapshot_tariff_item(1, $item);
statistics_assert($item->get_meta('_yoga_statistics_tariff') === 'yes', 'new purchases retain tariff identity');

$base = array('completed_days' => 4, 'status' => 'active', 'last_marked_on' => '2026-10-30', 'reset_count' => 0);
statistics_assert(yoga_statistics_sadhana_row($base, '2026-10-31')['streak'] === 4, 'yesterday counts toward current streak');
$base['last_marked_on'] = '2026-10-29';
$stale = yoga_statistics_sadhana_row($base, '2026-10-31');
statistics_assert($stale['status'] === 'cancelled' && $stale['streak'] === 0 && $stale['completed_days'] === 4, 'stale cycle projected without erasing historical days');
$base['status'] = 'completed'; $base['last_marked_on'] = '2026-10-31';
statistics_assert(yoga_statistics_sadhana_row($base, '2026-10-31')['streak'] === 4, 'today-completed cycle has a current streak');
$base['last_marked_on'] = '2026-11-01';
statistics_assert(yoga_statistics_sadhana_row($base, '2026-10-31')['streak'] === 0, 'future dates cannot inflate current streak');
statistics_assert(yoga_statistics_record_activity(1, 10, 'bad', 'start') instanceof WP_Error, 'invalid session rejected');
$GLOBALS['stats_timezone'] = 'bad/timezone';
statistics_assert(yoga_statistics_user_timezone(1)->getName() === 'Europe/Moscow', 'invalid timezone falls back safely');

$GLOBALS['stats_allowed'] = false;
$queries = count($GLOBALS['stats_order_queries']);
ob_start(); yoga_statistics_render_user_profile((object) array('ID' => 1)); $denied = ob_get_clean();
statistics_assert($denied === '' && count($GLOBALS['stats_order_queries']) === $queries, 'unauthorized profile viewer receives no private statistics or queries');
$GLOBALS['stats_allowed'] = true;
ob_start(); yoga_statistics_render_user_profile((object) array('ID' => 1)); $profile = ob_get_clean();
statistics_assert(strpos($profile, 'История заказов и подписок') !== false && strpos($profile, '800.00 RUB') !== false, 'profile includes finances and purchase history');
statistics_assert(strpos($profile, '<script>') === false && strpos($profile, '&lt;script&gt;') !== false, 'order item labels escaped');
statistics_assert(strpos(yoga_statistics_practice_link(1), '<script>') === false && strpos(yoga_statistics_user_link(1), '<script>') === false, 'entity titles escaped');
statistics_assert(isset($GLOBALS['stats_hooks']['admin_menu'], $GLOBALS['stats_hooks']['show_user_profile'], $GLOBALS['stats_hooks']['edit_user_profile']), 'admin entry points registered');
echo "PASS: pagination, paid/refunded revenue, currencies, tariff ranking, cache, access control, escaping and read-only sadhana projection.\n";
