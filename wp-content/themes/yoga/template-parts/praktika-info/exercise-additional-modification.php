<?php
/**
 * Additional exercise modification.
 *
 * @package Yoga
 */

if (!isset($additional_modification) || !is_array($additional_modification)) {
	return;
}

$variant_matter = !empty($additional_modification['matter']) && is_array($additional_modification['matter'])
	? $additional_modification['matter']
	: array();
$variant_details = trim((string) ($additional_modification['details'] ?? ''));
$variant_timing = !empty($additional_modification['timing']) && is_array($additional_modification['timing'])
	? $additional_modification['timing']
	: array();
$variant_media_type = (string) ($additional_modification['media_type'] ?? 'none');
$variant_media_file = $additional_modification['media_file'] ?? array();
$variant_gallery = yoga_normalize_practice_exercise_gallery($additional_modification['gallery'] ?? array());
$variant_content = $additional_modification['content'] ?? '';
$variant_show_timer = $variant_timing !== array();
$variant_gallery_fancybox = 'practice-exercise-gallery-' . $index . '-' . $ex_idx . '-' . $additional_modification_version;
?>

<div class="exercise-item" data-version="<?php echo esc_attr($additional_modification_version); ?>" data-end-signal="<?php echo $end_signal_enabled ? 'true' : 'false'; ?>" data-end-signal-src="<?php echo esc_url($practice_timer_end_signal_url); ?>" style="display: none;">
	<div class="exercise-item__info">
		<?php if ($title): ?>
		<h3><?php echo esc_html($title); ?> (<?php echo esc_html($additional_modification_label); ?>)</h3>
		<?php endif; ?>

		<?php if ($subtitle): ?>
		<h4><?php echo esc_html($subtitle); ?></h4>
		<?php endif; ?>

		<div class="exercise-switches">
			<div class="exercise-switches__item" data-target="main">
				<b><?php echo esc_html($execution_label); ?></b>
			</div>
			<?php foreach ($modification_tabs as $modification_tab): ?>
			<div class="exercise-switches__item<?php echo $modification_tab['version'] === $additional_modification_version ? ' active' : ''; ?>" data-target="<?php echo esc_attr($modification_tab['version']); ?>">
				<b><?php echo esc_html($modification_tab['label']); ?></b>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="exercise-item__info-details">
			<?php if (!empty($variant_matter)): ?>
			<?php foreach ($variant_matter as $item): ?>
			<div>
				<?php if (!empty($item['title'])): ?>
				<b><?php echo esc_html($item['title']); ?>:</b>
				<?php endif; ?>
				<?php if (!empty($item['description'])): ?>
				<?php
					echo function_exists('yoga_practice_format_rich_text')
						? yoga_practice_format_rich_text($item['description'])
						: wp_kses_post(wpautop((string) $item['description']));
				?>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
			<?php endif; ?>

			<?php if ($variant_timing !== array()): ?>
			<div>
				<b>Время:</b>
				<?php foreach ($variant_timing as $timing_idx => $value): ?>
				<?php if ($timing_idx > 0): ?>, <?php endif; ?>
				<span class="exercise-time-label"><?php echo esc_html(yoga_get_timing_label_short($timing_idx)); ?></span>
				<span class="exercise-time-value"><?php echo esc_html((string) intval($value)); ?></span>
				<span class="exercise-time-unit">мин.</span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ($variant_details): ?>
			<div class="exercise-detail-rich"><b>Доп. детали:</b> <?php
				echo function_exists('yoga_practice_format_rich_text')
					? yoga_practice_format_rich_text($variant_details)
					: wp_kses_post(wpautop((string) $variant_details));
			?></div>
			<?php endif; ?>
		</div>
	</div>

	<div class="exercise-item__media">
		<?php if ($variant_gallery !== array()): ?>
		<?php
			$variant_slider_class = 'exercise-slider';
			if (!$variant_show_timer) {
				$variant_slider_class .= ' exercise-slider_full';
			}
			if (count($variant_gallery) > 1) {
				$variant_slider_class .= ' exercise-slider_active';
			}
		?>
		<div class="<?php echo esc_attr($variant_slider_class); ?>">
			<?php foreach ($variant_gallery as $image): ?>
			<div class="exercise-slider__item">
				<a href="<?php echo esc_url($image['url']); ?>" class="exercise-slider__lightbox" data-fancybox="<?php echo esc_attr($variant_gallery_fancybox); ?>"<?php if (!empty($image['alt'])): ?> data-caption="<?php echo esc_attr($image['alt']); ?>"<?php endif; ?>>
					<span class="exercise-slider__media">
						<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
					</span>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ($variant_show_timer): ?>
		<div class="exercise-timer" data-version="<?php echo esc_attr($additional_modification_version); ?>">
			<div class="timer-main">
				<b class="timer-main__title">Таймер</b>
				<div class="timer-main__time">
					<div class="timer-main__time-bg">
						<img class="timer-main__time-bg-default" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/timer-bg-default.svg'); ?>" alt="">
						<img class="timer-main__time-bg-hover" src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/timer-bg-hover.svg'); ?>" alt="">
					</div>
					<span class="timer-display">0:00</span>
				</div>
			</div>
			<div class="timer-buttons">
				<?php $variant_timing_count = count($variant_timing); ?>
				<?php foreach ($variant_timing as $value): ?>
				<?php
					$button_class = $variant_timing_count === 2
						? 'btn'
						: ($variant_timing_count === 1 ? 'btn btn_big' : 'btn btn_min');
				?>
				<button type="button" class="<?php echo esc_attr($button_class); ?> timer-preset" data-duration="<?php echo esc_attr($value * 60); ?>">
					<span><?php echo esc_html((string) intval($value)); ?> мин.</span>
				</button>
				<?php endforeach; ?>
				<button type="button" class="btn timer-play-pause"><span>Старт</span></button>
				<button type="button" class="btn timer-reset"><span>Сброс</span></button>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<div class="player">
		<div class="player__plug">
			<?php if (!empty($variant_media_file) && $variant_media_type !== 'none'): ?>
			<div class="exercise-player" data-version="<?php echo esc_attr($additional_modification_version); ?>" data-media-type="<?php echo esc_attr($variant_media_type); ?>" data-media-src="<?php echo esc_url($variant_media_file['url'] ?? ''); ?>" data-allow-fullscreen="<?php echo $allow_fullscreen ? 'true' : 'false'; ?>" data-restrict-scrub="<?php echo $restrict_scrub ? 'true' : 'false'; ?>" data-auto-play="<?php echo $auto_play ? 'true' : 'false'; ?>">
				<?php if ($variant_media_type === 'audio'): ?>
				<audio controls><source src="<?php echo esc_url($variant_media_file['url'] ?? ''); ?>" type="audio/mp3"></audio>
				<?php elseif ($variant_media_type === 'video'): ?>
				<video controls playsinline><source src="<?php echo esc_url($variant_media_file['url'] ?? ''); ?>" type="video/mp4">Ваш браузер не поддерживает видео тег.</video>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($variant_content): ?>
	<div class="exercise-content">
		<?php
			echo function_exists('yoga_practice_format_rich_text')
				? yoga_practice_format_rich_text($variant_content, true)
				: apply_filters('the_content', $variant_content);
		?>
	</div>
	<?php endif; ?>
</div>
