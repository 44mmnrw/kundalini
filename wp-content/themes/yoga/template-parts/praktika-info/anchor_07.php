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
	$video_title = 'Видео выполнение';
}
$video_subtitle = trim((string) ($section['subtitle'] ?? ''));
$video_details = trim((string) ($section['details'] ?? ''));
$video_description = $section['description'] ?? '';
$player_id = 'practice-execution-video-' . sanitize_html_class((string) ($section_key ?? $anchor_id));
?>
<div class="practice-execution-video">
	<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
	<div class="practice-execution-video__card">
		<div class="practice-execution-video__info">
			<h3><?php echo esc_html($video_title); ?></h3>
			<?php if ($video_subtitle !== ''): ?>
			<h4><?php echo esc_html($video_subtitle); ?></h4>
			<?php endif; ?>
			<?php if ($video_details !== ''): ?>
			<div class="exercise-item__info-details">
				<div class="exercise-detail-rich">
					<?php
					echo function_exists('yoga_practice_format_detail_text')
						? yoga_practice_format_detail_text($video_details)
						: wp_kses_post(wpautop($video_details));
					?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div class="player">
			<div class="player__plug">
				<?php
				yoga_render_practice_media_player(array(
					'media_type'       => 'video',
					'media_file'       => $section['media_file'] ?? null,
					'video_source'     => $section['video_source'] ?? 'file',
					'kinescope_url'    => $section['kinescope_url'] ?? '',
					'youtube_url'      => $section['youtube_url'] ?? '',
					'version'          => 'standalone',
					'player_id'        => $player_id,
					'allow_fullscreen' => true,
					'restrict_scrub'   => false,
					'auto_play'        => false,
				));
				?>
			</div>
		</div>
		<?php if (trim((string) $video_description) !== ''): ?>
		<div class="exercise-content practice-execution-video__description">
			<?php
			echo function_exists('yoga_practice_format_rich_text')
				? yoga_practice_format_rich_text($video_description, true)
				: apply_filters('the_content', (string) $video_description);
			?>
		</div>
		<?php endif; ?>
	</div>
</div>
