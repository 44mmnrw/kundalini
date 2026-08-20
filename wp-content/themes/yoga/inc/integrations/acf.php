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

if (!function_exists('yoga_migrate_retired_acf_visual_fields')) {
	/**
	 * Removes retired editor fields whose visuals are now rendered by the theme.
	 *
	 * Field values and media attachments are intentionally preserved so the
	 * migration remains recoverable from the ACF trash.
	 */
	function yoga_migrate_retired_acf_visual_fields() {
		$schema_version = 1;
		if ((int) get_option('yoga_acf_visual_fields_schema_version', 0) >= $schema_version) {
			return;
		}

		global $wpdb;

		$field_keys = array(
			'field_whyme_item_bg',
			'field_reviews_decor',
			'field_questions_quote_decor',
			'field_questions_faq_image',
			'field_subscription_decor',
			'field_subscription_btn_icon',
			'field_contacts_btn_icon',
			'field_faq_form_btn_icon',
			'field_tariff_bg_image',
		);

		$placeholders = implode(',', array_fill(0, count($field_keys), '%s'));
		$field_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID
				 FROM {$wpdb->posts}
				 WHERE post_type = 'acf-field'
				   AND post_status <> 'trash'
				   AND post_name IN ($placeholders)",
				$field_keys
			)
		);

		foreach ($field_ids as $field_id) {
			wp_trash_post((int) $field_id);
		}

		update_option('yoga_acf_visual_fields_schema_version', $schema_version, false);
	}
}

add_action('init', 'yoga_migrate_retired_acf_visual_fields', 5);

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

if (!function_exists('yoga_build_practice_philosophy_content')) {
	/**
	 * Combines the legacy philosophy fields into one editable HTML block.
	 */
	function yoga_build_practice_philosophy_content($before_list, $habits, $conclusion): string {
		$html = '';
		$before_list = trim((string) $before_list);
		$conclusion = trim((string) $conclusion);
		$habit_items = preg_split('/\r\n|\r|\n/', (string) $habits);

		if ($before_list !== '') {
			$html .= wpautop(wp_kses_post($before_list));
		}

		$list_html = '';
		foreach ((array) $habit_items as $habit) {
			$habit = trim((string) $habit);
			if ($habit !== '') {
				$list_html .= '<li>' . esc_html($habit) . '</li>';
			}
		}
		if ($list_html !== '') {
			$html .= '<ul>' . $list_html . '</ul>';
		}

		if ($conclusion !== '') {
			$html .= wpautop(wp_kses_post($conclusion));
		}

		return $html;
	}
}

if (!function_exists('yoga_unify_practice_philosophy_fields')) {
	/**
	 * Replaces the three legacy Anchor 03 fields with one WYSIWYG field.
	 */
	function yoga_unify_practice_philosophy_fields(array $field): array {
		if (empty($field['layouts']) || !is_array($field['layouts'])) {
			return $field;
		}

		$default_content = yoga_build_practice_philosophy_content(
			'Что ослабляет тело? Наши собственные привычки:',
			"Желание обладать\nОграничение и контроль\nГнев и напряжение\nСильная привязанность",
			'Эти состояния создают энергетические блоки, нарушающие нормальный поток жизненной силы, и открывают дверь для болезней - как физических, так и психических. Однако у нас есть выбор, Мы - творцы своего здоровья. Мы сами создаём своё тело, каждой мыслью, поступком и даже каждым приёмом пищи. Поэтому крайне важно очищать организм и регулировать поток энергии.'
		);

		foreach ($field['layouts'] as $layout_index => $layout) {
			if ((string) ($layout['name'] ?? '') !== 'anchor_03' || empty($layout['sub_fields'])) {
				continue;
			}

			$sub_fields = array();
			$content_added = false;
			foreach ($layout['sub_fields'] as $sub_field) {
				$key = (string) ($sub_field['key'] ?? '');
				if ($key === 'field_anchor_03_question' && !$content_added) {
					$content_field = array(
						'ID'                => 0,
						'key'               => 'field_anchor_03_content',
						'label'             => 'Текст философии',
						'name'              => 'philosophy_content',
						'_name'             => 'philosophy_content',
						'prefix'            => 'acf',
						'type'              => 'wysiwyg',
						'instructions'      => 'Вступление, список и заключение редактируются в одном поле.',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array('width' => '', 'class' => '', 'id' => ''),
						'default_value'     => $default_content,
						'tabs'              => 'all',
						'toolbar'           => 'full',
						'media_upload'      => 1,
						'delay'             => 0,
						'parent'            => $field['ID'] ?? 0,
						'parent_layout'     => $layout['key'] ?? 'layout_anchor_03',
					);
					if (function_exists('acf_get_valid_field')) {
						$content_field = acf_get_valid_field($content_field);
					}
					$sub_fields[] = $content_field;
					$content_added = true;
					continue;
				}

				if (in_array($key, array('field_anchor_03_habits_text', 'field_anchor_03_conclusion'), true)) {
					continue;
				}

				$sub_fields[] = $sub_field;
			}

			$field['layouts'][$layout_index]['sub_fields'] = $sub_fields;
		}

		return $field;
	}
}

add_filter('acf/load_field/key=field_practice_sections', 'yoga_unify_practice_philosophy_fields', 30);

if (!function_exists('yoga_migrate_practice_philosophy_content')) {
	/**
	 * Migrates customized legacy values once, while keeping the old meta intact.
	 */
	function yoga_migrate_practice_philosophy_content(): void {
		if (get_option('yoga_practice_philosophy_content_migrated_v1')) {
			return;
		}

		$practice_ids = get_posts(
			array(
				'post_type'              => 'practice',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ($practice_ids as $practice_id) {
			$layouts = get_post_meta($practice_id, 'practice_sections', true);
			if (!is_array($layouts)) {
				continue;
			}

			foreach ($layouts as $index => $layout_name) {
				if ($layout_name !== 'anchor_03') {
					continue;
				}

				$prefix = 'practice_sections_' . (int) $index . '_';
				$content_key = $prefix . 'philosophy_content';
				if (trim((string) get_post_meta($practice_id, $content_key, true)) !== '') {
					continue;
				}

				$content = yoga_build_practice_philosophy_content(
					get_post_meta($practice_id, $prefix . 'before_list_text', true),
					get_post_meta($practice_id, $prefix . 'habits_text', true),
					get_post_meta($practice_id, $prefix . 'conclusion_text', true)
				);
				if ($content !== '') {
					update_post_meta($practice_id, $content_key, $content);
					update_post_meta($practice_id, '_' . $content_key, 'field_anchor_03_content');
				}
			}
		}

		update_option('yoga_practice_philosophy_content_migrated_v1', 1, false);
	}
}

add_action('admin_init', 'yoga_migrate_practice_philosophy_content', 30);

if (!function_exists('yoga_add_practice_exercise_content_format_hint')) {
	/**
	 * Explains how to insert the movable focus callout in exercise descriptions.
	 */
	function yoga_add_practice_exercise_content_format_hint(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		$hint = 'Выделите нужный абзац в визуальном редакторе и выберите формат в списке «Стили»: «Фокус (розовый блок)», «Фиолетовый заголовок» или «Чёрный заголовок».';
		foreach ($field['sub_fields'] as $index => $sub_field) {
			if (in_array((string) ($sub_field['name'] ?? ''), array('content', 'content_mod'), true)) {
				$field['sub_fields'][$index]['instructions'] = $hint;
			}
		}

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_content_format_hint', 19);

if (!function_exists('yoga_sync_practice_section_menu_titles')) {
	/**
	 * Keep the frontend menu title equal to the visible section heading.
	 *
	 * Exercise sections only have section_title and therefore remain untouched.
	 * Updating the single scalar meta avoids rewriting formatted media/repeater data.
	 *
	 * @param int|string $post_id Saved ACF object ID.
	 */
	function yoga_sync_practice_section_menu_titles($post_id): void {
		$post_id = is_numeric($post_id) ? (int) $post_id : 0;
		if ($post_id <= 0 || get_post_type($post_id) !== 'practice') {
			return;
		}

		$layouts = get_post_meta($post_id, 'practice_sections', true);
		$count = is_array($layouts) ? count($layouts) : (int) $layouts;
		for ($index = 0; $index < $count; $index++) {
			$prefix = 'practice_sections_' . $index . '_';
			$source_key = '';
			if (metadata_exists('post', $post_id, $prefix . 'main_title')) {
				$source_key = $prefix . 'main_title';
			} elseif (metadata_exists('post', $post_id, $prefix . 'title')) {
				$source_key = $prefix . 'title';
			}
			if ($source_key === '' || !metadata_exists('post', $post_id, $prefix . 'section_title')) {
				continue;
			}

			update_post_meta(
				$post_id,
				$prefix . 'section_title',
				(string) get_post_meta($post_id, $source_key, true)
			);
		}
	}
}

add_action('acf/save_post', 'yoga_sync_practice_section_menu_titles', 20);

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
				'instructions'      => 'Оставьте пустым, если в модификации этот блок не нужен.',
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
				'instructions'      => 'Оставьте пустым, если в модификации этот блок не нужен.',
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
			if ($target['name'] === 'timing') {
				$variant_field['default_value'] = array();
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

if (!function_exists('yoga_restructure_practice_exercise_modifications')) {
	/**
	 * Replaces the legacy toggle/first-modification controls with clear visual blocks
	 * and one repeater containing every optional modification.
	 */
	function yoga_restructure_practice_exercise_modifications(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		$by_name = array();
		foreach ($field['sub_fields'] as $sub_field) {
			$name = (string) ($sub_field['name'] ?? '');
			if ($name !== '') {
				$by_name[$name] = $sub_field;
			}
		}

		$legacy_repeater = $by_name['additional_modifications'] ?? array();
		$modification_fields = !empty($legacy_repeater['sub_fields']) && is_array($legacy_repeater['sub_fields'])
			? $legacy_repeater['sub_fields']
			: array();

		foreach ($modification_fields as $index => $modification_field) {
			$name = (string) ($modification_field['name'] ?? $index);
			$modification_field['ID'] = 0;
			$modification_field['key'] = 'field_ex_modifications_' . sanitize_key($name);
			$modification_field['parent'] = 0;
			$modification_field['parent_repeater'] = 'field_ex_modifications';
			$modification_field['conditional_logic'] = 0;
			if ($name === 'matter') {
				$modification_field['wrapper'] = is_array($modification_field['wrapper'] ?? null) ? $modification_field['wrapper'] : array();
				$modification_field['wrapper']['class'] = trim((string) ($modification_field['wrapper']['class'] ?? '') . ' acf-hidden yoga-legacy-exercise-field');
			}

			if ($name === 'modification_name') {
				$modification_field['label'] = 'Название модификации';
				$modification_field['instructions'] = 'Так будет называться вкладка на странице. Например: «Для спины» или «Облегчённый вариант».';
				$modification_field['placeholder'] = 'Например: Для спины';
			}

			if (!empty($modification_field['sub_fields']) && is_array($modification_field['sub_fields'])) {
				foreach ($modification_field['sub_fields'] as $child_index => $child_field) {
					$child_name = (string) ($child_field['name'] ?? $child_index);
					$child_field['ID'] = 0;
					$child_field['key'] = $modification_field['key'] . '_' . sanitize_key($child_name);
					$child_field['parent'] = 0;
					$child_field['parent_repeater'] = $modification_field['key'];
					$modification_field['sub_fields'][$child_index] = $child_field;
				}
			}

			$modification_fields[$index] = $modification_field;
		}

		$video_source_field = static function (string $key, string $parent_repeater, string $media_type_key): array {
			return array(
				'ID'                => 0,
				'key'               => $key,
				'label'             => 'Источник видео',
				'name'              => 'video_source',
				'_name'             => 'video_source',
				'prefix'            => 'acf',
				'type'              => 'select',
				'instructions'      => 'Выберите один источник видео. Ниже появится только нужное поле.',
				'required'          => 0,
				'conditional_logic' => array(
					array(
						array('field' => $media_type_key, 'operator' => '==', 'value' => 'video'),
					),
				),
				'wrapper'           => array('width' => '', 'class' => 'yoga-video-source-field yoga-video-source-field--select', 'id' => ''),
				'choices'           => array(
					'file'      => 'Медиафайл',
					'kinescope' => 'Kinescope',
					'youtube'   => 'YouTube',
				),
				'default_value'     => 'file',
				'allow_null'        => 0,
				'multiple'          => 0,
				'ui'                => 1,
				'ajax'              => 0,
				'placeholder'       => '',
				'return_format'     => 'value',
				'parent'            => 0,
				'parent_repeater'   => $parent_repeater,
			);
		};

		$kinescope_url_field = static function (string $key, string $parent_repeater, string $media_type_key, string $video_source_key): array {
			return array(
				'ID'                => 0,
				'key'               => $key,
				'label'             => 'Ссылка Kinescope',
				'name'              => 'kinescope_url',
				'_name'             => 'kinescope_url',
				'prefix'            => 'acf',
				'type'              => 'url',
				'instructions'      => 'Вставьте HTTPS-ссылку на видео Kinescope, например https://kinescope.io/abc123.',
				'required'          => 0,
				'conditional_logic' => array(
					array(
						array('field' => $media_type_key, 'operator' => '==', 'value' => 'video'),
						array('field' => $video_source_key, 'operator' => '==', 'value' => 'kinescope'),
					),
				),
				'wrapper'           => array('width' => '', 'class' => 'yoga-video-source-field yoga-video-source-field--url', 'id' => ''),
				'default_value'     => '',
				'placeholder'       => 'https://kinescope.io/...',
				'parent'            => 0,
				'parent_repeater'   => $parent_repeater,
			);
		};

		$youtube_url_field = static function (string $key, string $parent_repeater, string $media_type_key, string $video_source_key): array {
			return array(
				'ID'                => 0,
				'key'               => $key,
				'label'             => 'Ссылка YouTube',
				'name'              => 'youtube_url',
				'_name'             => 'youtube_url',
				'prefix'            => 'acf',
				'type'              => 'url',
				'instructions'      => 'Вставьте ссылку на видео YouTube: youtube.com/watch, youtu.be, Shorts или embed.',
				'required'          => 0,
				'conditional_logic' => array(
					array(
						array('field' => $media_type_key, 'operator' => '==', 'value' => 'video'),
						array('field' => $video_source_key, 'operator' => '==', 'value' => 'youtube'),
					),
				),
				'wrapper'           => array('width' => '', 'class' => 'yoga-video-source-field yoga-video-source-field--url', 'id' => ''),
				'default_value'     => '',
				'placeholder'       => 'https://www.youtube.com/watch?v=...',
				'parent'            => 0,
				'parent_repeater'   => $parent_repeater,
			);
		};

		$modification_video_source = $video_source_field(
			'field_ex_modifications_video_source',
			'field_ex_modifications',
			'field_ex_modifications_media_type'
		);
		$modification_kinescope_url = $kinescope_url_field(
			'field_ex_modifications_kinescope_url',
			'field_ex_modifications',
			'field_ex_modifications_media_type',
			'field_ex_modifications_video_source'
		);
		$modification_youtube_url = $youtube_url_field(
			'field_ex_modifications_youtube_url',
			'field_ex_modifications',
			'field_ex_modifications_media_type',
			'field_ex_modifications_video_source'
		);

		$media_file_index = null;
		foreach ($modification_fields as $index => $modification_field) {
			if (($modification_field['name'] ?? '') === 'media_file') {
				$media_file_index = $index;
				break;
			}
		}
		if ($media_file_index !== null) {
			$modification_fields[$media_file_index]['conditional_logic'] = array(
				array(
					array('field' => 'field_ex_modifications_media_type', 'operator' => '==', 'value' => 'audio'),
				),
				array(
					array('field' => 'field_ex_modifications_media_type', 'operator' => '==', 'value' => 'video'),
					array('field' => 'field_ex_modifications_video_source', 'operator' => '==', 'value' => 'file'),
				),
			);
			array_splice($modification_fields, $media_file_index, 0, array($modification_video_source, $modification_kinescope_url, $modification_youtube_url));
		} else {
			$modification_fields[] = $modification_video_source;
			$modification_fields[] = $modification_kinescope_url;
			$modification_fields[] = $modification_youtube_url;
		}

		$main_video_source = $video_source_field('field_ex_video_source', 'field_exercise_items', 'field_ex_media_type');
		$main_kinescope_url = $kinescope_url_field('field_ex_kinescope_url', 'field_exercise_items', 'field_ex_media_type', 'field_ex_video_source');
		$main_youtube_url = $youtube_url_field('field_ex_youtube_url', 'field_exercise_items', 'field_ex_media_type', 'field_ex_video_source');
		$main_video_source['parent'] = $field['ID'] ?? 0;
		$main_kinescope_url['parent'] = $field['ID'] ?? 0;
		$main_youtube_url['parent'] = $field['ID'] ?? 0;
		if (isset($by_name['media_file'])) {
			$by_name['media_file']['conditional_logic'] = array(
				array(
					array('field' => 'field_ex_media_type', 'operator' => '==', 'value' => 'audio'),
				),
				array(
					array('field' => 'field_ex_media_type', 'operator' => '==', 'value' => 'video'),
					array('field' => 'field_ex_video_source', 'operator' => '==', 'value' => 'file'),
				),
			);
		}

		$accordion = static function (string $key, string $label, bool $open = false): array {
			return array(
				'ID'           => 0,
				'key'          => $key,
				'label'        => $label,
				'name'         => '',
				'_name'        => '',
				'type'         => 'accordion',
				'instructions' => '',
				'required'     => 0,
				'wrapper'      => array('width' => '', 'class' => 'yoga-exercise-admin-section', 'id' => ''),
				'open'         => $open ? 1 : 0,
				'multi_expand' => 1,
				'endpoint'     => 0,
				'parent'       => 0,
				'parent_repeater' => 'field_exercise_items',
			);
		};

		$modifications = array(
			'ID'                => 0,
			'key'               => 'field_ex_modifications',
			'label'             => 'Модификации упражнения',
			'name'              => 'modifications',
			'_name'             => 'modifications',
			'prefix'            => 'acf',
			'type'              => 'repeater',
			'instructions'      => 'Добавляйте столько вариантов выполнения, сколько нужно. Каждая строка станет отдельной вкладкой рядом с «Основной модификацией».',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array('width' => '', 'class' => 'yoga-exercise-modifications', 'id' => ''),
			'layout'            => 'block',
			'min'               => 0,
			'max'               => 0,
			'collapsed'         => 'field_ex_modifications_modification_name',
			'button_label'      => 'Добавить модификацию',
			'rows_per_page'     => 20,
			'parent'            => $field['ID'] ?? 0,
			'parent_repeater'   => 'field_exercise_items',
			'sub_fields'        => $modification_fields,
		);
		$main_modification_name = array(
			'ID'                => 0,
			'key'               => 'field_ex_main_modification_name',
			'label'             => 'Название выполнения',
			'name'              => 'main_modification_name',
			'_name'             => 'main_modification_name',
			'prefix'            => 'acf',
			'type'              => 'text',
			'instructions'      => 'Название первой вкладки упражнения на странице. Например: «Выполнение» или «Классический вариант».',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array('width' => '', 'class' => '', 'id' => ''),
			'default_value'     => 'Выполнение',
			'placeholder'       => 'Выполнение',
			'parent'            => $field['ID'] ?? 0,
			'parent_repeater'   => 'field_exercise_items',
		);

		$append_named = static function (array &$target, array $names) use ($by_name): void {
			foreach ($names as $name) {
				if (isset($by_name[$name])) {
					$target[] = $by_name[$name];
				}
			}
		};

		$restructured = array($accordion('field_ex_admin_common', 'Общие данные упражнения', true));
		$append_named($restructured, array('title', 'subtitle'));
		$restructured[] = $accordion('field_ex_admin_main_modification', 'Выполнение', true);
		$restructured[] = $main_modification_name;
		$append_named($restructured, array('details', 'timing', 'media_type'));
		$restructured[] = $main_video_source;
		$restructured[] = $main_kinescope_url;
		$restructured[] = $main_youtube_url;
		$append_named($restructured, array('media_file', 'duration', 'gallery', 'content'));
		$main_labels = array(
			'timing'     => 'Время/циклы',
			'media_type' => 'Тип медиа',
			'media_file' => 'Медиафайл',
			'duration'   => 'Длительность (сек)',
			'gallery'    => 'Галерея изображений',
			'content'    => 'Описание упражнения',
		);
		foreach ($restructured as $index => $sub_field) {
			$name = (string) ($sub_field['name'] ?? '');
			if (isset($main_labels[$name])) {
				$restructured[$index]['label'] = $main_labels[$name];
			}
		}
		$modifications_accordion = $accordion('field_ex_admin_modifications', 'Дополнительные модификации', true);
		$modifications_accordion['wrapper']['class'] .= ' yoga-flat-modifications-accordion';
		$restructured[] = $modifications_accordion;
		$restructured[] = $modifications;
		$restructured[] = $accordion('field_ex_admin_player_settings', 'Общие настройки плеера');
		$append_named($restructured, array('allow_fullscreen', 'restrict_scrub', 'auto_play', 'signal_v_koncze'));

		// Keep old fields registered but invisible so existing values can be migrated safely.
		$legacy_names = array(
			'has_modifications', 'execution_name', 'modification_name', 'matter', 'matter_mod', 'details_mod',
			'timing_mod', 'media_type_mod', 'media_file_mod', 'duration_mod', 'gallery_mod',
			'content_mod', 'additional_modifications',
		);
		foreach ($legacy_names as $legacy_name) {
			if (!isset($by_name[$legacy_name])) {
				continue;
			}

			$legacy_field = $by_name[$legacy_name];
			$legacy_field['wrapper'] = is_array($legacy_field['wrapper'] ?? null) ? $legacy_field['wrapper'] : array();
			$legacy_field['wrapper']['class'] = trim((string) ($legacy_field['wrapper']['class'] ?? '') . ' acf-hidden yoga-legacy-exercise-field');
			$restructured[] = $legacy_field;
		}

		if (function_exists('acf_get_valid_field')) {
			foreach ($restructured as $index => $sub_field) {
				$restructured[$index] = acf_get_valid_field($sub_field);
			}
		}

		$field['sub_fields'] = $restructured;

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_restructure_practice_exercise_modifications', 24);

if (!function_exists('yoga_validate_kinescope_video_url')) {
	function yoga_validate_kinescope_video_url($valid, $value) {
		if ($valid !== true || trim((string) $value) === '') {
			return $valid;
		}

		$scheme = strtolower((string) wp_parse_url((string) $value, PHP_URL_SCHEME));
		$host = strtolower((string) wp_parse_url((string) $value, PHP_URL_HOST));
		if ($scheme !== 'https' || ($host !== 'kinescope.io' && !str_ends_with($host, '.kinescope.io'))) {
			return 'Укажите корректную HTTPS-ссылку на видео Kinescope.';
		}

		return $valid;
	}
}

add_filter('acf/validate_value/key=field_ex_kinescope_url', 'yoga_validate_kinescope_video_url', 10, 2);
add_filter('acf/validate_value/key=field_ex_modifications_kinescope_url', 'yoga_validate_kinescope_video_url', 10, 2);

if (!function_exists('yoga_get_youtube_video_id')) {
	function yoga_get_youtube_video_id($url): string {
		$url = trim((string) $url);
		if ($url === '' || strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)) !== 'https') {
			return '';
		}

		$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
		$host = preg_replace('/^(?:www\.|m\.)/', '', $host);
		$path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
		$video_id = '';

		if ($host === 'youtu.be') {
			$video_id = explode('/', $path)[0] ?? '';
		} elseif (in_array($host, array('youtube.com', 'youtube-nocookie.com'), true)) {
			$query = array();
			parse_str((string) wp_parse_url($url, PHP_URL_QUERY), $query);
			if (!empty($query['v'])) {
				$video_id = (string) $query['v'];
			} elseif (preg_match('~^(?:embed|shorts|live|v)/([^/]+)~', $path, $matches)) {
				$video_id = (string) $matches[1];
			}
		}

		return preg_match('/^[A-Za-z0-9_-]{11}$/', $video_id) ? $video_id : '';
	}
}

if (!function_exists('yoga_validate_youtube_video_url')) {
	function yoga_validate_youtube_video_url($valid, $value) {
		if ($valid !== true || trim((string) $value) === '') {
			return $valid;
		}

		return yoga_get_youtube_video_id($value) !== ''
			? $valid
			: 'Укажите корректную HTTPS-ссылку на видео YouTube.';
	}
}

add_filter('acf/validate_value/key=field_ex_youtube_url', 'yoga_validate_youtube_video_url', 10, 2);
add_filter('acf/validate_value/key=field_ex_modifications_youtube_url', 'yoga_validate_youtube_video_url', 10, 2);

if (!function_exists('yoga_enable_practice_exercise_end_signal_by_default')) {
	/**
	 * Enables the timer end signal for newly added exercises. An explicitly saved
	 * false value is preserved, so editors can still disable it per exercise.
	 */
	function yoga_enable_practice_exercise_end_signal_by_default(array $field): array {
		$field['default_value'] = 1;

		return $field;
	}
}

add_filter('acf/load_field/key=field_68aebe989eeb1', 'yoga_enable_practice_exercise_end_signal_by_default');

if (!function_exists('yoga_add_practice_exercise_admin_hints')) {
	/**
	 * Explains in plain language where every exercise field appears on the frontend.
	 */
	function yoga_add_practice_exercise_admin_hints(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		$instructions_by_key = array(
			'field_ex_has_modifications' => 'Включите, если у упражнения есть другие варианты выполнения. На странице появятся вкладки для переключения между основной версией и модификациями.',
			'field_ex_execution_name' => 'Название вкладки основной версии упражнения. Видно только когда включены модификации. Если оставить пустым, будет «Выполнение».',
			'field_ex_modification_name' => 'Название вкладки первой модификации. Например: «Для спины» или «Облегчённый вариант».',
			'field_ex_title' => 'Главный чёрный заголовок упражнения на странице. Он общий для основной версии и всех модификаций.',
			'field_ex_subtitle' => 'Фиолетовый заголовок сразу под главным заголовком. Он общий для основной версии и всех модификаций.',
			'field_ex_allow_fullscreen' => 'Служебная настройка полноэкранного режима встроенного аудио- или видеоплеера.',
			'field_ex_restrict_scrub' => 'Служебная настройка перемотки аудио или видео для пользователей без подписки.',
			'field_ex_auto_play' => 'Служебная настройка автоматического запуска медиа вместе с таймером.',
			'field_68aebe989eeb1' => 'Если включено, после завершения таймера упражнения прозвучит сигнал, заданный в общих настройках темы.',
		);

		$instructions_by_name = array(
			'modification_name' => 'Название этой вкладки модификации на странице. Например: «Для спины» или «Облегчённый вариант».',
			'matter' => 'Короткие характеристики сразу под фиолетовым подзаголовком. В каждой строке «Заголовок» выводится жирным до двоеточия, «Описание» — обычным текстом.',
			'matter_mod' => 'Короткие характеристики первой модификации сразу под фиолетовым подзаголовком. Заполняйте только то, что должно показываться в этой вкладке.',
			'details' => 'Текст в блоке «Доп. детали» под подзаголовком. Каждый пункт пишите с новой строки в виде «Название: описание» — часть до двоеточия будет жирной.',
			'details_mod' => 'Дополнительные детали первой модификации. Каждый пункт пишите с новой строки в виде «Название: описание» — часть до двоеточия будет жирной. Пустое поле на странице не выводится.',
			'timing' => 'Отмеченные варианты времени или циклов показываются под характеристиками упражнения и доступны для запуска таймера.',
			'timing_mod' => 'Время или циклы только для первой модификации. Отмеченные варианты показываются в её вкладке и доступны для таймера.',
			'media_type' => 'Выберите, какой плеер показать в этой версии упражнения: аудио, видео или без медиа.',
			'media_type_mod' => 'Выберите тип плеера для первой модификации: аудио, видео или без медиа.',
			'media_file' => 'Файл, который будет воспроизводиться в плеере выбранной версии упражнения.',
			'media_file_mod' => 'Аудио- или видеофайл для первой модификации.',
			'duration' => 'Продолжительность медиа в секундах. Это техническое значение для работы плеера.',
			'duration_mod' => 'Продолжительность медиа первой модификации в секундах.',
			'gallery' => 'Изображения этой версии упражнения. На странице они показываются в галерее под информацией об упражнении.',
			'gallery_mod' => 'Изображения первой модификации. Они показываются только в её вкладке.',
			'content' => 'Большое текстовое описание под плеером и галереей. Для розового блока или специальных заголовков выделите абзац и выберите нужный вариант в списке «Стили».',
			'content_mod' => 'Большое текстовое описание первой модификации под её плеером и галереей. Для розового блока или специальных заголовков используйте список «Стили».',
			'additional_modifications' => 'Здесь добавляются вторая и следующие модификации. Каждая строка создаёт отдельную вкладку упражнения; первая модификация заполняется в полях выше.',
		);

		$decorate_fields = static function (array $fields, string $parent_name = '') use (&$decorate_fields, $instructions_by_key, $instructions_by_name): array {
			foreach ($fields as $index => $sub_field) {
				$key = (string) ($sub_field['key'] ?? '');
				$name = (string) ($sub_field['name'] ?? '');

				if (isset($instructions_by_key[$key])) {
					$sub_field['instructions'] = $instructions_by_key[$key];
				} elseif (in_array($parent_name, array('matter', 'matter_mod'), true) && $name === 'title') {
					$sub_field['instructions'] = 'Жирная подпись до двоеточия. Например: «Асана» или «Мантра».';
				} elseif (in_array($parent_name, array('matter', 'matter_mod'), true) && $name === 'description') {
					$sub_field['instructions'] = 'Обычный текст после двоеточия.';
				} elseif (isset($instructions_by_name[$name])) {
					$sub_field['instructions'] = $instructions_by_name[$name];
				}

				if (!empty($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
					$sub_field['sub_fields'] = $decorate_fields($sub_field['sub_fields'], $name);
				}

				$fields[$index] = $sub_field;
			}

			return $fields;
		};

		$field['sub_fields'] = $decorate_fields($field['sub_fields']);

		return $field;
	}
}

add_filter('acf/load_field/key=field_exercise_items', 'yoga_add_practice_exercise_admin_hints', 26);

if (!function_exists('yoga_set_practice_steps_admin_column_widths')) {
	/**
	 * Uses block rows so the theme-styled practice editor can present each step
	 * as a card without changing the stored repeater value.
	 */
	function yoga_set_practice_steps_admin_column_widths(array $field): array {
		if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
			return $field;
		}

		$field['layout'] = 'block';

		foreach ($field['sub_fields'] as $index => $sub_field) {
			$field['sub_fields'][$index]['wrapper'] = is_array($sub_field['wrapper'] ?? null)
				? $sub_field['wrapper']
				: array();
			$field['sub_fields'][$index]['wrapper']['width'] = '';
		}

		return $field;
	}
}

add_filter('acf/load_field/key=field_anchor_05_steps', 'yoga_set_practice_steps_admin_column_widths', 20);

if (!function_exists('yoga_migrate_practice_exercise_modifications')) {
	/**
	 * Copies legacy first/additional modification fields into the unified repeater.
	 * Old metadata is intentionally retained as a rollback-safe backup.
	 */
	function yoga_migrate_practice_exercise_modifications(): void {
		if (!is_admin() || !function_exists('get_field') || !function_exists('update_field')) {
			return;
		}

		$schema_version = 6;
		$previous_schema_version = (int) get_option('yoga_exercise_modifications_schema_version', 0);
		if ($previous_schema_version >= $schema_version) {
			return;
		}

		$legacy_map = array(
			'field_ex_modification_name' => 'field_ex_modifications_modification_name',
			'field_ex_matter_mod'        => 'field_ex_modifications_matter',
			'field_ex_details_mod'       => 'field_ex_modifications_details',
			'field_ex_timing_mod'        => 'field_ex_modifications_timing',
			'field_ex_media_type_mod'    => 'field_ex_modifications_media_type',
			'field_ex_media_file_mod'    => 'field_ex_modifications_media_file',
			'field_ex_duration_mod'      => 'field_ex_modifications_duration',
			'field_ex_gallery_mod'       => 'field_ex_modifications_gallery',
			'field_ex_content_mod'       => 'field_ex_modifications_content',
		);
		$additional_map = array(
			'field_ex_additional_modification_name'       => 'field_ex_modifications_modification_name',
			'field_ex_additional_modification_matter'     => 'field_ex_modifications_matter',
			'field_ex_additional_modification_details'    => 'field_ex_modifications_details',
			'field_ex_additional_modification_timing'     => 'field_ex_modifications_timing',
			'field_ex_additional_modification_media_type' => 'field_ex_modifications_media_type',
			'field_ex_additional_modification_media_file' => 'field_ex_modifications_media_file',
			'field_ex_additional_modification_duration'   => 'field_ex_modifications_duration',
			'field_ex_additional_modification_gallery'    => 'field_ex_modifications_gallery',
			'field_ex_additional_modification_content'    => 'field_ex_modifications_content',
		);

		$row_has_content = static function (array $row): bool {
			foreach ($row as $key => $value) {
				if (str_ends_with((string) $key, '_media_type') && in_array($value, array('', 'none', null), true)) {
					continue;
				}
				if (is_array($value) && $value === array()) {
					continue;
				}
				if (!in_array($value, array('', false, null), true)) {
					return true;
				}
			}

			return false;
		};

		$map_row = static function (array $source, array $map): array {
			$row = array();
			foreach ($map as $source_key => $target_key) {
				$row[$target_key] = $source[$source_key] ?? '';
			}

			return $row;
		};

		$matter_to_text = static function ($rows, array $title_keys, array $description_keys): string {
			if (!is_array($rows)) {
				return '';
			}

			$lines = array();
			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}

				$title = '';
				foreach ($title_keys as $key) {
					if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
						$title = trim((string) $row[$key]);
						break;
					}
				}

				$description = '';
				foreach ($description_keys as $key) {
					if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
						$description = trim((string) $row[$key]);
						break;
					}
				}

				if ($title !== '') {
					$lines[] = $title . ':' . ($description !== '' ? ' ' . $description : '');
				} elseif ($description !== '') {
					$lines[] = $description;
				}
			}

			return implode("\n", $lines);
		};

		$prepend_details = static function (array &$row, string $details_key, string $converted_text): bool {
			if ($converted_text === '') {
				return false;
			}

			$existing = trim((string) ($row[$details_key] ?? ''));
			$row[$details_key] = $converted_text . ($existing !== '' ? "\n" . $existing : '');

			return true;
		};

		$migrate_node = static function (&$node) use (&$migrate_node, $legacy_map, $additional_map, $row_has_content, $map_row, $matter_to_text, $prepend_details, $previous_schema_version): bool {
			if (!is_array($node)) {
				return false;
			}

			$changed = false;
			$is_exercise = array_key_exists('field_ex_title', $node) || array_key_exists('field_ex_has_modifications', $node);
			if ($is_exercise && empty($node['field_ex_main_modification_name'])) {
				$legacy_main_name = trim((string) ($node['field_ex_execution_name'] ?? ''));
				$node['field_ex_main_modification_name'] = $legacy_main_name !== '' && $legacy_main_name !== '__unified__'
					? $legacy_main_name
					: 'Выполнение';
				$changed = true;
			}
			if ($is_exercise && ($node['field_ex_main_modification_name'] ?? '') === 'Основная модификация') {
				$node['field_ex_main_modification_name'] = 'Выполнение';
				$changed = true;
			}
			if ($is_exercise && ($node['field_ex_execution_name'] ?? '') !== '__unified__') {
				// This retired field doubles as a migration marker, preventing deleted
				// unified rows from being resurrected from the legacy backup.
				$node['field_ex_execution_name'] = '__unified__';
				$changed = true;
			}
			if (
				$is_exercise
				&& $previous_schema_version === 1
				&& empty($node['field_ex_has_modifications'])
				&& !empty($node['field_ex_modifications'])
			) {
				$node['field_ex_modifications'] = false;
				$changed = true;
			}

			if ($is_exercise && empty($node['field_ex_modifications'])) {
				$rows = array();
				$legacy_row = $map_row($node, $legacy_map);
				if (!empty($node['field_ex_has_modifications'])) {
					$rows[] = $legacy_row;
				}

				$additional_rows = $node['field_ex_additional_modifications'] ?? array();
				if (is_array($additional_rows)) {
					foreach ($additional_rows as $additional_row) {
						if (!is_array($additional_row)) {
							continue;
						}

						$mapped_row = $map_row($additional_row, $additional_map);
						if ($row_has_content($mapped_row)) {
							$rows[] = $mapped_row;
						}
					}
				}

				if ($rows !== array()) {
					$node['field_ex_modifications'] = $rows;
					$changed = true;
				}
			}

			if ($is_exercise && $previous_schema_version < 4) {
				$main_matter = $matter_to_text(
					$node['field_ex_asana'] ?? array(),
					array('field_68aeb9b8f7b3a', 'title'),
					array('field_68aeb9c9f7b3b', 'description')
				);
				if ($prepend_details($node, 'field_ex_details', $main_matter)) {
					$changed = true;
				}

				if (!empty($node['field_ex_modifications']) && is_array($node['field_ex_modifications'])) {
					foreach ($node['field_ex_modifications'] as &$modification_row) {
						if (!is_array($modification_row)) {
							continue;
						}

						$modification_matter = $matter_to_text(
							$modification_row['field_ex_modifications_matter'] ?? array(),
							array('field_ex_modifications_matter_title', 'title'),
							array('field_ex_modifications_matter_description', 'description')
						);
						if ($prepend_details($modification_row, 'field_ex_modifications_details', $modification_matter)) {
							$changed = true;
						}
					}
					unset($modification_row);
				}
			}

			foreach ($node as &$child) {
				if (is_array($child) && $migrate_node($child)) {
					$changed = true;
				}
			}
			unset($child);

			return $changed;
		};

		$practice_ids = get_posts(array(
			'post_type'      => 'practice',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		));

		foreach ($practice_ids as $practice_id) {
			$sections = get_field('field_practice_sections', $practice_id, false);
			if (!is_array($sections) || !$migrate_node($sections)) {
				continue;
			}

			update_field('field_practice_sections', $sections, $practice_id);
		}

		update_option('yoga_exercise_modifications_schema_version', $schema_version, false);
	}
}

add_action('admin_init', 'yoga_migrate_practice_exercise_modifications', 20);

if (!function_exists('yoga_delay_acf_wysiwyg_editors')) {
	function yoga_delay_acf_wysiwyg_editors(array $field): array {
		if (!is_admin()) {
			return $field;
		}

		$field['delay'] = 1;

		if (function_exists('yoga_is_modern_practice_editor') && yoga_is_modern_practice_editor()) {
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

