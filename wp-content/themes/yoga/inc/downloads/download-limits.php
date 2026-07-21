<?php
/**
 * Компонент темы: download limits.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_get_tariff_download_limit')) {





	function yoga_get_tariff_download_limit(int $product_id): ?int {
		if ($product_id <= 0 || !function_exists('get_field')) {
			return null;
		}

		$limit = get_field('tariff_download_limit', $product_id);

		if (($limit === null || $limit === '') && function_exists('wc_get_product')) {
			$product = wc_get_product($product_id);
			if ($product && $product->is_type('variation')) {
				$parent_id = (int) $product->get_parent_id();
				if ($parent_id > 0) {
					$limit = get_field('tariff_download_limit', $parent_id);
				}
			}
		}

		if ($limit === null || $limit === '') {
			return null;
		}

		$limit = (int) $limit;
		if ($limit <= 0) {
			return null;
		}

		return $limit;
	}
}

if (!function_exists('yoga_get_user_download_limit')) {





	function yoga_get_user_download_limit(?int $user_id = null): ?int {
		if (!function_exists('get_current_user_tariff')) {
			return null;
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return null;
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['product_id'])) {
			return null;
		}

		return yoga_get_tariff_download_limit((int) $tariff['product_id']);
	}
}

if (!function_exists('yoga_get_user_download_period_bounds')) {





	function yoga_get_user_download_period_bounds(?int $user_id = null): ?array {
		if (!function_exists('get_current_user_tariff') || !function_exists('calculate_access_duration')) {
			return null;
		}

		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return null;
		}

		$tariff = get_current_user_tariff($user_id);
		if (!is_array($tariff) || empty($tariff['access_end'])) {
			return null;
		}

		$access_end = (int) $tariff['access_end'];
		$period     = (string) ($tariff['period'] ?? 'month');
		$duration   = calculate_access_duration($period);

		return array(
			'start' => max(0, $access_end - $duration),
			'end'   => $access_end,
		);
	}
}

if (!function_exists('yoga_get_user_download_log')) {



	function yoga_get_user_download_log(int $user_id): array {
		if ($user_id <= 0) {
			return array();
		}

		$log = get_user_meta($user_id, 'yoga_download_log', true);
		if (!is_array($log)) {
			return array();
		}

		$normalized = array();
		foreach ($log as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$practice_id = (int) ($entry['practice_id'] ?? 0);
			$ts          = (int) ($entry['ts'] ?? 0);
			if ($practice_id <= 0 || $ts <= 0) {
				continue;
			}
			$normalized[] = array(
				'practice_id' => $practice_id,
				'ts'          => $ts,
			);
		}

		return $normalized;
	}
}

if (!function_exists('yoga_get_user_downloads_used')) {
	function yoga_get_user_downloads_used(int $user_id = null): int {
		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		if ($user_id <= 0) {
			return 0;
		}

		$bounds = yoga_get_user_download_period_bounds($user_id);
		if ($bounds === null) {
			return 0;
		}

		$count = 0;
		foreach (yoga_get_user_download_log($user_id) as $entry) {
			if ($entry['ts'] >= $bounds['start'] && $entry['ts'] <= $bounds['end']) {
				$count++;
			}
		}

		return $count;
	}
}

if (!function_exists('yoga_get_user_downloads_remaining')) {



	function yoga_get_user_downloads_remaining(int $user_id = null): ?int {
		$limit = yoga_get_user_download_limit($user_id);
		if ($limit === null) {
			return null;
		}

		return max(0, $limit - yoga_get_user_downloads_used($user_id));
	}
}

if (!function_exists('yoga_url_to_upload_path')) {
	function yoga_url_to_upload_path(string $url): string {
		$uploads = wp_upload_dir();
		if (empty($uploads['baseurl']) || empty($uploads['basedir'])) {
			return '';
		}

		if (strpos($url, $uploads['baseurl']) !== 0) {
			return '';
		}

		$relative = substr($url, strlen($uploads['baseurl']));
		$path     = $uploads['basedir'] . $relative;

		return is_file($path) ? $path : '';
	}
}

if (!function_exists('yoga_get_practice_download_source')) {



	function yoga_get_practice_download_source(int $practice_id): ?array {
		if ($practice_id <= 0 || get_post_type($practice_id) !== 'practice' || !function_exists('get_field')) {
			return null;
		}

		$raw = get_field('practice_download', $practice_id);
		if ($raw === null || $raw === '' || $raw === false) {
			return null;
		}

		$url         = '';
		$attachment_id = 0;

		if (is_array($raw)) {
			$url           = (string) ($raw['url'] ?? '');
			$attachment_id = (int) ($raw['ID'] ?? 0);
		} elseif (is_numeric($raw)) {
			$attachment_id = (int) $raw;
			$url           = (string) wp_get_attachment_url($attachment_id);
		} else {
			$url = (string) $raw;
		}

		if ($url === '') {
			return null;
		}

		$path = '';
		if ($attachment_id > 0) {
			$attached = get_attached_file($attachment_id);
			if (is_string($attached) && $attached !== '' && is_file($attached)) {
				$path = $attached;
			}
		}
		if ($path === '') {
			$path = yoga_url_to_upload_path($url);
		}

		$filename = '';
		if ($attachment_id > 0) {
			$filename = basename(get_attached_file($attachment_id) ?: '');
		}
		if ($filename === '') {
			$parsed = wp_parse_url($url, PHP_URL_PATH);
			$filename = $parsed ? basename($parsed) : 'protocol.pdf';
		}

		return array(
			'url'      => $url,
			'path'     => $path,
			'filename' => $filename,
		);
	}
}

if (!function_exists('yoga_user_has_downloaded_practice')) {



	function yoga_user_has_downloaded_practice(int $user_id, int $practice_id): bool {
		if ($user_id <= 0 || $practice_id <= 0) {
			return false;
		}

		$bounds = yoga_get_user_download_period_bounds($user_id);
		if ($bounds === null) {
			return false;
		}

		foreach (yoga_get_user_download_log($user_id) as $entry) {
			if (
				$entry['practice_id'] === $practice_id
				&& $entry['ts'] >= $bounds['start']
				&& $entry['ts'] <= $bounds['end']
			) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('yoga_user_can_download_practice')) {
	function yoga_user_can_download_practice(int $user_id, int $practice_id): bool {
		if ($user_id <= 0 || $practice_id <= 0) {
			return false;
		}

		if (
			!function_exists('yoga_viewer_has_full_practice_sections')
			|| !yoga_viewer_has_full_practice_sections($user_id, $practice_id)
		) {
			return false;
		}

		if (yoga_get_practice_download_source($practice_id) === null) {
			return false;
		}

		$limit = yoga_get_user_download_limit($user_id);
		if ($limit === null) {
			return true;
		}

		return yoga_get_user_downloads_used($user_id) < $limit;
	}
}

if (!function_exists('yoga_record_practice_download')) {
	function yoga_record_practice_download(int $user_id, int $practice_id): void {
		if ($user_id <= 0 || $practice_id <= 0) {
			return;
		}


		if (yoga_user_has_downloaded_practice($user_id, $practice_id)) {
			return;
		}

		$log   = yoga_get_user_download_log($user_id);
		$log[] = array(
			'practice_id' => $practice_id,
			'ts'          => time(),
		);

		$bounds = yoga_get_user_download_period_bounds($user_id);
		if ($bounds !== null) {
			$cutoff = $bounds['start'] - (365 * DAY_IN_SECONDS);
			$log    = array_values(
				array_filter(
					$log,
					static function (array $entry) use ($cutoff): bool {
						return $entry['ts'] >= $cutoff;
					}
				)
			);
		}

		update_user_meta($user_id, 'yoga_download_log', $log);
	}
}

if (!function_exists('yoga_get_practice_download_url')) {
	function yoga_get_practice_download_url(int $practice_id, int $user_id = null): string {
		if ($user_id === null) {
			$user_id = get_current_user_id();
		}

		return wp_nonce_url(
			add_query_arg(
				array(
					'action'      => 'yoga_practice_download',
					'practice_id' => $practice_id,
				),
				admin_url('admin-ajax.php')
			),
			'yoga_practice_download_' . $practice_id,
			'nonce'
		);
	}
}

if (!function_exists('yoga_get_download_limit_exceeded_message')) {
	function yoga_get_download_limit_exceeded_message(): string {
		return (string) apply_filters(
			'yoga_download_limit_exceeded_message',
			__('Лимит скачиваний исчерпан. Новые загрузки будут доступны после продления подписки.', 'yoga')
		);
	}
}

if (!function_exists('yoga_get_practice_already_downloaded_message')) {
	function yoga_get_practice_already_downloaded_message(): string {
		return (string) apply_filters(
			'yoga_practice_already_downloaded_message',
			__('Протокол уже скачан', 'yoga')
		);
	}
}
