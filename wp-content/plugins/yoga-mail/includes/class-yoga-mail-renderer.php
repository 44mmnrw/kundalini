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

		$subject = $this->merge((string) $values['subject'], $definition, $data, 'text', $preview);
		$preheader = $this->merge((string) $values['preheader'], $definition, $data, 'text', $preview);
		$heading = $this->merge((string) $values['heading'], $definition, $data, 'text', $preview);
		$body = $this->merge((string) $values['body'], $definition, $data, 'html', $preview);
		$cta_label = $this->merge((string) $values['cta_label'], $definition, $data, 'text', $preview);
		$cta_url = $this->merge((string) $values['cta_url'], $definition, $data, 'url', $preview);
		$footer_note = $this->merge((string) $values['footer_note'], $definition, $data, 'text', $preview);
		foreach (array($subject, $preheader, $heading, $body, $cta_label, $cta_url, $footer_note) as $result) {
			if (is_wp_error($result)) {
				return $result;
			}
		}

		$body_html = $this->inline_content_styles((string) $body);
		$settings = $this->registry->settings();
		$template_path = YOGA_MAIL_PATH . 'templates/layout/html.php';
		ob_start();
		include $template_path;
		$html = (string) ob_get_clean();

		$plain_body = $this->plain_from_html((string) $body);
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

	public function inline_content_styles(string $html): string {
		$html = wp_kses_post($html);
		if (trim(wp_strip_all_tags($html)) !== '' && strpos($html, '<') === false) {
			$html = wpautop(esc_html($html));
		}
		$html = preg_replace_callback(
			'/\sstyle=(["\'])(.*?)\1/is',
			static function (array $matches): string {
				$style = preg_replace(
					'/(^|;)\s*font-family\s*:[^;]*/i',
					'$1font-family:Mulish,Arial,Helvetica,sans-serif',
					$matches[2]
				);
				return ' style=' . $matches[1] . $style . $matches[1];
			},
			$html
		);
		$styles = array(
			'p'  => 'margin:0 0 15px;font-family:Mulish,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f1f1f;text-align:center;',
			'ul' => 'margin:0 0 15px;padding-left:24px;font-family:Mulish,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f1f1f;text-align:left;',
			'ol' => 'margin:0 0 15px;padding-left:24px;font-family:Mulish,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f1f1f;text-align:left;',
			'li' => 'margin:0 0 8px;font-family:Mulish,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f1f1f;',
			'a'  => 'color:#9153e1;text-decoration:underline;',
		);
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
