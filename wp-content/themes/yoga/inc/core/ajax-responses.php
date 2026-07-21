<?php
/**
 * Компонент темы: ajax responses.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yoga_ajax_error')) {
    function yoga_ajax_error($message, $code = 'error', $status = 400, $extra = array()) {
        $payload = array_merge(array(
            'code' => $code,
            'message' => $message,
        ), $extra);

        wp_send_json_error($payload, $status);
    }
}

if (!function_exists('yoga_ajax_success')) {
    function yoga_ajax_success($message = '', $data = array(), $status = 200) {
        $payload = array_merge(array(
            'message' => $message,
        ), $data);

        wp_send_json_success($payload, $status);
    }
}
