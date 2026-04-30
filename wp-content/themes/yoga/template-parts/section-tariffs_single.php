<?php
// Добавьте в functions.php или создайте через интерфейс ACF

// 1. Поле для выделенного тарифа
add_action('acf/init', 'register_tariff_fields');
function register_tariff_fields() {
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
?>