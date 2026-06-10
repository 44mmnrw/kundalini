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

// Группа «Поля тарифов» (group_tariff_fields) — только в ACF → Field Groups (БД).
// Локальная регистрация удалена, чтобы не перебивать настройки из админки.

if (!function_exists('yoga_register_practice_type_fields')) {
    function yoga_register_practice_type_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_practice_type_fields',
            'title' => 'Поля типа практики',
            'fields' => array(
                array(
                    'key' => 'field_practice_type_card_image',
                    'label' => 'Картинка карточки',
                    'name' => 'practice_type_card_image',
                    'type' => 'image',
                    'return_format' => 'id',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ),
                array(
                    'key' => 'field_practice_type_card_color',
                    'label' => 'Цвет карточки',
                    'name' => 'practice_type_card_color',
                    'type' => 'select',
                    'choices' => array(
                        'green' => 'Зеленая',
                        'violet' => 'Фиолетовая (прозрачная)',
                        'pink' => 'Розовая',
                        'violet_alt' => 'Фиолетовая (прозрачная, вариант 2)',
                    ),
                    'default_value' => 'violet',
                    'allow_null' => 0,
                    'ui' => 1,
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_practice_type_card_order',
                    'label' => 'Порядок карточки',
                    'name' => 'practice_type_card_order',
                    'type' => 'number',
                    'default_value' => 0,
                    'min' => 0,
                    'step' => 1,
                    'placeholder' => '0',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'practice-type',
                    ),
                ),
            ),
        ));
    }
}

add_action('acf/init', 'yoga_register_practice_type_fields');

if (!function_exists('yoga_sync_practice_type_term_fields')) {
    /**
     * Подстраховка сохранения полей taxonomy-term:
     * пишем значения напрямую в termmeta, чтобы цвет/порядок гарантированно сохранялись.
     */
    function yoga_sync_practice_type_term_fields($term_id) {
        if (!is_admin() || empty($_POST['acf']) || !is_array($_POST['acf'])) {
            return;
        }

        $term_id = (int) $term_id;
        if ($term_id <= 0) {
            return;
        }

        $acf_payload = $_POST['acf'];

        $color = isset($acf_payload['field_practice_type_card_color']) ? sanitize_key((string) $acf_payload['field_practice_type_card_color']) : '';
        if ($color !== '') {
            update_term_meta($term_id, 'practice_type_card_color', $color);
        } else {
            delete_term_meta($term_id, 'practice_type_card_color');
        }

        $order = isset($acf_payload['field_practice_type_card_order']) ? (int) $acf_payload['field_practice_type_card_order'] : 0;
        update_term_meta($term_id, 'practice_type_card_order', $order);
    }
}

add_action('created_practice-type', 'yoga_sync_practice_type_term_fields', 20);
add_action('edited_practice-type', 'yoga_sync_practice_type_term_fields', 20);

if (!function_exists('yoga_register_homepage_section_toggles')) {
    function yoga_register_homepage_section_toggles() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_homepage_section_toggles',
            'title' => 'Главная: отображение секций',
            'fields' => array(
                array(
                    'key' => 'field_show_videos_section',
                    'label' => 'Показывать секцию Видео',
                    'name' => 'show_videos_section',
                    'type' => 'true_false',
                    'ui' => 1,
                    'default_value' => 1,
                ),
                array(
                    'key' => 'field_show_review_people_photos',
                    'label' => 'Показывать фото людей в отзывах',
                    'name' => 'show_review_people_photos',
                    'type' => 'true_false',
                    'ui' => 1,
                    'default_value' => 1,
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
            'position' => 'side',
        ));
    }
}

add_action('acf/init', 'yoga_register_homepage_section_toggles');

if (!function_exists('yoga_register_theme_cta_fields')) {
    function yoga_register_theme_cta_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_theme_cta_fields',
            'title' => 'Тексты кнопок покупки',
            'fields' => array(
                array(
                    'key' => 'field_purchase_cta_text',
                    'label' => 'Текст CTA покупки',
                    'name' => 'purchase_cta_text',
                    'type' => 'text',
                    'default_value' => 'Выбрать тариф',
                    'placeholder' => 'Выбрать тариф',
                    'instructions' => 'Единый текст для кнопок покупки/подписки в теме.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-general-settings',
                    ),
                ),
            ),
        ));
    }
}

add_action('acf/init', 'yoga_register_theme_cta_fields');

if (!function_exists('yoga_register_guest_practice_sections_fields')) {
	function yoga_register_guest_practice_sections_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		$choices = function_exists('yoga_get_practice_section_layout_choices')
			? yoga_get_practice_section_layout_choices()
			: array(
				'anchor_01' => 'Anchor 01 — О крийе',
				'anchor_02' => 'Anchor 02 — Эффекты крийи',
				'anchor_03' => 'Anchor 03 — Философия практики',
				'anchor_04' => 'Anchor 04 — Рекомендации',
				'anchor_05' => 'Anchor 05 — Техника выполнения',
				'anchor_06' => 'Anchor 06 — Комментарии',
			);

		acf_add_local_field_group(
			array(
				'key'                   => 'group_guest_practice_sections',
				'title'                 => 'Практики: секции для гостей',
				'fields'                => array(
					array(
						'key'           => 'field_guest_practice_sections',
						'label'         => 'Секции, видимые гостям',
						'name'          => 'guest_practice_sections',
						'type'          => 'checkbox',
						'instructions'  => 'Для гостей и пользователей без активного тарифа. Отметьте якоря (Anchor 01–06), которые они увидят. Если ничего не выбрано — показываются все секции. С оплаченным тарифом — всегда все секции.',
						'choices'       => $choices,
						'layout'        => 'vertical',
						'return_format' => 'value',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'theme-general-settings',
						),
					),
				),
				'menu_order'            => 6,
			)
		);
	}
}

add_action('acf/init', 'yoga_register_guest_practice_sections_fields', 15);

if (!function_exists('yoga_register_theme_smartcaptcha_fields')) {
    function yoga_register_theme_smartcaptcha_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_theme_smartcaptcha',
            'title' => 'Yandex SmartCaptcha',
            'fields' => array(
                array(
                    'key' => 'field_smartcaptcha_intro',
                    'label' => '',
                    'name' => '',
                    'type' => 'message',
                    'message' => 'Ключи из <a href="https://yandex.cloud/ru/docs/smartcaptcha/" target="_blank" rel="noopener noreferrer">консоли Yandex Cloud</a>. Если заданы константы <code>YOGA_SMARTCAPTCHA_CLIENT_KEY</code> и <code>YOGA_SMARTCAPTCHA_SERVER_KEY</code> в <code>wp-config.php</code>, они имеют приоритет над этими полями.',
                    'new_lines' => 'wpautop',
                    'esc_html' => 0,
                ),
                array(
                    'key' => 'field_smartcaptcha_client_key',
                    'label' => 'Клиентский ключ (site key)',
                    'name' => 'smartcaptcha_client_key',
                    'type' => 'text',
                    'instructions' => 'Отображается в браузере в виджете капчи. Добавьте домен сайта в настройках капчи в облаке.',
                    'placeholder' => '',
                ),
                array(
                    'key' => 'field_smartcaptcha_server_key',
                    'label' => 'Серверный ключ (secret)',
                    'name' => 'smartcaptcha_server_key',
                    'type' => 'text',
                    'instructions' => 'Только для проверки токена на сервере. Не используйте в JavaScript.',
                    'placeholder' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-general-settings',
                    ),
                ),
            ),
            'menu_order' => 5,
        ));
    }
}

add_action('acf/init', 'yoga_register_theme_smartcaptcha_fields');

