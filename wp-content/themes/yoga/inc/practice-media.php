<?php
/**
 * Shared practice media helpers.
 *
 * @package Yoga
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('yoga_normalize_kinescope_video_url')) {
	function yoga_normalize_kinescope_video_url($url): string {
		$url = esc_url_raw(trim((string) $url), array('https'));
		if ($url === '') {
			return '';
		}

		$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
		if ($host !== 'kinescope.io' && !str_ends_with($host, '.kinescope.io')) {
			return '';
		}

		return $url;
	}
}

if (!function_exists('yoga_get_practice_media_file_url')) {
	function yoga_get_practice_media_file_url($file): string {
		if (is_array($file)) {
			$url = trim((string) ($file['url'] ?? ''));
			if ($url !== '') {
				return esc_url_raw($url);
			}

			$file = (int) ($file['ID'] ?? $file['id'] ?? 0);
		}

		if (is_numeric($file) && (int) $file > 0) {
			return esc_url_raw((string) wp_get_attachment_url((int) $file));
		}

		return is_string($file) ? esc_url_raw(trim($file)) : '';
	}
}

if (!function_exists('yoga_get_practice_media_descriptor')) {
	/**
	 * Normalizes one exercise/section media value for validation and rendering.
	 */
	function yoga_get_practice_media_descriptor(array $args): array {
		$media_type = sanitize_key((string) ($args['media_type'] ?? 'none'));
		$video_source = sanitize_key((string) ($args['video_source'] ?? 'file'));
		$media_url = yoga_get_practice_media_file_url($args['media_file'] ?? null);

		if ($media_type === 'audio' && $media_url !== '') {
			return array(
				'media_type' => 'audio',
				'provider'   => 'file',
				'src'        => $media_url,
			);
		}

		if ($media_type !== 'video') {
			return array();
		}

		if ($video_source === 'file' && $media_url !== '') {
			return array(
				'media_type' => 'video',
				'provider'   => 'file',
				'src'        => $media_url,
			);
		}

		if ($video_source === 'kinescope') {
			$kinescope_url = yoga_normalize_kinescope_video_url($args['kinescope_url'] ?? '');
			if ($kinescope_url !== '') {
				return array(
					'media_type' => 'video',
					'provider'   => 'kinescope',
					'src'        => $kinescope_url,
				);
			}
		}

		if ($video_source === 'youtube' && function_exists('yoga_get_youtube_video_id')) {
			$youtube_id = yoga_get_youtube_video_id($args['youtube_url'] ?? '');
			if ($youtube_id !== '') {
				return array(
					'media_type' => 'video',
					'provider'   => 'youtube',
					'src'        => $youtube_id,
				);
			}
		}

		return array();
	}
}

if (!function_exists('yoga_practice_video_section_has_valid_media')) {
	function yoga_practice_video_section_has_valid_media(array $section): bool {
		return yoga_get_practice_media_descriptor(array(
			'media_type'    => 'video',
			'media_file'    => $section['media_file'] ?? null,
			'video_source'  => $section['video_source'] ?? 'file',
			'kinescope_url' => $section['kinescope_url'] ?? '',
			'youtube_url'   => $section['youtube_url'] ?? '',
		)) !== array();
	}
}

if (!function_exists('yoga_render_practice_media_player')) {
	function yoga_render_practice_media_player(array $args): void {
		$media = yoga_get_practice_media_descriptor($args);
		if ($media === array()) {
			return;
		}

		$version = sanitize_key((string) ($args['version'] ?? 'main'));
		$player_id = sanitize_html_class((string) ($args['player_id'] ?? 'practice-player'));
		?>
		<div class="exercise-player"
			data-version="<?php echo esc_attr($version); ?>"
			data-media-type="<?php echo esc_attr($media['media_type']); ?>"
			data-media-provider="<?php echo esc_attr($media['provider']); ?>"
			data-media-src="<?php echo esc_attr($media['src']); ?>"
			data-allow-fullscreen="<?php echo !empty($args['allow_fullscreen']) ? 'true' : 'false'; ?>"
			data-restrict-scrub="<?php echo !empty($args['restrict_scrub']) ? 'true' : 'false'; ?>"
			data-auto-play="<?php echo !empty($args['auto_play']) ? 'true' : 'false'; ?>">
			<?php if ($media['provider'] === 'kinescope'): ?>
			<div id="<?php echo esc_attr($player_id); ?>" class="kinescope-player-container"></div>
			<?php elseif ($media['provider'] === 'youtube'): ?>
			<div id="<?php echo esc_attr($player_id); ?>" class="youtube-player-container" data-plyr-provider="youtube" data-plyr-embed-id="<?php echo esc_attr($media['src']); ?>"></div>
			<?php elseif ($media['media_type'] === 'audio'): ?>
			<audio controls><source src="<?php echo esc_url($media['src']); ?>" type="audio/mp3"></audio>
			<?php else: ?>
			<video controls playsinline><source src="<?php echo esc_url($media['src']); ?>" type="video/mp4">Ваш браузер не поддерживает видео тег.</video>
			<?php endif; ?>
		</div>
		<?php
	}
}
