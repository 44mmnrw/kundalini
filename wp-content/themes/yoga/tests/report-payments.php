<?php
/** Isolated regression checks for the delivery review; no WP bootstrap or network. */
namespace YooKassa\Request\Payments {
    class CreatePaymentRequestSerializer { function serialize($request) { return array(); } }
}
namespace {
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
require dirname(__DIR__, 4) . '/wp-includes/class-wp-error.php';
function add_action(...$args) {}
function add_filter(...$args) {}
function do_action(...$args) {}
function apply_filters($hook, $value) { return $value; }
function __($text, ...$args) { return $text; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_option($key, $default = false) { return 'test-key'; }
function wp_json_encode($value) { return json_encode($value); }
function wp_remote_post($url, $args) {
    expect($args['headers']['Authorization'] === 'Basic ' . base64_encode('test-key:test-key'), 'valid API authorization');
    $GLOBALS['requests']++;
    return new WP_Error('transport', 'Offline fixture');
}
function yoga_get_selected_yookassa_payment_type_for_api() { return 'alfa_pay'; }
class YooKassaLogger {
    static function info($value) {}
    static function sendHeka($value) {}
    static function error($value) { $GLOBALS['api_error'] = $value; }
}
class YooKassaGatewayEPL {
    protected $subscribe = false, $paymentMethod = '';
    protected function getBuilder($order) { return new class { function build() { return new \stdClass(); } }; }
}
function expect($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
$GLOBALS['requests'] = 0;
require dirname(__DIR__) . '/inc/woocommerce/yookassa-gateway.php';
$result = (new Yoga_YooKassa_Gateway_EPL())->createPayment(null);
expect(is_wp_error($result) && $result->get_error_code() === 'transport', 'transport errors survive logging without toArray fatal: ' . ($GLOBALS['api_error'] ?? ''));
expect($GLOBALS['requests'] === 1, 'Alfa Pay reaches HTTP client');

class WC_Order {
    function __construct(public $status) {}
    function is_paid() { return in_array($this->status, array('processing', 'completed'), true); }
    function has_status($statuses) { return in_array($this->status, (array) $statuses, true); }
    function get_items() { return array(); }
    function get_date_completed() { return new \DateTimeImmutable('2026-10-01'); }
}
function yoga_get_payment_success_order() { return $GLOBALS['fixture_order']; }
function home_url($path) { return 'https://example.com' . $path; }
function is_email($email) { return true; }
function wp_date($format, $timestamp = null) { return date($format, $timestamp ?? time()); }
function get_template_directory_uri() { return 'https://example.com/theme'; }
function esc_html($text) { return htmlspecialchars($text); }
function esc_url($text) { return htmlspecialchars($text); }
function esc_attr($text) { return htmlspecialchars($text); }
function esc_html_e($text, ...$args) { echo esc_html($text); }
require dirname(__DIR__) . '/inc/woocommerce/payment-success.php';
foreach (array(null => 'missing', 'pending' => 'pending', 'on-hold' => 'pending', 'failed' => 'failed', 'cancelled' => 'failed', 'refunded' => 'failed', 'processing' => 'paid', 'completed' => 'paid') as $status => $state) {
    $GLOBALS['fixture_order'] = $status === '' ? null : new WC_Order($status);
    expect(yoga_get_payment_success_context()['payment_state'] === $state, 'correct state for ' . $status);
    ob_start();
    include dirname(__DIR__) . '/template-parts/section-payment-success.php';
    $html = ob_get_clean();
    expect((strpos($html, 'успешно подключен') !== false) === ($state === 'paid'), 'success text only after paid status');
}
function check_ajax_referer(...$args) {}
function yoga_ajax_error($message, $code, $status) { throw new \RuntimeException($code, $status); }
require dirname(__DIR__) . '/inc/ajax/auth-sms.php';
foreach (array('handle_yoga_email_register', 'handle_yoga_email_login', 'handle_verify_sms_code') as $handler) {
    try { $handler(); throw new \LogicException('Missing session dependency was ignored'); }
    catch (\RuntimeException $e) { expect($e->getMessage() === 'session_unavailable' && $e->getCode() === 503, 'fail before account mutations when MU-plugin is missing'); }
}
echo "PASS: Alfa Pay transport, logging, 8 payment screen states and missing session dependency.\n";
}
