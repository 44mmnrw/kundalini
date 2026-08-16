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
					'key' => 'field_copy_protection_disable_context_menu_blocking',
					'label' => 'Отключить блокировку правой кнопки мыши',
					'name' => 'copy_protection_disable_context_menu_blocking',
					'type' => 'true_false',
					'instructions' => 'Если включено, контекстное меню браузера будет открываться по щелчку правой кнопкой мыши. Остальная защита контента продолжит работать.',
					'ui' => 1,
					'default_value' => 0,
				),
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

if (!function_exists('yoga_add_practice_exercise_modification_name_field')) {
	/**
	 * Adds a deployable name field to the database-defined exercise repeater.
	 */
	function yoga_add_practice_exercise_modification_name_field(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			$field['sub_fields'] = array();
		}

		foreach ($field['sub_fields'] as $sub_field) {
			if (
				(isset($sub_field['key']) && $sub_field['key'] === 'field_ex_modification_name')
				|| (isset($sub_field['name']) && $sub_field['name'] === 'modification_name')
			) {
				return $field;
			}
		}

		$modification_name_field = array(
			'ID'                => 0,
			'key'               => 'field_ex_modification_name',
			'label'             => 'Название модификации',
			'name'              => 'modification_name',
			'_name'             => 'modification_name',
			'prefix'            => 'acf',
			'type'              => 'text',
			'instructions'      => 'Например: Для спины',
			'required'          => 0,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_ex_has_modifications',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'default_value'     => '',
			'placeholder'       => 'Для спины',
			'parent'            => $field['ID'] ?? 0,
			'parent_repeater'   => 'field_exercise_items',
		);

		if (function_exists('acf_get_valid_field')) {
			$modification_name_field = acf_get_valid_field($modification_name_field);
		}

		$insert_at = 0;
		foreach ($field['sub_fields'] as $index => $sub_field) {
			if (isset($sub_field['key']) && $sub_field['key'] === 'field_ex_has_modifications') {
				$insert_at = $index + 1;
				break;
			}
		}

		array_splice($field['sub_fields'], $insert_at, 0, array($modification_name_field));

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_modification_name_field', 20);

if (!function_exists('yoga_add_practice_exercise_execution_name_field')) {
	/**
	 * Adds an editable label for the main exercise tab when modifications are enabled.
	 */
	function yoga_add_practice_exercise_execution_name_field(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			$field['sub_fields'] = array();
		}

		foreach ($field['sub_fields'] as $sub_field) {
			if (
				(isset($sub_field['key']) && $sub_field['key'] === 'field_ex_execution_name')
				|| (isset($sub_field['name']) && $sub_field['name'] === 'execution_name')
			) {
				return $field;
			}
		}

		$execution_name_field = array(
			'ID'                => 0,
			'key'               => 'field_ex_execution_name',
			'label'             => 'Название первой вкладки',
			'name'              => 'execution_name',
			'_name'             => 'execution_name',
			'prefix'            => 'acf',
			'type'              => 'text',
			'instructions'      => 'Например: Выполнение или Для начинающих',
			'required'          => 0,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_ex_has_modifications',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'default_value'     => '',
			'placeholder'       => 'Выполнение',
			'parent'            => $field['ID'] ?? 0,
			'parent_repeater'   => 'field_exercise_items',
		);

		if (function_exists('acf_get_valid_field')) {
			$execution_name_field = acf_get_valid_field($execution_name_field);
		}

		$insert_at = 0;
		foreach ($field['sub_fields'] as $index => $sub_field) {
			if (isset($sub_field['key']) && $sub_field['key'] === 'field_ex_has_modifications') {
				$insert_at = $index + 1;
				break;
			}
		}

		array_splice($field['sub_fields'], $insert_at, 0, array($execution_name_field));

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_execution_name_field', 21);

if (!function_exists('yoga_add_practice_exercise_modification_detail_fields')) {
	/**
	 * Adds separate content and additional-details fields for an exercise modification.
	 */
	function yoga_add_practice_exercise_modification_detail_fields(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			$field['sub_fields'] = array();
		}

		$existing_names = array();
		foreach ($field['sub_fields'] as $sub_field) {
			if (!empty($sub_field['name'])) {
				$existing_names[] = (string) $sub_field['name'];
			}
		}

		$conditional_logic = array(
			array(
				array(
					'field'    => 'field_ex_has_modifications',
					'operator' => '==',
					'value'    => '1',
				),
			),
		);

		$fields_to_add = array();
		if (!in_array('matter_mod', $existing_names, true)) {
			$fields_to_add[] = array(
				'ID'                => 0,
				'key'               => 'field_ex_matter_mod',
				'label'             => 'Содержание (Модификация)',
				'name'              => 'matter_mod',
				'_name'             => 'matter_mod',
				'prefix'            => 'acf',
				'type'              => 'repeater',
				'instructions'      => 'Если не заполнено, используется содержание основной версии упражнения.',
				'required'          => 0,
				'conditional_logic' => $conditional_logic,
				'wrapper'           => array('width' => '', 'class' => '', 'id' => ''),
				'layout'            => 'table',
				'min'               => 0,
				'max'               => 0,
				'collapsed'         => '',
				'button_label'      => 'Добавить',
				'rows_per_page'     => 20,
				'parent'            => $field['ID'] ?? 0,
				'parent_repeater'   => 'field_exercise_items',
				'sub_fields'        => array(
					array(
						'ID'              => 0,
						'key'             => 'field_ex_matter_mod_title',
						'label'           => 'Заголовок',
						'name'            => 'title',
						'_name'           => 'title',
						'prefix'          => 'acf',
						'type'            => 'text',
						'instructions'    => '',
						'required'        => 0,
						'conditional_logic' => 0,
						'wrapper'         => array('width' => '', 'class' => '', 'id' => ''),
						'default_value'   => '',
						'maxlength'       => '',
						'placeholder'     => '',
						'prepend'         => '',
						'append'          => '',
						'parent'          => 0,
						'parent_repeater' => 'field_ex_matter_mod',
					),
					array(
						'ID'              => 0,
						'key'             => 'field_ex_matter_mod_description',
						'label'           => 'Описание',
						'name'            => 'description',
						'_name'           => 'description',
						'prefix'          => 'acf',
						'type'            => 'text',
						'instructions'    => '',
						'required'        => 0,
						'conditional_logic' => 0,
						'wrapper'         => array('width' => '', 'class' => '', 'id' => ''),
						'default_value'   => '',
						'maxlength'       => '',
						'placeholder'     => '',
						'prepend'         => '',
						'append'          => '',
						'parent'          => 0,
						'parent_repeater' => 'field_ex_matter_mod',
					),
				),
			);
		}

		if (!in_array('details_mod', $existing_names, true)) {
			$fields_to_add[] = array(
				'ID'                => 0,
				'key'               => 'field_ex_details_mod',
				'label'             => 'Доп. детали (Модификация)',
				'name'              => 'details_mod',
				'_name'             => 'details_mod',
				'prefix'            => 'acf',
				'type'              => 'textarea',
				'instructions'      => 'Если не заполнено, используются дополнительные детали основной версии упражнения.',
				'required'          => 0,
				'conditional_logic' => $conditional_logic,
				'wrapper'           => array('width' => '', 'class' => '', 'id' => ''),
				'default_value'     => '',
				'new_lines'         => '',
				'maxlength'         => '',
				'placeholder'       => '',
				'rows'              => '',
				'parent'            => $field['ID'] ?? 0,
				'parent_repeater'   => 'field_exercise_items',
			);
		}

		if ($fields_to_add === array()) {
			return $field;
		}

		if (function_exists('acf_get_valid_field')) {
			foreach ($fields_to_add as $index => $field_to_add) {
				$fields_to_add[$index] = acf_get_valid_field($field_to_add);
			}
		}

		$insert_at = count($field['sub_fields']);
		foreach ($field['sub_fields'] as $index => $sub_field) {
			if (isset($sub_field['name']) && $sub_field['name'] === 'details') {
				$insert_at = $index + 1;
				break;
			}
		}

		array_splice($field['sub_fields'], $insert_at, 0, $fields_to_add);

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_modification_detail_fields', 22);

if (!function_exists('yoga_add_practice_exercise_additional_modifications_field')) {
	/**
	 * Adds a repeater for any exercise modifications after the legacy first one.
	 */
	function yoga_add_practice_exercise_additional_modifications_field(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		foreach ($field['sub_fields'] as $sub_field) {
			if (($sub_field['name'] ?? '') === 'additional_modifications') {
				return $field;
			}
		}

		$source_fields = array();
		foreach ($field['sub_fields'] as $sub_field) {
			$name = (string) ($sub_field['name'] ?? '');
			if ($name !== '') {
				$source_fields[$name] = $sub_field;
			}
		}

		$field_map = array(
			'modification_name' => array('name' => 'modification_name', 'label' => 'Название варианта', 'key' => 'field_ex_additional_modification_name'),
			'matter_mod'        => array('name' => 'matter', 'label' => 'Содержание', 'key' => 'field_ex_additional_modification_matter'),
			'details_mod'       => array('name' => 'details', 'label' => 'Доп. детали', 'key' => 'field_ex_additional_modification_details'),
			'timing_mod'        => array('name' => 'timing', 'label' => 'Время/циклы', 'key' => 'field_ex_additional_modification_timing'),
			'media_type_mod'    => array('name' => 'media_type', 'label' => 'Тип медиа', 'key' => 'field_ex_additional_modification_media_type'),
			'media_file_mod'    => array('name' => 'media_file', 'label' => 'Медиафайл', 'key' => 'field_ex_additional_modification_media_file'),
			'duration_mod'      => array('name' => 'duration', 'label' => 'Длительность (сек)', 'key' => 'field_ex_additional_modification_duration'),
			'gallery_mod'       => array('name' => 'gallery', 'label' => 'Галерея изображений', 'key' => 'field_ex_additional_modification_gallery'),
			'content_mod'       => array('name' => 'content', 'label' => 'Описание упражнения', 'key' => 'field_ex_additional_modification_content'),
		);

		$variant_fields = array();
		foreach ($field_map as $source_name => $target) {
			if (!isset($source_fields[$source_name])) {
				continue;
			}

			$variant_field = $source_fields[$source_name];
			$variant_field['ID'] = 0;
			$variant_field['key'] = $target['key'];
			$variant_field['name'] = $target['name'];
			$variant_field['_name'] = $target['name'];
			$variant_field['label'] = $target['label'];
			$variant_field['conditional_logic'] = 0;
			$variant_field['parent'] = 0;
			$variant_field['parent_repeater'] = 'field_ex_additional_modifications';
			if ($target['name'] === 'media_file') {
				$variant_field['conditional_logic'] = array(
					array(
						array(
							'field'    => 'field_ex_additional_modification_media_type',
							'operator' => '!=',
							'value'    => 'none',
						),
					),
				);
			}

			if ($target['name'] === 'modification_name') {
				$variant_field['instructions'] = 'Например: Для спины';
				$variant_field['placeholder'] = 'Для спины';
			}

			if (!empty($variant_field['sub_fields']) && is_array($variant_field['sub_fields'])) {
				foreach ($variant_field['sub_fields'] as $child_index => $child_field) {
					$child_field['ID'] = 0;
					$child_field['key'] = $target['key'] . '_' . sanitize_key((string) ($child_field['name'] ?? $child_index));
					$child_field['parent'] = 0;
					$child_field['parent_repeater'] = $target['key'];
					$variant_field['sub_fields'][$child_index] = $child_field;
				}
			}

			$variant_fields[] = $variant_field;
		}

		if ($variant_fields === array()) {
			return $field;
		}

		$additional_modifications = array(
			'ID'                => 0,
			'key'               => 'field_ex_additional_modifications',
			'label'             => 'Дополнительные модификации',
			'name'              => 'additional_modifications',
			'_name'             => 'additional_modifications',
			'prefix'            => 'acf',
			'type'              => 'repeater',
			'instructions'      => 'Добавьте второй и последующие варианты выполнения упражнения. Первый вариант заполняется в полях выше.',
			'required'          => 0,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_ex_has_modifications',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
			'wrapper'           => array('width' => '', 'class' => '', 'id' => ''),
			'layout'            => 'block',
			'min'               => 0,
			'max'               => 0,
			'collapsed'         => 'field_ex_additional_modification_name',
			'button_label'      => 'Добавить модификацию',
			'rows_per_page'     => 20,
			'parent'            => $field['ID'] ?? 0,
			'parent_repeater'   => 'field_exercise_items',
			'sub_fields'        => $variant_fields,
		);

		if (function_exists('acf_get_valid_field')) {
			$additional_modifications = acf_get_valid_field($additional_modifications);
		}

		$first_modification_labels = array(
			'modification_name' => 'Название первой модификации',
			'matter_mod'        => 'Содержание (Первая модификация)',
			'details_mod'       => 'Доп. детали (Первая модификация)',
			'timing_mod'        => 'Время/циклы (Первая модификация)',
			'media_type_mod'    => 'Тип медиа (Первая модификация)',
			'media_file_mod'    => 'Медиафайл (Первая модификация)',
			'duration_mod'      => 'Длительность (сек, Первая модификация)',
			'gallery_mod'       => 'Галерея изображений (Первая модификация)',
			'content_mod'       => 'Описание упражнения (Первая модификация)',
		);
		foreach ($field['sub_fields'] as $index => $sub_field) {
			$name = (string) ($sub_field['name'] ?? '');
			if (isset($first_modification_labels[$name])) {
				$field['sub_fields'][$index]['label'] = $first_modification_labels[$name];
			}
		}

		$field['sub_fields'][] = $additional_modifications;

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_additional_modifications_field', 23);

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

