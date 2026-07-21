<?php
/**
 * Компонент темы: acf.
 *
 * @package Yoga
 */
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

if (!function_exists('yoga_register_tariff_frontend_access_fields')) {
	function yoga_register_tariff_frontend_access_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(array(
			'key' => 'group_tariff_frontend_access_fields',
			'title' => 'Доступ на фронте',
			'fields' => array(
				array(
					'key' => 'field_tariff_hide_audio_section_paywall',
					'label' => 'Не показывать заглушку для аудио',
					'name' => 'hide_audio_section_paywall',
					'type' => 'true_false',
					'instructions' => 'Если включено, пользователи этого тарифа не увидят заглушку закрытых секций с аудио. Сама секция остается недоступной, она просто скрывается вместе с пунктом меню.',
					'ui' => 1,
					'default_value' => 0,
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
			'position' => 'side',
			'menu_order' => 3,
		));
	}
}

add_action('acf/init', 'yoga_register_tariff_frontend_access_fields', 15);




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

if (!function_exists('yoga_register_footer_settings_fields')) {
	function yoga_register_footer_settings_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(array(
			'key' => 'group_footer_settings',
			'title' => 'Футер',
			'fields' => array(
				array(
					'key' => 'field_footer_requisites',
					'label' => 'Реквизиты',
					'name' => 'footer_requisites',
					'type' => 'textarea',
					'instructions' => 'Каждая строка выводится отдельной строкой в футере.',
					'default_value' => "ИП КСЕНОФОНТОВА МАРИНА ЕВГЕНЬЕВНА\nИНН 632200860531\nОГРНИП 319631300101827",
					'new_lines' => '',
					'rows' => 4,
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
			'menu_order' => 9,
		));
	}
}

add_action('acf/init', 'yoga_register_footer_settings_fields', 15);

if (!function_exists('yoga_register_practice_timer_signal_fields')) {
	function yoga_register_practice_timer_signal_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(array(
			'key' => 'group_practice_timer_signal',
			'title' => 'Практики: таймер',
			'fields' => array(
				array(
					'key' => 'field_practice_timer_end_signal_file',
					'label' => 'Файл сигнала в конце таймера',
					'name' => 'practice_timer_end_signal_file',
					'type' => 'file',
					'instructions' => 'Один общий аудиофайл для упражнений, где включена галочка «Сигнал в конце».',
					'return_format' => 'array',
					'library' => 'all',
					'mime_types' => 'mp3,wav,ogg,m4a',
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
			'menu_order' => 7,
		));
	}
}

add_action('acf/init', 'yoga_register_practice_timer_signal_fields', 15);

if (!function_exists('yoga_register_copy_protection_settings_fields')) {
	function yoga_register_copy_protection_settings_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(array(
			'key' => 'group_copy_protection_settings',
			'title' => 'Защита контента',
			'fields' => array(
				array(
					'key' => 'field_copy_protection_block_devtools_shortcuts',
					'label' => 'Блокировать F12 и горячие клавиши DevTools',
					'name' => 'copy_protection_block_devtools_shortcuts',
					'type' => 'true_false',
					'instructions' => 'Если включено, на фронтенде блокируются F12 и Ctrl/Cmd+Shift+I/J/C/K. По умолчанию выключено, чтобы не мешать разработке.',
					'ui' => 1,
					'default_value' => 0,
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
			'menu_order' => 8,
		));
	}
}

add_action('acf/init', 'yoga_register_copy_protection_settings_fields', 15);

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
						'instructions'  => 'Для гостей и пользователей без активного тарифа. Отметьте якоря (Anchor 01–06), которые они увидят. Если ничего не выбрано — показываются все секции. Отдельную практику можно открыть полностью в её карточке: «Доступ для гостей». С оплаченным тарифом — всегда все секции.',
						'choices'       => $choices,
						'layout'        => 'vertical',
						'return_format' => 'value',
					),
					array(
						'key'           => 'field_practice_questions_hidden_tariffs',
						'label'         => 'Скрывать блок «Остались вопросы?» для тарифов',
						'name'          => 'practice_questions_hidden_tariffs',
						'type'          => 'post_object',
						'instructions'  => 'Выберите тарифы, для которых на страницах практик не нужно показывать форму вопроса. Если поле пустое — блок показывается всем.',
						'post_type'     => array('product'),
						'return_format' => 'id',
						'multiple'      => 1,
						'allow_null'    => 1,
						'ui'            => 1,
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

if (!function_exists('yoga_register_practice_guest_access_fields')) {
	function yoga_register_practice_guest_access_fields() {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_practice_guest_access',
				'title'    => 'Доступ для гостей',
				'fields'   => array(
					array(
						'key'           => 'field_practice_open_for_guests',
						'label'         => 'Открыть полностью для гостей',
						'name'          => 'practice_open_for_guests',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
						'instructions'  => 'Если включено, гости и пользователи без активного тарифа увидят все секции этой практики, несмотря на ограничения в «Настройки темы» → «Секции, видимые гостям».',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'practice',
						),
					),
				),
				'position' => 'side',
				'menu_order' => 2,
			)
		);
	}
}

add_action('acf/init', 'yoga_register_practice_guest_access_fields', 15);

if (!function_exists('yoga_delay_acf_wysiwyg_editors')) {
	function yoga_delay_acf_wysiwyg_editors(array $field): array {
		if (!is_admin()) {
			return $field;
		}

		$field['delay'] = 1;

		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		$post_type = $post_id > 0 ? get_post_type($post_id) : '';
		if ($post_type === 'practice') {
			$field['toolbar'] = 'full';
			$field['media_upload'] = 1;
			$field['tabs'] = 'all';
		}

		return $field;
	}
}

add_filter('acf/load_field/type=wysiwyg', 'yoga_delay_acf_wysiwyg_editors', 20);

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

