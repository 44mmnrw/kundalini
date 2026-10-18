<?php
/** In-memory checks: no WordPress bootstrap, database writes or payment API calls. */
define('ABSPATH', __DIR__ . '/');
$hooks = $products = $orders = $notices = array();
$user_id = 7;
function add_action($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, ...$args) { add_action($name, $callback); }
function __($text, ...$args) { return $text; }
function wp_timezone() { return new DateTimeZone('Europe/Moscow'); }
function wp_date($format, $timestamp) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone())->format($format); }
function wp_strip_all_tags($text) { return strip_tags($text); }
function wp_unslash($value) { return $value; }
function get_current_user_id() { return $GLOBALS['user_id']; }
function wc_get_product($id) { return $GLOBALS['products'][$id] ?? null; }
function yoga_product_is_tariff($id) { return in_array($id, array(10, 11, 12), true); }
function wc_get_is_paid_statuses() { return array('processing', 'completed'); }
function wc_get_orders($query) {
	return array_values(array_filter($GLOBALS['orders'], static function ($order) use ($query) {
		return $order->customer === $query['customer_id'] && in_array($order->status, $query['status'], true)
			&& $order->paid && $order->paid->getTimestamp() > (int) substr($query['date_paid'], 1);
	}));
}
function wc_has_notice($message, $type) { return in_array($message, $GLOBALS['notices'], true); }
function wc_add_notice($message, $type) { $GLOBALS['notices'][] = $message; }
function WC() { return $GLOBALS['wc']; }
class WP_Error {
	private $code, $message, $data;
	public function __construct($code, $message, $data = null) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
class WC_Admin_Meta_Boxes {
	public static $errors = array();
	public static function add_error($error) { self::$errors[] = $error; }
}
class Product {
	public $id, $parent, $months;
	public function __construct($id, $months, $parent = 0) { $this->id = $id; $this->months = $months; $this->parent = $parent; }
	public function get_id() { return $this->id; }
	public function get_parent_id() { return $this->parent; }
	public function get_meta($key) { return $this->months; }
	public function get_name() { return 'Тариф ' . $this->id; }
	public function update_meta_data($key, $value) { $this->months = $value; }
}
class Item {
	private $id;
	public function __construct($id) { $this->id = $id; }
	public function get_product_id() { return $this->id; }
}
class WC_Order {
	public $id, $customer, $paid, $items, $status, $binding = false;
	public function __construct($id, $customer, $paid, $items, $status = 'completed') {
		$this->id = $id; $this->customer = $customer; $this->paid = $paid;
		$this->items = array_map(static function ($id) { return new Item($id); }, $items); $this->status = $status;
	}
	public function get_id() { return $this->id; }
	public function get_customer_id() { return $this->customer; }
	public function get_meta($key) { return $key === '_ytr_card_binding' && $this->binding ? 'yes' : ''; }
	public function get_date_paid() { return $this->paid; }
	public function get_items() { return $this->items; }
}
class Cart {
	public $items;
	public function __construct($items) { $this->items = $items; }
	public function get_cart() { return $this->items; }
}
require dirname(__DIR__) . '/inc/woocommerce/tariff-purchase-limit.php';
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function limit($product_id = 10, $customer = 7, $exclude = 0) { return yoga_tariff_purchase_limit_error($product_id, $customer, $exclude, true); }
$products = array(10 => new Product(10, 6), 11 => new Product(11, 0), 12 => new Product(12, 6), 101 => new Product(101, 0, 10), 102 => new Product(102, 0, 10), 20 => new Product(20, 6));
$recent = (new DateTimeImmutable('now', wp_timezone()))->modify('-2 months');
$old = (new DateTimeImmutable('now', wp_timezone()))->modify('-7 months');
check(limit() === null && limit(10, 0) === null, 'first purchase and guest selection allowed');
$orders = array(new WC_Order(1, 7, $recent, array(10)));
$error = limit();
check($error instanceof WP_Error && strpos($error->get_error_message(), 'Тариф 10') !== false, 'recent paid purchase blocked with named tariff');
check($error->get_error_data()['next_purchase_at'] === yoga_tariff_next_purchase_at($recent->getTimestamp(), 6), 'eligibility calculated from payment timestamp');
check(limit(101) instanceof WP_Error && limit(102) instanceof WP_Error, 'all variations share parent restriction');
check(limit(11) === null && limit(20) === null && limit(12) === null && limit(10, 8) === null, 'unrestricted products, other tariffs and other accounts unaffected');
check(limit(10, 7, 1) === null, 'current order excluded');
$orders[0]->paid = $old;
check(limit() === null, 'purchase available after interval expires');
foreach (array('pending', 'failed', 'cancelled', 'refunded') as $status) {
	$orders = array(new WC_Order(1, 7, $recent, array(10), $status));
	check(limit() === null, $status . ' order does not consume purchase allowance');
}
$orders = array(new WC_Order(1, 7, null, array(10)));
check(limit() === null, 'order without payment date does not consume allowance');
$orders = array(new WC_Order(1, 7, $recent, array(10)));
$orders[0]->binding = true;
check(limit() === null, 'card binding is not a tariff purchase');
$orders = array(new WC_Order(1, 7, $old, array(10)), new WC_Order(2, 7, $recent, array(10)));
check(limit()->get_error_data()['next_purchase_at'] === yoga_tariff_next_purchase_at($recent->getTimestamp(), 6), 'latest matching payment controls interval');
$paid_august = new DateTimeImmutable('2025-08-31 12:34:56', wp_timezone());
$paid_leap = new DateTimeImmutable('2023-08-31 12:34:56', wp_timezone());
check(wp_date('Y-m-d H:i:s', yoga_tariff_next_purchase_at($paid_august->getTimestamp(), 6)) === '2026-02-28 12:34:56', 'six calendar months clamp to February end');
check(wp_date('Y-m-d H:i:s', yoga_tariff_next_purchase_at($paid_leap->getTimestamp(), 6)) === '2024-02-29 12:34:56', 'leap year handled');
check(wp_date('Y-m-d H:i:s', yoga_tariff_next_purchase_at($paid_august->getTimestamp(), 0)) === '2025-08-31 12:34:56', 'boundary preserves exact payment time');

$_POST['_yoga_repurchase_months'] = '6';
yoga_save_tariff_purchase_limit_field($products[11]);
check($products[11]->months === 6, 'admin can set six months');
$_POST['_yoga_repurchase_months'] = '-1';
yoga_save_tariff_purchase_limit_field($products[11]);
check($products[11]->months === 6 && count(WC_Admin_Meta_Boxes::$errors) === 1, 'invalid admin input preserves previous value');
$_POST['_yoga_repurchase_months'] = '0';
yoga_save_tariff_purchase_limit_field($products[11]);
check($products[11]->months === 0, 'admin can disable limit');

$pending = new WC_Order(5, 7, null, array(10), 'pending');
check(yoga_tariff_order_purchase_limit_error($pending) instanceof WP_Error, 'old unpaid order rechecked before payment');
$pending->binding = true;
check(yoga_tariff_order_purchase_limit_error($pending) === null, 'card binding payment unaffected');
$pending->binding = false;
$pending->customer = 0;
check(yoga_tariff_order_purchase_limit_error($pending) instanceof WP_Error, 'restricted tariff requires account at payment');
$pending->customer = 7;
$caught = false;
try { yoga_validate_tariff_order_payment($pending); } catch (Exception $e) { $caught = true; }
check($caught, 'order-pay action stops blocked payment');
check(yoga_tariff_limit_purchasable(true, $products[101]) === false, 'direct add_to_cart through variation blocked');
check(yoga_tariff_limit_purchase_message('default', $products[101]) !== 'default', 'cart error explains next available date');
$wc = (object) array('cart' => new Cart(array(array('product_id' => 10, 'variation_id' => 101))));
yoga_validate_tariff_purchase_cart();
yoga_validate_tariff_purchase_cart();
check(count($notices) === 1, 'checkout blocks with one clear notice');
$user_id = 0; $notices = array();
yoga_validate_tariff_purchase_cart();
check(count($notices) === 1 && strpos($notices[0], 'войдите') !== false, 'guest checkout requires login');

require dirname(__DIR__, 3) . '/plugins/yoga-tariff-renewal/includes/class-ytr-yookassa.php';
$result = YTR_YooKassa::charge_renewal($pending, 'do-not-charge');
check(!$result['success'] && strpos($result['message'], 'Повторная покупка') !== false, 'recurring charge blocked before any gateway call');
check(isset($hooks['woocommerce_before_pay_action'], $hooks['woocommerce_checkout_process'], $hooks['woocommerce_admin_process_product_object']), 'admin and payment validations registered');
echo "PASS: tariff purchase intervals, month boundaries, variations, order history, admin settings, cart, order-pay and automatic charges.\n";
