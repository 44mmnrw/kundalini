<?php
/**
 * Plugin Name: Yoga Admin Heartbeat
 * Description: Reduces background polling from open WordPress admin tabs.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
    exit;
}

function yoga_admin_heartbeat_settings(array $settings): array {
    if (!is_admin()) {
        return $settings;
    }

    // Core honours minimalInterval even when the editor requests a faster tick.
    // 60 seconds leaves room to refresh the default 150-second post lock and
    // keeps Heartbeat available for autosave and authentication checks.
    $minimum = isset($settings['minimalInterval']) ? (int) $settings['minimalInterval'] : 0;
    $minimum = $minimum > 0 && $minimum <= 600 ? $minimum : 0;
    $settings['minimalInterval'] = max(60, $minimum);
    $settings['interval'] = max(
        $settings['minimalInterval'],
        isset($settings['interval']) ? (int) $settings['interval'] : 60
    );

    return $settings;
}
add_filter('heartbeat_settings', 'yoga_admin_heartbeat_settings', PHP_INT_MAX);
