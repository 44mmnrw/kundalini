<?php
/** Offline regression: real YooKassa gateway/SDK, no WordPress, DB or network. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('ABSPATH', __DIR__);
define('YOOKASSA_VERSION', 'test');
$hooks = array();
function add_filter($name, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$name][$priority][] = $callback; }
function add_action(...$args) { add_filter(...$args); }
function apply_filters($name, $value) {
    $callbacks = $GLOBALS['hooks'][$name] ?? array();
    ksort($callbacks);
    foreach ($callbacks as $group) foreach ($group as $callback) $value = $callback($value);
    return $value;
}
function sanitize_key($s) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($s)); }
function wp_unslash($s) { return $s; }
function wc_clean($s) { return $s; }
function is_checkout() { return true; }
function is_user_logged_in() { return false; }
function get_current_user_id() { return 7; }
function get_option($name, $default = false) { return $default; }
function get_site_url($blog = null, $path = '') { return 'https://example.test/' . $path; }
function WC() { return (object) array('session' => null, 'cart' => null); }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
class WC_Payment_Gateway {}
class WC_Order {
    function get_order_key() { return 'synthetic-order'; }
    function get_checkout_payment_url($on_checkout = false) { return 'https://example.test/checkout/'; }
    function get_meta($key) { return ''; }
}
class WC_Payment_Tokens {
    public static $reads = 0;
    static function get($id) {
        self::$reads++;
        return new class {
            function get_user_id() { return 7; }
            function get_token() { return 'saved-method-from-previous-checkout'; }
        };
    }
}
class YooKassaLogger {
    static function info($message) {}
    static function sendHeka($events) {}
}
class YooKassaHandler { static function setReceiptIfNeeded(...$args) {} }
class YooKassaOrderHelper {
    static function getTotal($order) { return 700; }
    static function getAmountByCurrency($amount) { return new \YooKassa\Model\MonetaryAmount($amount, 'RUB'); }
}
$plugin = dirname(__DIR__, 3) . '/plugins/yookassa';
require $plugin . '/includes/lib/vendor/autoload.php';
require $plugin . '/gateway/YooKassaGateway.php';
require $plugin . '/gateway/YooKassaGatewayEPL.php';
require $plugin . '/gateway/YooKassaWidgetGateway.php';
require dirname(__DIR__) . '/inc/woocommerce/checkout-yookassa.php';
trait OfflineGateway {
    function __construct() {}
    function createDescription($order) { return 'Offline checkout regression'; }
    function request($order) {
        $GLOBALS['yoga_yookassa_checkout_order'] = $order;
        $this->prepareSubscription();
        return apply_filters('woocommerce_yookassa_create_payment_request', $this->getBuilder($order)->build());
    }
}
class OfflineEPL extends Yoga_YooKassa_Gateway_EPL { use OfflineGateway; }
class OfflineWidget extends Yoga_YooKassa_Gateway_Widget { use OfflineGateway; }
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru';
$serializer = new \YooKassa\Request\Payments\CreatePaymentRequestSerializer();
foreach (array('OfflineEPL', 'OfflineWidget') as $class) {
    foreach (array('bank_card', 'sbp', 'sberpay', 'tinkoff_bank', 'yoo_money') as $type) {
        $gateway = new $class();
        $_POST = array('yoga_checkout_payment_type' => $type, 'wc-' . $gateway->id . '-payment-token' => '42');
        $body = $serializer->serialize($gateway->request(new WC_Order()));
        check(!isset($body['payment_method_id']) && !isset($body['payment_token']), 'Manual choice must not submit a saved method or token');
        check(($body['payment_method_data']['type'] ?? '') === yoga_map_checkout_payment_type_to_api($type), 'Selected method is retained');
        check(isset($body['confirmation']['type']), 'Fresh payment retains confirmation instructions');
        if ($type === 'sbp') check($body['confirmation']['type'] === 'redirect' && $body['capture'] && !$body['save_payment_method'], 'SBP uses redirect, capture and no saving');
    }
}
check(WC_Payment_Tokens::$reads === 0, 'Hidden saved-card selections are ignored before token lookup');
// Native saved-card flows with no custom selection must continue to work.
$_POST = array('wc-yookassa_epl-payment-token' => '42');
unset($GLOBALS['yoga_yookassa_checkout_order']);
$body = $serializer->serialize((new OfflineEPL())->request(new WC_Order()));
check(isset($body['payment_method_id']) && !isset($body['payment_method_data']), 'Native saved-card flow remains intact');
// Defensive filtering also handles a request already populated by another hook.
$_POST = array('yoga_checkout_payment_type' => 'sbp');
$request = \YooKassa\Request\Payments\CreatePaymentRequest::builder()->setAmount('700.00')->setPaymentMethodId('saved-method')->build();
$request->setPaymentToken('stale-token');
$GLOBALS['yoga_yookassa_checkout_order'] = new WC_Order();
$body = $serializer->serialize(yoga_yookassa_apply_payment_type_to_request($request));
check(!isset($body['payment_method_id']) && !isset($body['payment_token']) && $body['payment_method_data']['type'] === 'sbp', 'Existing conflicting fields are removed');
echo "Checkout saved-payment conflict tests passed.\n";
