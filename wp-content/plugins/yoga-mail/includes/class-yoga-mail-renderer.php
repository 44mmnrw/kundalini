<?php

if (!defined('ABSPATH')) {
	exit;
}

final class Yoga_Mail_Renderer {
	private $registry;

	public function __construct(Yoga_Mail_Registry $registry) {
		$this->registry = $registry;
	}

	/**
	 * @return array|WP_Error subject, html, text, template_id.
	 */
	public function render(string $template_id, array $data = array(), bool $preview = false) {
		$definition = $this->registry->get($template_id);
		$values = $this->registry->values($template_id);
		if (!$definition || !$values) {
			return new WP_Error('yoga_mail_unknown_template', 'Неизвестный шаблон письма: ' . $template_id);
		}

		$data = wp_parse_args($data, array(
			'site_name' => wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
			'site_url'  => home_url('/'),
		));
		if ($preview) {
			$data = wp_parse_args($data, $this->registry->examples($template_id));
		}
		$is_staff_question = Yoga_Mail_Admin_Request::is_template($template_id);
		if (!$is_staff_question) {
			unset($data['question_reply_url'], $data['request_reply_link']);
		}

		$subject = $this->merge((string) $values['subject'], $definition, $data, 'text', $preview);
		$preheader = $this->merge((string) $values['preheader'], $definition, $data, 'text', $preview);
		$heading = $this->merge((string) $values['heading'], $definition, $data, 'text', $preview);
		$body = $this->merge((string) $values['body'], $definition, $data, 'html', $preview);
		$omit_cta = $template_id === 'question-answer' && !empty($data['omit_cta']);
		$cta_label = $omit_cta ? '' : $this->merge((string) $values['cta_label'], $definition, $data, 'text', $preview);
		$cta_url = $omit_cta ? '' : $this->merge((string) $values['cta_url'], $definition, $data, 'url', $preview);
		$footer_note = $this->merge((string) $values['footer_note'], $definition, $data, 'text', $preview);
		foreach (array($subject, $preheader, $heading, $body, $cta_label, $cta_url, $footer_note) as $result) {
			if (is_wp_error($result)) {
				return $result;
			}
		}
		// Staff event links always target the saved question, even with an older saved template.
		if ($is_staff_question && !empty($data['question_reply_url'])) {
			$cta_label = 'Ответить из админки';
			$cta_url = esc_url_raw((string) $data['question_reply_url']);
		}
		if ($template_id === 'wp-email-changed') {
			$body = $this->format_email_change_addresses((string) $body, $data);
		}
		if (in_array($template_id, array('payment-success-receipt', 'renewal-success'), true) && !empty($data['fiscal_receipt_notice'])) {
			$body .= '<p style="margin:20px 0 0;line-height:1.5;text-align:center;">' . esc_html((string) $data['fiscal_receipt_notice']) . '</p>';
		}

		$body_html = $this->inline_content_styles((string) $body, $is_staff_question ? 14 : 16);
		$settings = $this->registry->settings();
		$is_admin_notification = $definition['group'] === 'Административные';
		if ($is_admin_notification) {
			$settings['footer_text'] = 'Это оповестительное письмо для администратора онлайн-платформы Кундалини Класс.';
		}
		$template_path = YOGA_MAIL_PATH . 'templates/layout/html.php';
		ob_start();
		include $template_path;
		$html = (string) ob_get_clean();
		if ($is_staff_question) {
			$html = str_replace('font-size:16px', 'font-size:14px', $html);
			$html = self::non_clickable_staff_emails($html);
		}
		$html = $this->responsive_html($html, $is_staff_question);

		$plain_body = $this->plain_from_html((string) $body);
		if ($template_id === 'wp-email-changed') {
			$plain_body = str_replace("\u{200B}", '', $plain_body);
		}
		$text_parts = array_filter(array((string) $heading, $plain_body));
		if ((string) $cta_label !== '' && (string) $cta_url !== '') {
			$text_parts[] = (string) $cta_label . ': ' . (string) $cta_url;
		}
		if ((string) $footer_note !== '') {
			$text_parts[] = (string) $footer_note;
		}
		$privacy_url = function_exists('yoga_get_legal_document_url')
			? (string) yoga_get_legal_document_url('privacy_policy', '')
			: '';
		if ($privacy_url === '' && function_exists('get_privacy_policy_url')) {
			$privacy_url = (string) get_privacy_policy_url();
		}
		if ($privacy_url === '') {
			$privacy_url = home_url('/privacy-policy/');
		}
		$copyright_year = function_exists('wp_date') ? wp_date('Y') : date('Y');
		if ($is_admin_notification) {
			$text_parts[] = $settings['footer_text'];
		}
		$text_parts[] = 'Поддержка — support@platform.kundalini-class.ru';
		$text_parts[] = 'Политика конфиденциальности: ' . $privacy_url;
		$text_parts[] = $copyright_year . ' © Кундалини Класс. Все права защищены.';

		return array(
			'template_id' => $template_id,
			'subject'     => wp_strip_all_tags((string) $subject),
			'html'        => $html,
			'text'        => trim(implode("\n\n", $text_parts)),
		);
	}

	/** Use the same invisible separator as account emails to discourage automatic email links. */
	private static function non_clickable_staff_emails(string $html): string {
		$html = preg_replace('/<a\b(?=[^>]*\bhref\s*=\s*(["\'])mailto:)[^>]*>(.*?)<\/a>/is', '$2', $html);
		return preg_replace_callback('/(?<=>)[^<]+(?=<)/u', static function (array $matches): string {
			return str_replace('@', '&#8203;@', $matches[0]);
		}, $html);
	}

	/** Split addresses across table cells so mail clients cannot turn them into links. */
	private function format_email_change_addresses(string $body, array $data): string {
		$addresses = array(
			'old_email' => '#1f1f1f',
			'new_email' => '#9153e1',
		);
		$valid_addresses = array();
		foreach ($addresses as $key => $color) {
			$address = trim((string) ($data[$key] ?? ''));
			if (is_email($address)) {
				$valid_addresses[$address] = $color;
			}
		}
		if (!$valid_addresses) {
			return $body;
		}
		uksort($valid_addresses, static function (string $a, string $b): int {
			return strlen($b) <=> strlen($a);
		});

		// The default and saved templates place each address in its own paragraph.
		$body = preg_replace_callback('/<p\b[^>]*>.*?<\/p>/is', static function (array $matches) use ($valid_addresses): string {
			$label = trim(html_entity_decode(wp_strip_all_tags($matches[0]), ENT_QUOTES, 'UTF-8'));
			foreach ($valid_addresses as $address => $color) {
				if (strcasecmp($label, $address) === 0) {
					return self::email_address_cells($address, $color);
				}
			}
			return $matches[0];
		}, $body);

		// A customized template may put a mailto link around the address elsewhere.
		$body = preg_replace_callback('/<a\b[^>]*>(.*?)<\/a>/is', static function (array $matches) use ($valid_addresses): string {
			$label = trim(html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES, 'UTF-8'));
			foreach ($valid_addresses as $address => $color) {
				if (strcasecmp($label, $address) === 0) {
					return esc_html($address);
				}
			}
			return $matches[0];
		}, $body);

		return preg_replace_callback('/(?<=>)[^<]+(?=<)/u', static function (array $matches) use ($valid_addresses): string {
			$text = $matches[0];
			foreach ($valid_addresses as $address => $color) {
				$escaped = esc_html($address);
				$display = str_replace('@', '&#8203;@', $escaped);
				$text = str_replace($escaped, '<span style="color:' . $color . ';text-decoration:none;">' . $display . '</span>', $text);
			}
			return $text;
		}, $body);
	}

	private static function email_address_cells(string $address, string $color): string {
		list($local, $domain) = explode('@', $address, 2);
		$fragments = array($local, '@');
		$domain_parts = explode('.', $domain);
		foreach ($domain_parts as $index => $part) {
			if ($index > 0) {
				$fragments[] = '.';
			}
			$fragments[] = $part;
		}
		$style = 'padding:0;font-family:Mulish,Helvetica,Arial,sans-serif;font-size:16px;line-height:1;font-weight:700;color:' . $color . ';white-space:nowrap;text-decoration:none;';
		$cells = '';
		foreach ($fragments as $fragment) {
			$cells .= '<td valign="middle" style="' . $style . '">' . esc_html($fragment) . '</td>';
		}
		return '<table class="yoga-mail-address" role="presentation" cellpadding="0" cellspacing="0" border="0" align="right" style="margin:0 0 0 auto;border-collapse:collapse;"><tr>' . $cells . '</tr></table>';
	}

	/**
	 * Render one editable field without the common layout (WooCommerce integration).
	 *
	 * @return string|WP_Error
	 */
	public function render_field(string $template_id, string $field, array $data, string $context = 'text', bool $preview = false) {
		$definition = $this->registry->get($template_id);
		$values = $this->registry->values($template_id);
		if (!$definition || !$values || !array_key_exists($field, $values)) {
			return new WP_Error('yoga_mail_unknown_field', 'Неизвестное поле шаблона.');
		}
		$data = wp_parse_args($data, array(
			'site_name' => wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
			'site_url'  => home_url('/'),
		));
		if ($preview) {
			$data = wp_parse_args($data, $this->registry->examples($template_id));
		}
		return $this->merge((string) $values[$field], $definition, $data, $context, $preview);
	}

	public function inline_content_styles(string $html, int $font_size = 16): string {
		$html = wp_kses_post($html);
		if (trim(wp_strip_all_tags($html)) !== '' && strpos($html, '<') === false) {
			$html = wpautop(esc_html($html));
		}
		$html = preg_replace_callback(
			'/\sstyle=(["\'])(.*?)\1/is',
			static function (array $matches) use ($font_size): string {
				$style = preg_replace(
					'/(^|;)\s*font-family\s*:[^;]*/i',
					'$1font-family:Mulish,Helvetica,Arial,sans-serif',
					$matches[2]
				);
				// Apply the current body size to previously saved template styles too.
				if ($font_size === 16) {
					$style = preg_replace('/(^|;)\s*font-size\s*:\s*14px(?=\s*(?:!important\s*)?(?:;|$))/i', '${1}font-size:16px', $style);
				}
				return ' style=' . $matches[1] . $style . $matches[1];
			},
			$html
		);
		$styles = array(
			'p'  => 'margin:0 0 15px;font-family:Mulish,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#1f1f1f;text-align:center;',
			'ul' => 'margin:0 0 15px;padding-left:24px;font-family:Mulish,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#1f1f1f;text-align:left;',
			'ol' => 'margin:0 0 15px;padding-left:24px;font-family:Mulish,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#1f1f1f;text-align:left;',
			'li' => 'margin:0 0 8px;font-family:Mulish,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#1f1f1f;',
			'a'  => 'color:#9153e1;text-decoration:underline;',
		);
		if ($font_size === 14) {
			$styles = array_map(static function (string $style): string { return str_replace('font-size:16px', 'font-size:14px', $style); }, $styles);
		}
		foreach ($styles as $tag => $style) {
			$html = preg_replace_callback(
				'/<' . $tag . '(\s[^>]*)?>/i',
				static function (array $matches) use ($tag, $style): string {
					$attributes = isset($matches[1]) ? $matches[1] : '';
					if (preg_match('/\sstyle=([' . "\"'" . '])(.*?)\1/i', $attributes, $style_match)) {
						$replacement = ' style=' . $style_match[1] . $style . $style_match[2] . $style_match[1];
						$attributes = preg_replace('/\sstyle=([' . "\"'" . '])(.*?)\1/i', $replacement, $attributes, 1);
					} else {
						$attributes .= ' style="' . esc_attr($style) . '"';
					}
					return '<' . $tag . $attributes . '>';
				},
				$html
			);
		}
		$html = preg_replace_callback(
			'/<td\b([^>]*)>/i',
			static function (array $matches): string {
				$attributes = isset($matches[1]) ? $matches[1] : '';
				if (preg_match('/\salign\s*=/i', $attributes)) {
					return $matches[0];
				}
				if (
					preg_match("/\sstyle=([\"'])(.*?)\\1/i", $attributes, $style_match)
					&& preg_match('/(?:^|;)\s*text-align\s*:\s*(left|center|right)\b/i', $style_match[2], $align_match)
				) {
					return '<td align="' . strtolower($align_match[1]) . '"' . $attributes . '>';
				}
				return $matches[0];
			},
			$html
		);
		return $html;
	}

	/** Keep desktop inline sizes as a fallback; reduce 16px text on small screens. */
	public function responsive_html(string $html, bool $is_staff_request = false): string {
		if (stripos($html, '</head>') === false || strpos($html, 'id="yoga-mail-responsive"') !== false) {
			return $html;
		}
		$html = preg_replace_callback(
			'/<[a-z][a-z0-9]*\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i',
			static function (array $matches): string {
				$tag = $matches[0];
				if (!preg_match('/\sstyle=(["\'])(.*?)\1/is', $tag, $style)) {
					return $tag;
				}
				// Saved styles can override defaults, so use the last font-size declaration.
				preg_match_all('/(?:^|;)\s*font-size\s*:\s*([^;]+)/i', $style[2], $sizes);
				if (!$sizes[1] || !preg_match('/^16px\s*(?:!important\s*)?$/i', trim(end($sizes[1])))) {
					return $tag;
				}
				if (preg_match('/\sclass=(["\'])(.*?)\1/is', $tag)) {
					return preg_replace_callback('/\sclass=(["\'])(.*?)\1/is', static function (array $classes): string {
						return ' class=' . $classes[1] . $classes[2] . ' yoga-mail-text' . $classes[1];
					}, $tag, 1);
				}
				return preg_replace('/^(<[a-z][a-z0-9]*)\b/i', '$1 class="yoga-mail-text"', $tag, 1);
			},
			$html
		);
		$rules = $is_staff_request
			? '@media screen and (max-width:520px){.yoga-mail-shell{padding-left:10px!important;padding-right:10px!important;}.yoga-mail-content{padding-left:20px!important;padding-right:20px!important;}}'
			: '@media screen and (max-width:600px){.yoga-mail-text{font-size:14px!important;}.yoga-mail-shell{padding-left:0!important;padding-right:0!important;}.yoga-mail-card{max-width:600px!important;}.yoga-mail-content{padding-left:30px!important;padding-right:30px!important;}.yoga-mail-comment-gap{width:20px!important;min-width:20px!important;}}';
		$css = '<style id="yoga-mail-responsive" type="text/css">'
			. $rules
			. '</style>';
		return preg_replace('/<\/head>/i', $css . "\n</head>", $html, 1);
	}

	public function plain_from_html(string $html): string {
		$html = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html);
		$html = preg_replace('/<\/(p|li|h[1-6]|tr)>/i', "\n", (string) $html);
		$text = html_entity_decode(wp_strip_all_tags((string) $html), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
		$text = preg_replace("/[\t ]+\n/", "\n", $text);
		$text = preg_replace("/\n{3,}/", "\n\n", (string) $text);
		return trim((string) $text);
	}

	/**
	 * @return string|WP_Error
	 */
	private function merge(string $template, array $definition, array $data, string $context, bool $preview) {
		if ($template === '') {
			return '';
		}
		$error = null;
		$result = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_-]+)\s*\}\}/', function (array $matches) use ($definition, $data, $context, $preview, &$error): string {
			$key = sanitize_key($matches[1]);
			if (!isset($definition['tags'][$key])) {
				$error = new WP_Error('yoga_mail_unknown_merge_tag', 'Неизвестный merge-тег: ' . $key);
				return '';
			}
			if (!array_key_exists($key, $data)) {
				$error = new WP_Error('yoga_mail_missing_merge_data', 'Нет значения для merge-тега: ' . $key);
				return '';
			}
			$value = (string) $data[$key];
			$type = (string) ($definition['tags'][$key]['type'] ?? 'text');
			if ($context === 'url') {
				return esc_url_raw($value);
			}
			if ($context === 'html' && $type === 'html') {
				return wp_kses_post($value);
			}
			if ($context === 'html') {
				return esc_html($value);
			}
			return wp_strip_all_tags($value);
		}, $template);
		if ($error) {
			return $error;
		}
		if (preg_match('/\{\{.*?\}\}/', (string) $result)) {
			return new WP_Error('yoga_mail_unresolved_merge_tag', 'В шаблоне остался неразрешённый merge-тег.');
		}
		if ($context === 'url' && trim($template) !== '' && $result === '') {
			return new WP_Error('yoga_mail_invalid_url', 'CTA содержит недопустимый URL.');
		}
		if ($context === 'url' && $result !== '' && !wp_http_validate_url((string) $result) && strpos((string) $result, 'mailto:') !== 0) {
			return new WP_Error('yoga_mail_invalid_url', 'CTA содержит недопустимый URL.');
		}
		return (string) $result;
	}
}
