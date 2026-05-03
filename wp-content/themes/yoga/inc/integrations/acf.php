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

if (!function_exists('yoga_register_tariff_fields')) {
    function yoga_register_tariff_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_tariff_fields',
            'title' => 'Поля тарифов',
            'fields' => array(
                array(
                    'key' => 'field_tariff_highlighted',
                    'label' => 'Выделенный тариф',
                    'name' => 'tariff_highlighted',
                    'type' => 'true_false',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_tariff_bg_image',
                    'label' => 'Фоновое изображение',
                    'name' => 'tariff_bg_image',
                    'type' => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key' => 'field_tariff_features',
                    'label' => 'Особенности тарифа',
                    'name' => 'tariff_features',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_feature_text',
                            'label' => 'Текст особенности',
                            'name' => 'feature_text',
                            'type' => 'text',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_price_period',
                    'label' => 'Период тарифа',
                    'name' => 'price_period',
                    'type' => 'select',
                    'choices' => array(
                        'month' => 'Месяц',
                        'year' => 'Год',
                    ),
                    'default_value' => 'month',
                ),
                array(
                    'key' => 'field_price_text',
                    'label' => 'Текст после цены',
                    'name' => 'price_text',
                    'type' => 'text',
                    'default_value' => '/месяц',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
        ));
    }
}

add_action('acf/init', 'yoga_register_tariff_fields');

