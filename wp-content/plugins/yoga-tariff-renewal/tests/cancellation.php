<?php
/** Standalone in-memory regression tests. No WordPress bootstrap or network. */
define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);
define('MINUTE_IN_SECONDS', 60);
function __($s) { return $s; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function add_option($key, $value, ...$args) { if (isset($GLOBALS['options'][$key])) return false; $GLOBALS['options'][$key] = $value; return true; }
function delete_option($key) { unset($GLOBALS['options'][$key]); }
function sanitize_key($s) { return $s; }
function sanitize_text_field($s) { return $s; }
function get_user_meta($id, $key, $single = true) { return $GLOBALS['meta'][$key] ?? ''; }
function update_user_meta($id, $key, $value) { $GLOBALS['meta'][$key] = $value; }
function delete_user_meta($id, $key) { unset($GLOBALS['meta'][$key]); }
function current_time($type) { return time(); }
function get_current_user_tariff($id) { return false; }
function get_user_active_subscription() { return false; }
function is_wp_error($value) { return false; }
class YTR_Tariff {
    public static function get_access_end_timestamp($id) { return 0; }
}
class WC_Order {
    public $status = 'pending';
    public function get_id() { return 123; }
    public function update_status($status, $message) { $this->status = $status; }
    public function add_order_note($message) {}
}
class YTR_Orders {
    public static function create_renewal_order($id, $product) {
        if ($GLOBALS['scenario'] === 'cancel') YTR_User::disable_auto_renew($id, true);
        if ($GLOBALS['scenario'] === 'remove') YTR_Saved_Cards::remove_card($id, 'old-card');
        if ($GLOBALS['scenario'] === 'replace') update_user_meta($id, YTR_User::META_PAYMENT_METHOD_ID, 'new-card');
        return $GLOBALS['order'] = new WC_Order();
    }
}
class YTR_YooKassa {
    public static function charge_renewal($order, $method) {
        ++$GLOBALS['charges'];
        return array('success' => true, 'payment_id' => 'payment', 'status' => 'succeeded', 'message' => 'Paid');
    }
}
require dirname(__DIR__) . '/includes/class-ytr-user.php';
require dirname(__DIR__) . '/includes/class-ytr-lk.php';
require dirname(__DIR__) . '/includes/class-ytr-saved-cards.php';
require dirname(__DIR__) . '/includes/class-ytr-renewal.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
foreach (array('normal', 'cancel', 'remove', 'replace') as $scenario) {
    $GLOBALS['scenario'] = $scenario;
    $GLOBALS['charges'] = 0;
    $GLOBALS['options'] = $GLOBALS['meta'] = array();
    YTR_User::enable_auto_renew(7, 660, 'old-card');
    update_user_meta(7, YTR_Saved_Cards::META_KEY, array(array('id' => 'old-card', 'payment_method_id' => 'old-card', 'recurring' => true, 'last4' => '1234')));
    $result = YTR_Renewal::renew_user(7);
    check($GLOBALS['charges'] === ($scenario === 'normal' ? 1 : 0), 'Unexpected charge in ' . $scenario);
    check($result['success'] === ($scenario === 'normal'), 'Unexpected result in ' . $scenario);
    if ($scenario !== 'normal') check($GLOBALS['order']->status === 'cancelled', 'Order must be cancelled');
    if ($scenario === 'remove') {
        check(YTR_Saved_Cards::get_cards(7) === array(), 'Removed card stays absent');
        check(YTR_LK::was_auto_renew_cancelled(7), 'Removing charge card marks cancellation');
        check(!YTR_Renewal::user_needs_renewal(7), 'No later charge after card removal');
    }
    check(!isset($GLOBALS['options']['ytr_renewal_lock_user_7']), 'User lock released');
}
$GLOBALS['meta'] = array();
YTR_User::enable_auto_renew(7, 660, 'old-card');
$result = YTR_LK::cancel_subscription(7);
check($result['success'] && $result['access_end'] === '', 'Expired subscription can be cancelled');
check(strpos($result['message'], 'Доступ сохранится') === false, 'No access promise for expired tariff');
check(!YTR_Renewal::user_needs_renewal(7), 'Cancellation prevents later renewal');
check(YTR_User::get_payment_method_id(7) === '', 'Cancellation clears charge token');

// Render the actual expired-subscription branch from the account template.
$source = file_get_contents(dirname(__DIR__, 3) . '/themes/yoga/templates-page/lk.php');
$start = strpos($source, '<?php if (!$current_subscription && $ytr_auto_renew_active) : ?>');
check($start !== false, 'Expired subscription branch exists');
$end = strpos($source, '<?php endif; ?>', $start) + strlen('<?php endif; ?>');
$template = substr($source, $start, $end - $start);
foreach (array(array(false, true, true), array(false, false, false), array(true, true, false)) as $case) {
    list($current_subscription, $ytr_auto_renew_active, $expected) = $case;
    ob_start(); eval('?>' . $template); $html = ob_get_clean();
    check((strpos($html, 'id="ytr-cancel-subscription-btn"') !== false) === $expected, 'Expired cancellation button visibility');
}
echo "PASS: cancellation after expiry, account button, payment setting changes before charge.\n";
