<?php
/**
 * Helpers for rendering practice text content.
 */

if (!function_exists('yoga_practice_content_images_lightbox')) {
	function yoga_practice_content_images_lightbox($html): string {
		$html = (string) $html;
		if (trim($html) === '') {
			return $html;
		}

		$protected_anchors = array();
		$html = preg_replace_callback('/<a\b[^>]*>.*?<img\b[^>]*>.*?<\/a>/is', static function ($matches) use (&$protected_anchors) {
			$key = '%%YOGA_PRACTICE_IMAGE_LINK_' . count($protected_anchors) . '%%';
			$protected_anchors[$key] = yoga_practice_prepare_existing_image_link($matches[0]);
			return $key;
		}, $html);

		$html = preg_replace_callback('/<img\b[^>]*>/i', static function ($matches) {
			return yoga_practice_wrap_image_tag_with_lightbox($matches[0]);
		}, $html);

		if (!empty($protected_anchors)) {
			$html = strtr($html, $protected_anchors);
		}

		return $html;
	}
}

if (!function_exists('yoga_practice_format_rich_text')) {
	function yoga_practice_format_rich_text($value, bool $apply_content_filters = false): string {
		if (is_array($value) || is_object($value)) {
			return '';
		}

		$html = trim((string) $value);
		if ($html === '') {
			return '';
		}

		if ($apply_content_filters) {
			$html = apply_filters('the_content', $html);
		} elseif (!preg_match('/<\s*(p|br|ul|ol|li|strong|b|em|i|a|span|blockquote|figure|img)\b/i', $html)) {
			$html = wpautop($html);
		}

		$html = wp_kses_post($html);

		return function_exists('yoga_practice_content_images_lightbox')
			? yoga_practice_content_images_lightbox($html)
			: $html;
	}
}

if (!function_exists('yoga_practice_prepare_existing_image_link')) {
	function yoga_practice_prepare_existing_image_link(string $anchor_html): string {
		if (!preg_match('/<img\b[^>]*>/i', $anchor_html, $image_match)) {
			return $anchor_html;
		}

		$image_tag = $image_match[0];
		$href = yoga_practice_get_full_image_url_from_img_tag($image_tag);
		if ($href === '') {
			return $anchor_html;
		}

		return preg_replace_callback('/<a\b[^>]*>/i', static function ($matches) use ($image_tag, $href) {
			return yoga_practice_prepare_lightbox_open_tag($matches[0], $image_tag, $href);
		}, $anchor_html, 1);
	}
}

if (!function_exists('yoga_practice_wrap_image_tag_with_lightbox')) {
	function yoga_practice_wrap_image_tag_with_lightbox(string $image_tag): string {
		$href = yoga_practice_get_full_image_url_from_img_tag($image_tag);
		if ($href === '') {
			return $image_tag;
		}

		$link_open = yoga_practice_prepare_lightbox_open_tag('<a>', $image_tag, $href);
		return $link_open . $image_tag . '</a>';
	}
}

if (!function_exists('yoga_practice_get_full_image_url_from_img_tag')) {
	function yoga_practice_get_full_image_url_from_img_tag(string $image_tag): string {
		$class = yoga_practice_get_html_attribute($image_tag, 'class');
		if ($class !== '' && preg_match('/\bwp-image-(\d+)\b/', $class, $matches)) {
			$full_url = wp_get_attachment_image_url((int) $matches[1], 'full');
			if (is_string($full_url) && $full_url !== '') {
				return $full_url;
			}
		}

		return yoga_practice_get_html_attribute($image_tag, 'src');
	}
}

if (!function_exists('yoga_practice_prepare_lightbox_open_tag')) {
	function yoga_practice_prepare_lightbox_open_tag(string $open_tag, string $image_tag, string $href): string {
		$open_tag = yoga_practice_set_html_attribute($open_tag, 'href', esc_url_raw($href));
		$open_tag = yoga_practice_set_html_attribute($open_tag, 'data-fancybox', 'practice-content-images');
		$open_tag = yoga_practice_set_html_attribute($open_tag, 'class', yoga_practice_lightbox_link_class($image_tag, yoga_practice_get_html_attribute($open_tag, 'class')));

		$alt = trim(yoga_practice_get_html_attribute($image_tag, 'alt'));
		if ($alt !== '') {
			$open_tag = yoga_practice_set_html_attribute($open_tag, 'data-caption', $alt);
		}

		return $open_tag;
	}
}

if (!function_exists('yoga_practice_lightbox_link_class')) {
	function yoga_practice_lightbox_link_class(string $image_tag, string $existing_classes = ''): string {
		$classes = preg_split('/\s+/', trim($existing_classes));
		$classes = is_array($classes) ? array_filter($classes) : array();
		$classes[] = 'praktika-lightbox-link';

		$image_classes = preg_split('/\s+/', trim(yoga_practice_get_html_attribute($image_tag, 'class')));
		if (is_array($image_classes)) {
			foreach ($image_classes as $class) {
				if (in_array($class, array('aligncenter', 'alignleft', 'alignright'), true)) {
					$classes[] = $class;
				}
			}
		}

		return implode(' ', array_unique($classes));
	}
}

if (!function_exists('yoga_practice_get_html_attribute')) {
	function yoga_practice_get_html_attribute(string $tag, string $attribute): string {
		$attribute = preg_quote($attribute, '/');
		if (!preg_match('/\s' . $attribute . '\s*=\s*(["\'])(.*?)\1/is', $tag, $matches)) {
			return '';
		}

		return html_entity_decode((string) $matches[2], ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('yoga_practice_set_html_attribute')) {
	function yoga_practice_set_html_attribute(string $tag, string $attribute, string $value): string {
		$escaped_value = esc_attr($value);
		$quoted_attribute = preg_quote($attribute, '/');

		if (preg_match('/\s' . $quoted_attribute . '\s*=\s*(["\']).*?\1/is', $tag)) {
			return preg_replace_callback('/(\s' . $quoted_attribute . '\s*=\s*)(["\']).*?\2/is', static function ($matches) use ($escaped_value) {
				return $matches[1] . $matches[2] . $escaped_value . $matches[2];
			}, $tag, 1);
		}

		return preg_replace('/\s*\/?>$/', ' ' . $attribute . '="' . $escaped_value . '">', $tag, 1);
	}
}
