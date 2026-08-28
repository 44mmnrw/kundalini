<?php
/**
 * Optional standalone execution video section.
 *
 * @package Yoga
 */

if (!isset($section) || !is_array($section) || !function_exists('yoga_practice_video_section_has_valid_media')) {
	return;
}

if (!yoga_practice_video_section_has_valid_media($section)) {
	return;
}

$anchor_id = isset($anchor_id) && $anchor_id !== '' ? (string) $anchor_id : 'anchor_06';
$video_title = trim((string) ($section_title ?? ($section['section_title'] ?? '')));
if ($video_title === '') {
	$video_title = 'Видео выполнения';
}
$player_id = 'practice-execution-video-' . sanitize_html_class((string) ($section_key ?? $anchor_id));
?>
<div class="practice-execution-video">
	<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
	<h3 class="mtb"><?php echo esc_html($video_title); ?></h3>
	<div class="player">
		<div class="player__plug">
			<?php
			yoga_render_practice_media_player(array(
				'media_type'      => 'video',
				'media_file'      => $section['media_file'] ?? null,
				'video_source'    => $section['video_source'] ?? 'file',
				'kinescope_url'   => $section['kinescope_url'] ?? '',
				'youtube_url'     => $section['youtube_url'] ?? '',
				'version'         => 'standalone',
				'player_id'       => $player_id,
				'allow_fullscreen' => true,
				'restrict_scrub'  => false,
				'auto_play'       => false,
			));
			?>
		</div>
	</div>
</div>
