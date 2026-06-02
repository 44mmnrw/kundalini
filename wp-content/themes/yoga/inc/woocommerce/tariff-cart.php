<?php
/**
 * Корзина тарифов: один тариф, замена при выборе другого.
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_ensure_wc_cart_session')) {
	function yoga_ensure_wc_cart_session(): void {
		if (!function_exists('WC')) {
			return;
		}

		if (function_exists('wc_load_cart')) {
			wc_load_cart();
		}

		if (!WC()->session) {
			return;
		}

		if (!WC()->session->has_session()) {
			WC()->session->set_customer_session_cookie(true);
		}
	}
}

if (!function_exists('yoga_persist_cart')) {
	function yoga_persist_cart(): void {
		if (!function_exists('WC') || !WC()->cart) {
			return;
		}

		WC()->cart->calculate_totals();

		if (WC()->session) {
			WC()->session->set_customer_session_cookie(true);
			WC()->cart->set_session();
			WC()->session->save_data();
		}

		if (method_exists(WC()->cart, 'maybe_set_cart_cookies')) {
			WC()->cart->maybe_set_cart_cookies();
		}
	}
}

if (!function_exists('yoga_product_is_tariff')) {
	function yoga_product_is_tariff(int $product_id): bool {
		if ($product_id <= 0) {
			return false;
		}

		if (has_term('tariffs', 'product_cat', $product_id)) {
			return true;
		}

		$parent_id = wp_get_post_parent_id($product_id);

		return $parent_id > 0 && has_term('tariffs', 'product_cat', $parent_id);
	}
}

if (!function_exists('yoga_is_theme_checkout_context')) {
	function yoga_is_theme_checkout_context(): bool {
		if (function_exists('yoga_is_order_received_request') && yoga_is_order_received_request()) {
			return false;
		}
		if (function_exists('is_order_received_page') && is_order_received_page()) {
			return false;
		}

		if (function_exists('is_checkout') && is_checkout()) {
			return true;
		}

		if (!function_exists('wc_get_page_id')) {
			return false;
		}

		$checkout_id = (int) wc_get_page_id('checkout');

		return $checkout_id > 0 && is_page($checkout_id);
	}
}

if (!function_exists('yoga_get_tariff_form_action_url')) {
	function yoga_get_tariff_form_action_url(): string {
		return function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
	}
}

/**
 * Обработка POST удаления / добавления до вывода HTML (checkout.php).
 */
if (!function_exists('yoga_handle_cart_mutation_request')) {
	function yoga_handle_cart_mutation_request(): bool {
		if (!function_exists('WC') || !WC()->cart || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return false;
		}

		yoga_ensure_wc_cart_session();
		nocache_headers();

		$checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');

		// Удаление тарифа.
		if (!empty($_POST['yoga_remove'])) {
			$key = sanitize_text_field(wp_unslash($_POST['yoga_remove']));
			$nonce = isset($_POST['yoga_remove_nonce']) ? sanitize_text_field(wp_unslash($_POST['yoga_remove_nonce'])) : '';

			if ($nonce !== '' && wp_verify_nonce($nonce, 'yoga-cart') && $key !== '') {
				if (WC()->cart->get_cart_item($key)) {
					WC()->cart->remove_cart_item($key);
					yoga_persist_cart();
					if (function_exists('wc_add_notice')) {
						wc_add_notice(__('Тариф удалён.', 'yoga'), 'success');
					}
				} elseif (function_exists('wc_add_notice')) {
					wc_add_notice(__('Тариф не найден в корзине.', 'yoga'), 'error');
				}
			} elseif (function_exists('wc_add_notice')) {
				wc_add_notice(__('Не удалось подтвердить удаление. Обновите страницу.', 'yoga'), 'error');
			}

			wp_safe_redirect(remove_query_arg(array('remove_item', 'yoga_remove', '_wpnonce', 'add-to-cart'), $checkout_url));
			exit;
		}

		// Добавление / замена тарифа (форма с главной / тарифов).
		if (empty($_POST['yoga_add_tariff']) || !isset($_POST['add-to-cart'], $_POST['woocommerce-add-to-cart-nonce'])) {
			return false;
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce-add-to-cart-nonce'])), 'woocommerce-add-to-cart')) {
			if (function_exists('wc_add_notice')) {
				wc_add_notice(__('Ошибка безопасности. Обновите страницу.', 'yoga'), 'error');
			}
			wp_safe_redirect(wp_get_referer() ?: home_url('/'));
			exit;
		}

		$product_id = absint($_POST['add-to-cart']);
		if ($product_id <= 0 || !yoga_product_is_tariff($product_id)) {
			return false;
		}

		WC()->cart->empty_cart(true);

		$variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
		$variations = array();
		foreach ($_POST as $field_key => $field_value) {
			if (strpos((string) $field_key, 'attribute_') === 0) {
				$variations[(string) $field_key] = sanitize_text_field(wp_unslash($field_value));
			}
		}

		if ($variation_id > 0) {
			$added = WC()->cart->add_to_cart($product_id, 1, $variation_id, $variations);
		} else {
			$added = WC()->cart->add_to_cart($product_id, 1);
		}

		if ($added) {
			yoga_persist_cart();
			wp_safe_redirect($checkout_url);
			exit;
		}

		if (function_exists('wc_add_notice')) {
			wc_add_notice(__('Не удалось добавить тариф.', 'yoga'), 'error');
		}
		wp_safe_redirect(wp_get_referer() ?: home_url('/'));
		exit;
	}
}

add_action('woocommerce_init', 'yoga_ensure_wc_cart_session', 1);
add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false');

add_action('template_redirect', 'yoga_checkout_nocache_headers', 0);
function yoga_checkout_nocache_headers(): void {
	if (function_exists('yoga_is_theme_checkout_context') && yoga_is_theme_checkout_context()) {
		nocache_headers();
	}
}

add_action('wp_loaded', 'yoga_handle_cart_mutation_request', 99);
add_action('woocommerce_cart_item_removed', 'yoga_persist_cart', 20);
add_action('woocommerce_add_to_cart', 'yoga_persist_cart', 20);
