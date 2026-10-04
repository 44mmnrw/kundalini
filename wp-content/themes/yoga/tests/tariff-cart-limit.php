<?php
/** Run without WordPress or database writes: php tests/tariff-cart-limit.php */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['hooks'] = array();
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_action($hook, $callback, $priority = 10, $args = 1) { add_filter($hook, $callback, $priority, $args); }
function has_term($term, $taxonomy, $id) { return in_array($id, array(10, 20), true); }
function wp_get_post_parent_id($id) { return $id === 21 ? 20 : 0; }
require dirname(__DIR__) . '/inc/woocommerce/tariff-cart.php';
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function item($id, $quantity = 1, $variation = 0) { return array('product_id' => $id, 'variation_id' => $variation, 'quantity' => $quantity); }
function cart_change($items) {
    foreach ($GLOBALS['hooks']['woocommerce_cart_contents_changed'] as $callback) { $items = $callback($items); }
    return $items;
}
$other = item(30, 4);
check(cart_change(array()) === array(), 'Empty cart stays empty');
check(cart_change(array('other' => $other)) === array('other' => $other), 'Non-tariff quantity is preserved');
$items = cart_change(array('first' => item(10, 3), 'other' => $other, 'second' => item(20, 5)));
check($items === array('other' => $other, 'second' => item(20)), 'New tariff replaces old tariff and has quantity one');
$items['variation'] = item(20, 2, 21);
$items = cart_change($items);
check($items === array('other' => $other, 'variation' => item(20, 1, 21)), 'Variation also replaces previous tariff');
check(cart_change(array('same' => item(10, 2))) === array('same' => item(10)), 'Repeated add cannot increase quantity');
check(cart_change($items) === $items, 'Normalization is idempotent');
class TestCart {
    public $items;
    public $totals_runs = 0;
    public $saved_items;
    function __construct($items) { $this->items = $items; }
    function get_cart_contents() { return $this->items; }
    function set_cart_contents($items) { $this->items = $items; }
    function calculate_totals() {
        $this->totals_runs++;
        yoga_normalize_tariff_cart($this);
        $this->saved_items = $this->items;
    }
}
$cart = new TestCart(array('old' => item(10, 2), 'new' => item(20, 4), 'other' => $other));
yoga_restore_single_tariff_cart($cart);
check($cart->items === array('new' => item(20), 'other' => $other), 'Restored cart contains only one tariff');
check($cart->totals_runs === 1 && $cart->saved_items === $cart->items, 'Restored totals and session are recalculated once');
yoga_restore_single_tariff_cart($cart);
check($cart->totals_runs === 1, 'Valid restored cart needs no recalculation');
$cart->items['new']['quantity'] = 8;
$cart->calculate_totals();
check($cart->items['new']['quantity'] === 1, 'Quantity update is normalized before totals');
$product = new class { function get_id() { return 21; } };
check(yoga_tariffs_are_sold_individually(false, $product), 'Tariff variation is sold individually');
$product = new class { function get_id() { return 30; } };
check(!yoga_tariffs_are_sold_individually(false, $product), 'Other products retain quantity support');
check(yoga_tariffs_are_sold_individually(true, $product), 'Existing individual-sale setting is preserved');
fwrite(STDOUT, "Tariff cart limit tests passed.\n");
