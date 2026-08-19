<?php
/**
 * Компонент безопасности: protected media.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_protected_media_request_path')) {
	function yoga_protected_media_request_path(): string {
		if (!isset($_GET['yoga_protected_media'])) {
			return '';
		}

		$relative = (string) wp_unslash($_GET['yoga_protected_media']);
		$relative = rawurldecode($relative);
		$relative = str_replace('\\', '/', $relative);
		$relative = ltrim($relative, '/');

		if ($relative === '' || str_contains($relative, '../') || str_contains($relative, "\0")) {
			return '';
		}

		return $relative;
	}
}

if (!function_exists('yoga_protected_media_real_path')) {
	function yoga_protected_media_real_path(string $relative): string {
		$uploads = wp_upload_dir();
		$base_dir = !empty($uploads['basedir']) ? realpath((string) $uploads['basedir']) : false;
		if ($base_dir === false) {
			return '';
		}

		$path = realpath(trailingslashit($base_dir) . $relative);
		if ($path === false || !is_file($path)) {
			return '';
		}

		$base = rtrim(str_replace('\\', '/', $base_dir), '/') . '/';
		$real = str_replace('\\', '/', $path);

		return str_starts_with($real, $base) ? $path : '';
	}
}

if (!function_exists('yoga_protected_media_attachment_id_from_relative')) {
	function yoga_protected_media_attachment_id_from_relative(string $relative): int {
		global $wpdb;

		$relative = ltrim(str_replace('\\', '/', $relative), '/');
		if ($relative === '') {
			return 0;
		}

		$id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
				$relative
			)
		);
		if ($id > 0) {
			return $id;
		}

		$dir = trim(dirname($relative), '.\\/');
		$file = basename($relative);
		if ($dir === '' || $file === '') {
			return 0;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
				$wpdb->esc_like($dir . '/') . '%'
			),
			ARRAY_A
		);

		foreach ($rows as $row) {
			$attachment_id = (int) ($row['post_id'] ?? 0);
			if ($attachment_id <= 0) {
				continue;
			}

			$meta = wp_get_attachment_metadata($attachment_id);
			if (empty($meta['sizes']) || !is_array($meta['sizes'])) {
				continue;
			}

			foreach ($meta['sizes'] as $size) {
				if (is_array($size) && (string) ($size['file'] ?? '') === $file) {
					return $attachment_id;
				}
			}
		}

		return 0;
	}
}

if (!function_exists('yoga_protected_media_url_from_relative')) {
	function yoga_protected_media_url_from_relative(string $relative): string {
		$uploads = wp_upload_dir();
		if (empty($uploads['baseurl'])) {
			return '';
		}

		return trailingslashit((string) $uploads['baseurl']) . ltrim($relative, '/');
	}
}

if (!function_exists('yoga_protected_media_find_candidate_practices')) {



	function yoga_protected_media_find_candidate_practices(int $attachment_id, string $relative): array {
		global $wpdb;

		$ids = array();
		$basename = basename($relative);
		$url = yoga_protected_media_url_from_relative($relative);

		if ($attachment_id > 0) {
			$ids = array_merge(
				$ids,
				$wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value = %s",
						'practice_sections_%',
						(string) $attachment_id
					)
				)
			);

			$parent_id = (int) wp_get_post_parent_id($attachment_id);
			if ($parent_id > 0 && get_post_type($parent_id) === 'practice') {
				$ids[] = $parent_id;
			}
		}

		foreach (array_filter(array($basename, $url)) as $needle) {
			$ids = array_merge(
				$ids,
				$wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value LIKE %s",
						'practice_sections_%',
						'%' . $wpdb->esc_like((string) $needle) . '%'
					)
				)
			);
		}

		if ($attachment_id > 0) {
			$download_practices = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
					'practice_download',
					(string) $attachment_id
				)
			);
			$ids = array_merge($ids, $download_practices);
		}

		return array_values(
			array_unique(
				array_filter(
					array_map('intval', $ids),
					static function (int $post_id): bool {
						return $post_id > 0 && get_post_type($post_id) === 'practice';
					}
				)
			)
		);
	}
}

if (!function_exists('yoga_protected_media_section_contains_file')) {
	function yoga_protected_media_section_contains_file(array $section, int $attachment_id, string $relative): bool {
		$text_needles = array_filter(array(
			basename($relative),
			yoga_protected_media_url_from_relative($relative),
		));

		$media_keys = array(
			'ID',
			'id',
			'media_file',
			'media_file_mod',
			'gallery',
			'gallery_mod',
			'image',
			'url',
		);

		$stack = array(array('key' => '', 'value' => $section));
		while ($stack !== array()) {
			$current = array_pop($stack);
			$key = (string) ($current['key'] ?? '');
			$value = $current['value'] ?? null;

			if (is_array($value)) {
				if ($attachment_id > 0) {
					foreach (array('ID', 'id') as $id_key) {
						if (isset($value[$id_key]) && (int) $value[$id_key] === $attachment_id) {
							return true;
						}
					}
				}

				foreach ($value as $child_key => $child_value) {
					$stack[] = array('key' => (string) $child_key, 'value' => $child_value);
				}
				continue;
			}

			if ($attachment_id > 0 && in_array($key, $media_keys, true) && (string) $value === (string) $attachment_id) {
				return true;
			}

			$string_value = (string) $value;
			foreach ($text_needles as $needle) {
				if ($needle !== '' && $string_value === $needle) {
					return true;
				}

				if ($needle !== '' && str_contains($string_value, $needle)) {
					return true;
				}
			}
		}

		return false;
	}
}

if (!function_exists('yoga_protected_media_user_can_access')) {
	function yoga_protected_media_user_can_access(int $attachment_id, string $relative): ?bool {
		$practice_ids = yoga_protected_media_find_candidate_practices($attachment_id, $relative);
		if ($practice_ids === array()) {
			return null;
		}

		$matched_protected_reference = false;

		foreach ($practice_ids as $practice_id) {
			if (
				function_exists('yoga_get_practice_download_source')
				&& function_exists('yoga_user_can_download_practice')
			) {
				$download_source = yoga_get_practice_download_source($practice_id);
				if (is_array($download_source) && !empty($download_source['path'])) {
					$download_path = str_replace('\\', '/', (string) realpath((string) $download_source['path']));
					$request_path = str_replace('\\', '/', yoga_protected_media_real_path($relative));
					if ($download_path !== '' && $request_path !== '' && $download_path === $request_path) {
						$matched_protected_reference = true;
						return yoga_user_can_download_practice(get_current_user_id(), $practice_id);
					}
				}
			}

			$sections = function_exists('get_field') ? get_field('practice_sections', $practice_id) : array();
			if (!is_array($sections)) {
				continue;
			}

			foreach ($sections as $section) {
				if (!is_array($section) || !yoga_protected_media_section_contains_file($section, $attachment_id, $relative)) {
					continue;
				}

				$matched_protected_reference = true;

				$layout = sanitize_key((string) ($section['acf_fc_layout'] ?? ''));
				$can_view_layout = !function_exists('yoga_can_view_practice_section_layout')
					|| yoga_can_view_practice_section_layout($layout, $practice_id);
				$can_view_section = !function_exists('yoga_can_view_practice_section')
					|| yoga_can_view_practice_section($section, get_current_user_id(), $practice_id);

				if ($can_view_layout && $can_view_section) {
					return true;
				}
			}
		}

		return $matched_protected_reference ? false : null;
	}
}

if (!function_exists('yoga_protected_media_send_file')) {
	function yoga_protected_media_send_file(string $path, bool $protected): void {
		$size = filesize($path);
		if ($size === false) {
			status_header(404);
			exit;
		}

		$mime = wp_check_filetype($path);
		$type = !empty($mime['type']) ? (string) $mime['type'] : 'application/octet-stream';
		$start = 0;
		$end = (int) $size - 1;
		$status = 200;

		if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $matches)) {
			$status = 206;
			if ($matches[1] !== '') {
				$start = max(0, (int) $matches[1]);
			}
			if ($matches[2] !== '') {
				$end = min($end, (int) $matches[2]);
			}
			if ($start > $end || $start >= $size) {
				status_header(416);
				header('Content-Range: bytes */' . (string) $size);
				exit;
			}
		}

		status_header($status);
		header('Content-Type: ' . $type);
		header('Accept-Ranges: bytes');
		header('X-Content-Type-Options: nosniff');
		header('X-Robots-Tag: noindex, nofollow', true);

		if ($protected) {
			nocache_headers();
		} else {
			header('Cache-Control: public, max-age=31536000');
		}

		if ($status === 206) {
			header('Content-Range: bytes ' . $start . '-' . $end . '/' . (string) $size);
		}

		$length = $end - $start + 1;
		header('Content-Length: ' . (string) $length);

		$handle = fopen($path, 'rb');
		if ($handle === false) {
			status_header(404);
			exit;
		}

		fseek($handle, $start);
		$sent = 0;
		while (!feof($handle) && $sent < $length) {
			$chunk_size = min(1024 * 1024, $length - $sent);
			$buffer = fread($handle, $chunk_size);
			if ($buffer === false || $buffer === '') {
				break;
			}
			echo $buffer;
			$sent += strlen($buffer);
			flush();
		}

		fclose($handle);
		exit;
	}
}

if (!function_exists('yoga_handle_protected_media_request')) {
	function yoga_handle_protected_media_request(): void {
		$relative = yoga_protected_media_request_path();
		if ($relative === '') {
			return;
		}

		$path = yoga_protected_media_real_path($relative);
		if ($path === '') {
			status_header(404);
			exit;
		}

		$attachment_id = yoga_protected_media_attachment_id_from_relative($relative);
		$access = yoga_protected_media_user_can_access($attachment_id, $relative);

		if ($access === false) {
			status_header(is_user_logged_in() ? 403 : 401);
			header('X-Robots-Tag: noindex, nofollow', true);
			exit;
		}

		yoga_protected_media_send_file($path, $access === true);
	}
}

add_action('init', 'yoga_handle_protected_media_request', 0);
