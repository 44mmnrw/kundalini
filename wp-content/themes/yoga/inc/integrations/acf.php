<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yoga_register_acf_options_page')) {
    function yoga_register_acf_options_page() {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page(array(
            'page_title' => 'Общие настройки темы',
            'menu_title' => 'Настройки темы',
            'menu_slug' => 'theme-general-settings',
            'capability' => 'edit_posts',
            'redirect' => false,
        ));
    }
}

add_action('acf/init', 'yoga_register_acf_options_page');
