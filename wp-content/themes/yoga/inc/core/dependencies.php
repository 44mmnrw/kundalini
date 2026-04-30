<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yoga_has_acf')) {
    function yoga_has_acf() {
        return function_exists('get_field');
    }
}

if (!function_exists('yoga_has_woocommerce')) {
    function yoga_has_woocommerce() {
        return function_exists('WC') && class_exists('WooCommerce');
    }
}

if (!function_exists('yoga_require_woocommerce_for_ajax')) {
    function yoga_require_woocommerce_for_ajax() {
        if (yoga_has_woocommerce()) {
            return true;
        }

        yoga_ajax_error('WooCommerce недоступен', 'woocommerce_unavailable', 503);
        return false;
    }
}
