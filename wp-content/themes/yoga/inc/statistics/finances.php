<?php
/** Финансовая статистика через API заказов WooCommerce, включая HPOS. */
if (!defined('ABSPATH')) { exit; }

const YOGA_STATISTICS_FINANCE_CACHE = 'yoga_statistics_finance_v1';

function yoga_statistics_empty_finances(): array {
	return array('available' => function_exists('wc_get_orders'), 'error' => '', 'currencies' => array(), 'users' => array(), 'tariffs' => array(), 'updated_at' => time());
}

function yoga_statistics_money(float $amount): float {
	return round($amount, function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2);
}

/** Amounts are kept separate by currency; refunded and card-binding orders are handled explicitly. */
function yoga_statistics_add_order(array &$report, $order): void {
	if (!$order instanceof WC_Order || $order->get_meta('_ytr_card_binding') === 'yes') { return; }
	$currency = (string) $order->get_currency();
	$user_id = (int) $order->get_customer_id();
	$gross = max(0, (float) $order->get_total());
	$refunded = min($gross, max(0, (float) $order->get_total_refunded()));
	$net = yoga_statistics_money($gross - $refunded);
	if (!isset($report['currencies'][$currency])) {
		$report['currencies'][$currency] = array('paid' => 0.0, 'refunded' => 0.0, 'net' => 0.0, 'orders' => 0, 'guest_net' => 0.0);
	}
	$totals = &$report['currencies'][$currency];
	$totals['paid'] = yoga_statistics_money($totals['paid'] + $gross);
	$totals['refunded'] = yoga_statistics_money($totals['refunded'] + $refunded);
	$totals['net'] = yoga_statistics_money($totals['net'] + $net);
	$totals['orders']++;
	if ($user_id > 0) {
		$key = $currency . ':' . $user_id;
		if (!isset($report['users'][$key])) {
			$report['users'][$key] = array('user_id' => $user_id, 'currency' => $currency, 'orders' => 0, 'net' => 0.0);
		}
		$report['users'][$key]['orders']++;
		$report['users'][$key]['net'] = yoga_statistics_money($report['users'][$key]['net'] + $net);
	} else {
		$totals['guest_net'] = yoga_statistics_money($totals['guest_net'] + $net);
	}
	unset($totals);

	$line_refunds = array();
	$allocated_refunds = 0.0;
	foreach (array('line_item', 'fee', 'shipping') as $item_type) {
		foreach ($order->get_items($item_type) as $item_id => $item) {
			$line_refund = abs((float) $order->get_total_refunded_for_item((int) $item_id, $item_type));
			$taxes = $item->get_taxes();
			foreach (array_keys($taxes['total'] ?? array()) as $tax_id) {
				$line_refund += abs((float) $order->get_tax_refunded_for_item((int) $item_id, (int) $tax_id, $item_type));
			}
			$line_refunds[$item_id] = $line_refund;
			$allocated_refunds += $line_refund;
		}
	}
	$unallocated = max(0, $refunded - $allocated_refunds);
	$remaining = max(0, $gross - $allocated_refunds);
	$unallocated_ratio = $remaining > 0 ? max(0, 1 - $unallocated / $remaining) : 0;
	foreach ($order->get_items('line_item') as $item_id => $item) {
		$product_id = (int) $item->get_product_id();
		$is_tariff = $item->get_meta('_yoga_statistics_tariff') === 'yes'
			|| (function_exists('yoga_product_is_tariff') && yoga_product_is_tariff($product_id));
		if ($product_id <= 0 || !$is_tariff) { continue; }
		$key = $currency . ':' . $product_id;
		if (!isset($report['tariffs'][$key])) {
			$report['tariffs'][$key] = array('product_id' => $product_id, 'name' => $item->get_name(), 'currency' => $currency, 'quantity' => 0, 'orders' => 0, 'net' => 0.0);
		}
		$line_total = (float) $item->get_total() + (float) $item->get_total_tax();
		$line_refund = $line_refunds[$item_id] ?? 0;
		$quantity = max(0, (int) $item->get_quantity() - abs((int) $order->get_qty_refunded_for_item((int) $item_id)));
		if ($order->get_status() === 'refunded' || ($gross > 0 && $refunded >= $gross)) {
			$line_refund = $line_total;
			$quantity = 0;
		}
		$report['tariffs'][$key]['quantity'] += $quantity;
		$report['tariffs'][$key]['orders']++;
		$report['tariffs'][$key]['net'] = yoga_statistics_money($report['tariffs'][$key]['net'] + max(0, $line_total - $line_refund) * $unallocated_ratio);
	}
}

/** Read in bounded batches; never use SQL tied to one WooCommerce order storage. */
function yoga_statistics_finances(int $user_id = 0): array {
	$report = yoga_statistics_empty_finances();
	if (!$report['available']) { return $report; }
	if (!$user_id) {
		$cached = get_transient(YOGA_STATISTICS_FINANCE_CACHE);
		if (is_array($cached)) { return $cached; }
	}
	try {
		$page = 1;
		do {
			$args = array('type' => 'shop_order', 'status' => array_values(array_unique(array_merge(wc_get_is_paid_statuses(), array('refunded')))), 'limit' => 100, 'page' => $page, 'paginate' => true, 'orderby' => 'ID', 'order' => 'ASC', 'return' => 'objects');
			if ($user_id > 0) { $args['customer_id'] = $user_id; }
			$result = wc_get_orders($args);
			if (!is_object($result) || !isset($result->orders, $result->max_num_pages)) { throw new UnexpectedValueException('Invalid order query result'); }
			foreach ($result->orders as $order) { yoga_statistics_add_order($report, $order); }
			$page++;
		} while ($page <= (int) $result->max_num_pages);
	} catch (Throwable $error) {
		$report = yoga_statistics_empty_finances();
		$report['error'] = 'Не удалось прочитать заказы WooCommerce. Повторите попытку позже.';
		return $report;
	}
	$sort = static function (array $a, array $b): int {
		return strcmp($a['currency'], $b['currency']) ?: ($b['net'] <=> $a['net']);
	};
	uasort($report['users'], $sort);
	uasort($report['tariffs'], static function (array $a, array $b): int {
		return strcmp($a['currency'], $b['currency']) ?: (($b['quantity'] <=> $a['quantity']) ?: ($b['net'] <=> $a['net']));
	});
	if (!$user_id) {
		set_transient(YOGA_STATISTICS_FINANCE_CACHE, $report, 5 * MINUTE_IN_SECONDS);
	}
	return $report;
}

function yoga_statistics_clear_finance_cache(): void {
	delete_transient(YOGA_STATISTICS_FINANCE_CACHE);
}
foreach (array('woocommerce_new_order', 'woocommerce_update_order', 'woocommerce_order_status_changed', 'woocommerce_order_refunded', 'woocommerce_refund_deleted', 'woocommerce_delete_order', 'woocommerce_trash_order') as $hook) {
	add_action($hook, 'yoga_statistics_clear_finance_cache');
}

/** Keep the tariff classification of new purchases even if a product is later deleted. */
function yoga_statistics_snapshot_tariff_item($item_id, $item): void {
	if ($item instanceof WC_Order_Item_Product && function_exists('yoga_product_is_tariff') && yoga_product_is_tariff((int) $item->get_product_id())) {
		$item->add_meta_data('_yoga_statistics_tariff', 'yes', true);
		$item->save_meta_data();
	}
}
add_action('woocommerce_new_order_item', 'yoga_statistics_snapshot_tariff_item', 10, 2);
