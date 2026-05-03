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

if (!function_exists('yoga_register_popular_practices_fields')) {
    function yoga_register_popular_practices_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_popular_practices_section',
            'title' => 'Главная страница - Популярные практики',
            'fields' => array(
                array(
                    'key' => 'field_popular_practices_title',
                    'label' => 'Заголовок секции',
                    'name' => 'popular_practices_title',
                    'type' => 'text',
                    'default_value' => 'ПОПУЛЯРНЫЕ ПРАКТИКИ',
                ),
                array(
                    'key' => 'field_popular_practices_items',
                    'label' => 'Популярные практики',
                    'name' => 'popular_practices_items',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'button_label' => 'Добавить практику',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_practice_image',
                            'label' => 'Изображение практики',
                            'name' => 'practice_image',
                            'type' => 'image',
                            'required' => 1,
                            'return_format' => 'url',
                            'preview_size' => 'medium',
                            'library' => 'all',
                        ),
                        array(
                            'key' => 'field_practice_title',
                            'label' => 'Название практики',
                            'name' => 'practice_title',
                            'type' => 'text',
                            'required' => 1,
                        ),
                        array(
                            'key' => 'field_practice_description',
                            'label' => 'Описание практики',
                            'name' => 'practice_description',
                            'type' => 'textarea',
                            'required' => 1,
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_practice_style',
                            'label' => 'Стиль карточки',
                            'name' => 'practice_style',
                            'type' => 'select',
                            'choices' => array(
                                'default' => 'Стандартная',
                                'popular-practice_pink' => 'Розовая',
                                'popular-practice_green' => 'Зеленая',
                            ),
                            'default_value' => 'default',
                            'allow_null' => 0,
                            'ui' => 0,
                            'ajax' => 0,
                            'return_format' => 'value',
                        ),
                        array(
                            'key' => 'field_practice_link',
                            'label' => 'Ссылка на практику',
                            'name' => 'practice_link',
                            'type' => 'text',
                            'required' => 0,
                        ),
                        array(
                            'key' => 'field_practice_animation',
                            'label' => 'Анимация',
                            'name' => 'practice_animation',
                            'type' => 'select',
                            'choices' => array(
                                'wow fadeIn' => 'Fade In',
                                'wow fadeInUp' => 'Fade In Up',
                                'wow fadeInLeft' => 'Fade In Left',
                                'wow fadeInRight' => 'Fade In Right',
                            ),
                            'default_value' => 'wow fadeIn',
                            'required' => 0,
                            'allow_null' => 0,
                            'ui' => 0,
                            'ajax' => 0,
                            'return_format' => 'value',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'page_type',
                        'operator' => '==',
                        'value' => 'front_page',
                    ),
                ),
            ),
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'show_in_rest' => 0,
        ));
    }
}

add_action('acf/init', 'yoga_register_popular_practices_fields');
