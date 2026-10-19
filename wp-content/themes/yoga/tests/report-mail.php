<?php
/** Isolated mail regressions and optional HTML fixtures. Never sends email. */
define('ABSPATH', __DIR__ . '/');
define('YOGA_MAIL_PATH', dirname(__DIR__, 3) . '/plugins/yoga-mail/');
require dirname(__DIR__, 4) . '/wp-includes/class-wp-error.php';
$options = $filters = array();
function is_wp_error($v) { return $v instanceof WP_Error; }
function is_email($v) { return filter_var($v, FILTER_VALIDATE_EMAIL); }
function __($v, ...$args) { return $v; }
function do_action(...$args) {}
function add_action(...$args) {}
function add_filter($name, $cb, ...$args) { $GLOBALS['filters'][$name] = $cb; }
function apply_filters($name, $value, ...$args) { return isset($GLOBALS['filters'][$name]) ? $GLOBALS['filters'][$name]($value, ...$args) : $value; }
function content_url($path = '') { return 'https://example.com/wp-content' . $path; }
function home_url($path = '') { return 'https://example.com' . $path; }
function get_bloginfo($key = '') { return $key === 'charset' ? 'UTF-8' : 'Kundalini Class'; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, ...$args) { $GLOBALS['options'][$key] = $value; return true; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, $args); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)); }
function sanitize_text_field($v) { return trim(strip_tags($v)); }
function sanitize_textarea_field($v) { return trim(strip_tags($v)); }
function wp_strip_all_tags($v) { return strip_tags($v); }
function wp_kses_post($v) { return $v; }
function wp_specialchars_decode($v, $flags = ENT_QUOTES) { return html_entity_decode($v, $flags, 'UTF-8'); }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return esc_html($v); }
function esc_url($v) { return filter_var($v, FILTER_SANITIZE_URL); }
function esc_url_raw($v) { return esc_url($v); }
function wp_http_validate_url($v) { return filter_var($v, FILTER_VALIDATE_URL) ? $v : false; }
function wp_date($format) { return date($format); }
function wp_lostpassword_url() { return home_url('/reset/'); }
function wc_get_order($id) { return $GLOBALS['fixture_order'] ?? false; }
foreach (array('admin-request', 'registry', 'renderer', 'mailer', 'woocommerce') as $part) require YOGA_MAIL_PATH . 'includes/class-yoga-mail-' . $part . '.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$registry = new Yoga_Mail_Registry(); $renderer = new Yoga_Mail_Renderer($registry);
$mailer = new Yoga_Mail_Mailer($registry, $renderer);
$adapter = new Yoga_Mail_WooCommerce($registry, $renderer, $mailer);
$valid = $registry->values('payment-success-receipt');
check($registry->save_values('payment-success-receipt', $valid), 'valid receipt saved');
$stored = $options;
$invalid = $valid; $invalid['cta_url'] = 'https://';
check(!$registry->save_values('payment-success-receipt', $invalid), 'incomplete URL rejected before save');
check($stored === $options, 'invalid save preserves previous settings');
$invalid = $valid; $invalid['cta_url'] = '{{unknown_url}}';
check(!$registry->save_values('payment-success-receipt', $invalid), 'unknown tag rejected');
$options[Yoga_Mail_Registry::SETTINGS_OPTION] = array('woocommerce_enabled' => true);
$order = new class {
    public $sent = '';
    function get_id() { return 17; }
    function get_meta($key) { return $key === '_yoga_mail_payment_receipt_sent_at' ? $this->sent : ''; }
    function get_payment_method_title() { return 'СБП'; }
    function get_customer_id() { return 7; }
};
$GLOBALS['fixture_order'] = $order;
check($adapter->disable_standard_processing_email(true, $order), 'standard mail available when replacement failed');
$order->sent = '123';
check(!$adapter->disable_standard_processing_email(true, $order), 'standard mail suppressed after successful replacement');
class YTR_Saved_Cards { static function get_cards($id) { throw new RuntimeException('Unrelated saved cards must not be read'); } }
$method = new ReflectionMethod($adapter, 'payment_method_label');
check($method->invoke($adapter, $order) === 'СБП', 'receipt uses current order payment title');
$configure = new ReflectionMethod($adapter, 'add_email_filters');
$configure->invoke($adapter, 'customer_processing_order');
$options[Yoga_Mail_Registry::SETTINGS_OPTION] = array('woocommerce_enabled' => false);
$email = new class { function get_subject() { return 'subject'; } };
check(apply_filters('woocommerce_email_additional_content_customer_processing_order', 'Original body', null, $email) === 'Original body', 'disabled integration preserves body');
$rendered_count = 0;
foreach ($registry->all() as $id => $definition) {
    $result = $renderer->render($id, array(), true);
    check(!is_wp_error($result), 'template renders: ' . $id . (is_wp_error($result) ? ': ' . $result->get_error_message() : ''));
    $rendered_count++;
    if (!empty($argv[1])) {
        check(file_put_contents($argv[1] . '/' . $id . '.html', $result['html']) !== false, 'HTML fixture saved');
    }
}
echo "PASS: URL validation, save atomicity, payment title, mail fallback, disabled integration and $rendered_count template renders.\n";
