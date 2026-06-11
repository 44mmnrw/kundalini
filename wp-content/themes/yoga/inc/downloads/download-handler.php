<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_download_request_wants_json')) {
	function yoga_download_request_wants_json(): bool {
		if (isset($_GET['yoga_fetch']) && (string) $_GET['yoga_fetch'] === '1') {
			return true;
		}

		$requested_with = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			? strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH'])
			: '';

		return $requested_with === 'xmlhttprequest';
	}
}

if (!function_exists('yoga_download_abort')) {
	function yoga_download_abort(string $message, int $status = 403, string $code = 'download_denied'): void {
		if (yoga_download_request_wants_json()) {
			yoga_ajax_error($message, $code, $status);
		}

		wp_die(
			esc_html($message),
			esc_html__('Скачивание недоступно', 'yoga'),
			array('response' => $status)
		);
	}
}

if (!function_exists('yoga_handle_practice_download')) {
	function yoga_handle_practice_download(): void {
		if (!is_user_logged_in()) {
			yoga_download_abort(
				__('Для скачивания необходимо войти в аккаунт.', 'yoga'),
				401,
				'not_authenticated'
			);
		}

		$practice_id = isset($_GET['practice_id']) ? (int) $_GET['practice_id'] : 0;
		if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice') {
			yoga_download_abort(
				__('Практика не найдена.', 'yoga'),
				404,
				'practice_not_found'
			);
		}

		check_ajax_referer('yoga_practice_download_' . $practice_id, 'nonce');

		$user_id = get_current_user_id();

		if (!yoga_user_can_download_practice($user_id, $practice_id)) {
			$error_message = yoga_user_has_downloaded_practice($user_id, $practice_id)
				? yoga_get_practice_already_downloaded_message()
				: yoga_get_download_limit_exceeded_message();
			$error_code    = yoga_user_has_downloaded_practice($user_id, $practice_id)
				? 'already_downloaded'
				: 'limit_exceeded';

			yoga_download_abort($error_message, 403, $error_code);
		}

		$source = yoga_get_practice_download_source($practice_id);
		if ($source === null || $source['path'] === '') {
			yoga_download_abort(
				__('Файл протокола недоступен.', 'yoga'),
				404,
				'file_not_found'
			);
		}

		$real_path = realpath($source['path']);
		$uploads   = wp_upload_dir();
		$base_dir  = !empty($uploads['basedir']) ? realpath($uploads['basedir']) : false;

		if ($real_path === false || $base_dir === false || strpos($real_path, $base_dir) !== 0) {
			yoga_download_abort(
				__('Файл недоступен для скачивания.', 'yoga'),
				403,
				'file_forbidden'
			);
		}

		yoga_record_practice_download($user_id, $practice_id);

		$filename = sanitize_file_name($source['filename']);
		if ($filename === '') {
			$filename = 'protocol.pdf';
		}

		$mime = wp_check_filetype($real_path);
		$type = !empty($mime['type']) ? $mime['type'] : 'application/octet-stream';

		nocache_headers();
		header('Content-Type: ' . $type);
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . (string) filesize($real_path));
		header('X-Robots-Tag: noindex, nofollow', true);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile($real_path);
		exit;
	}
}

add_action('wp_ajax_yoga_practice_download', 'yoga_handle_practice_download');

if (!function_exists('yoga_enqueue_practice_download_script')) {
	function yoga_enqueue_practice_download_script(): void {
		if (!is_singular('practice')) {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();
		$js_path   = $theme_dir . '/assets/js/practice-download.js';
		$js_ver    = file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0';

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$js_ver = (string) time();
		}

		wp_enqueue_script(
			'yoga-practice-download',
			$theme_uri . '/assets/js/practice-download.js',
			array('jquery'),
			$js_ver,
			true
		);

		wp_localize_script(
			'yoga-practice-download',
			'yogaPracticeDownload',
			array(
				'downloadLabel'        => __('Скачать протокол практики', 'yoga'),
				'downloadedLabel'      => yoga_get_practice_already_downloaded_message(),
				'limitExceededMessage' => yoga_get_download_limit_exceeded_message(),
				'errorMessage'         => __('Не удалось скачать файл. Попробуйте ещё раз.', 'yoga'),
			)
		);
	}
}

add_action('wp_enqueue_scripts', 'yoga_enqueue_practice_download_script', 25);
