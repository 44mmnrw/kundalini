<?php
/**
 * Переиспользуемый шаблонный блок: anchor 05.
 *
 * @package Yoga
 */
	if (!isset($section['steps']) || empty($section['steps'])) {
		return;
	}

	$anchor_id = isset($anchor_id) && $anchor_id !== ''
		? (string) $anchor_id
		: 'anchor_05';

	$steps = $section['steps'];

	if (!function_exists('yoga_get_timing_label_short')) {
		function yoga_get_timing_label_short(int $index): string {
			if ($index === 0) {
				return 'мин.';
			}
			if ($index === 1) {
				return 'сред.';
			}
			if ($index === 2) {
				return 'макс.';
			}
			return 'доп.';
		}
	}
?>
<?php
	if (!function_exists('yoga_normalize_practice_exercise_gallery')) {



		function yoga_normalize_practice_exercise_gallery($gallery): array {
			if (!is_array($gallery)) {
				return array();
			}

			$images = array();
			foreach ($gallery as $image) {
				$url = '';
				$alt = '';

				if (is_array($image)) {
					$image_id = (int) ($image['ID'] ?? $image['id'] ?? 0);
					if ($image_id > 0) {
						$url = (string) wp_get_attachment_image_url($image_id, 'full');
					} else {
						$url = trim((string) ($image['url'] ?? ''));
					}
					$alt = (string) ($image['alt'] ?? '');

					if ($image_id > 0 && $alt === '') {
						$alt = (string) get_post_meta($image_id, '_wp_attachment_image_alt', true);
					}
				} elseif (is_numeric($image)) {
					$image_id = (int) $image;
					if ($image_id > 0) {
						$url = (string) wp_get_attachment_image_url($image_id, 'full');
						$alt = (string) get_post_meta($image_id, '_wp_attachment_image_alt', true);
					}
				}

				if ($url === '') {
					continue;
				}

				$images[] = array(
					'url' => $url,
					'alt' => $alt,
				);
			}

			return $images;
		}
	}

	if (!function_exists('yoga_get_practice_timer_end_signal_url')) {
		function yoga_get_practice_timer_end_signal_url(): string {
			if (!function_exists('get_field')) {
				return '';
			}

			$file = get_field('practice_timer_end_signal_file', 'option');
			if (is_array($file)) {
				if (!empty($file['url'])) {
					return (string) $file['url'];
				}

				$file_id = (int) ($file['ID'] ?? $file['id'] ?? 0);
				return $file_id > 0 ? (string) wp_get_attachment_url($file_id) : '';
			}

			if (is_numeric($file)) {
				return (string) wp_get_attachment_url((int) $file);
			}

			return is_string($file) ? trim($file) : '';
		}
	}

	$practice_timer_end_signal_url = yoga_get_practice_timer_end_signal_url();
?>

<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<?php foreach ($steps as $index => $step): ?>
<?php if (isset($step['section_title']) && $step['section_title']): ?>
<h3 class="mtb"><?php echo esc_html($step['section_title']); ?></h3>
<?php endif; ?>

<?php if (isset($step['exercise_items']) && !empty($step['exercise_items'])): ?>

<?php foreach ($step['exercise_items'] as $ex_idx => $exercise): ?>
<?php
	$uses_unified_modification_schema = ($exercise['execution_name'] ?? '') === '__unified__'
		|| !empty($exercise['modifications']);
	$legacy_has_modifications = !$uses_unified_modification_schema && !empty($exercise['has_modifications']);
	$execution_label = 'Основная модификация';
	$modification_name = trim((string) ($exercise['modification_name'] ?? ''));
	$modification_label = $modification_name !== '' ? $modification_name : 'Модификация 1';
	$title = $exercise['title'] ?? '';
	$subtitle = $exercise['subtitle'] ?? '';
	$matter = $exercise['matter'] ?? '';
	$details = $exercise['details'] ?? '';
	$matter_mod = !empty($exercise['matter_mod']) && is_array($exercise['matter_mod']) ? $exercise['matter_mod'] : array();
	$details_mod = trim((string) ($exercise['details_mod'] ?? ''));
	$timing = $exercise['timing'] ?? [];
	$timing_mod = $exercise['timing_mod'] ?? [];
	$media_type = $exercise['media_type'] ?? 'none';
	$media_file = $exercise['media_file'] ?? [];
	$media_type_mod = $exercise['media_type_mod'] ?? 'none';
	$media_file_mod = $exercise['media_file_mod'] ?? [];
	$duration = $exercise['duration'] ?? 180;
	$duration_mod = $exercise['duration_mod'] ?? 180;
	$gallery = yoga_normalize_practice_exercise_gallery($exercise['gallery'] ?? array());
	$gallery_mod = yoga_normalize_practice_exercise_gallery($exercise['gallery_mod'] ?? array());
	$gallery_fancybox = 'practice-exercise-gallery-' . $index . '-' . $ex_idx . '-main';
	$gallery_mod_fancybox = 'practice-exercise-gallery-' . $index . '-' . $ex_idx . '-mod';
	$show_timer = !empty($timing);
	$show_timer_mod = !empty($timing_mod);
	$content =  $exercise['content'] ?? [];
	$content_mod =  $exercise['content_mod'] ?? [];
	$legacy_additional_modification_rows = !empty($exercise['additional_modifications']) && is_array($exercise['additional_modifications'])
		? $exercise['additional_modifications']
		: array();
	$modification_row_has_content = static function ($row): bool {
		if (!is_array($row)) {
			return false;
		}

		foreach ($row as $key => $value) {
			if ($key === 'media_type' && in_array($value, array('', 'none', null), true)) {
				continue;
			}
			if (is_array($value) && $value === array()) {
				continue;
			}
			if (!in_array($value, array('', false, null), true)) {
				return true;
			}
		}

		return false;
	};
	$unified_modification_rows = !empty($exercise['modifications']) && is_array($exercise['modifications'])
		? array_values(array_filter($exercise['modifications'], $modification_row_has_content))
		: array();
	$additional_modification_rows = $uses_unified_modification_schema
		? array()
		: array_values(array_filter($legacy_additional_modification_rows, $modification_row_has_content));

	if ($unified_modification_rows !== array()) {
		$first_modification = array_shift($unified_modification_rows);
		$modification_name = trim((string) ($first_modification['modification_name'] ?? ''));
		$modification_label = $modification_name !== '' ? $modification_name : 'Модификация 1';
		$matter_mod = !empty($first_modification['matter']) && is_array($first_modification['matter']) ? $first_modification['matter'] : array();
		$details_mod = trim((string) ($first_modification['details'] ?? ''));
		$timing_mod = !empty($first_modification['timing']) && is_array($first_modification['timing']) ? $first_modification['timing'] : array();
		$media_type_mod = (string) ($first_modification['media_type'] ?? 'none');
		$media_file_mod = $first_modification['media_file'] ?? array();
		$duration_mod = $first_modification['duration'] ?? 180;
		$gallery_mod = yoga_normalize_practice_exercise_gallery($first_modification['gallery'] ?? array());
		$content_mod = $first_modification['content'] ?? '';
		$additional_modification_rows = $unified_modification_rows;
	}

	$has_modifications = $unified_modification_rows !== array()
		|| isset($first_modification)
		|| $legacy_has_modifications
		|| $additional_modification_rows !== array();
	$modification_tabs = array();
	if ($has_modifications) {
		$modification_tabs[] = array(
			'version' => 'mod',
			'label'   => $modification_label,
			'data'    => null,
		);

		foreach ($additional_modification_rows as $additional_index => $additional_modification) {
			if (!is_array($additional_modification)) {
				continue;
			}

			$additional_label = trim((string) ($additional_modification['modification_name'] ?? ''));
			$modification_tabs[] = array(
				'version' => 'mod-' . ((int) $additional_index + 2),
				'label'   => $additional_label !== '' ? $additional_label : 'Модификация ' . ((int) $additional_index + 2),
				'data'    => $additional_modification,
			);
		}
	}
	$allow_fullscreen = true;
	$restrict_scrub = false;
	$auto_play = true;
	$end_signal_enabled = !empty($exercise['signal_v_koncze']) && $practice_timer_end_signal_url !== '';

	$media_source = '';
	if ($media_type !== 'none' && !empty($media_file) && isset($media_file['url'])) {
		$media_source = $media_file['url'];
	}

	$media_source_mod = '';
	if ($has_modifications && $media_type_mod !== 'none' && !empty($media_file_mod) && isset($media_file_mod['url'])) {
		$media_source_mod = $media_file_mod['url'];
	}
?>

<div class="praktika-exercise" data-exercise-id="<?php echo esc_attr($index . '-' . $ex_idx); ?>">

    <div class="exercise-item active" data-version="main" data-end-signal="<?php echo $end_signal_enabled ? 'true' : 'false'; ?>" data-end-signal-src="<?php echo esc_url($practice_timer_end_signal_url); ?>">
        <div class="exercise-item__info">
            <?php if ($title): ?>
            <h3><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <?php if ($subtitle): ?>
            <h4><?php echo esc_html($subtitle); ?></h4>
            <?php endif; ?>

            <?php if ($has_modifications): ?>
            <div class="exercise-switches">
                <div class="exercise-switches__item active" data-target="main">
                    <b><?php echo esc_html($execution_label); ?></b>
				</div>
				<?php foreach ($modification_tabs as $modification_tab): ?>
				<div class="exercise-switches__item" data-target="<?php echo esc_attr($modification_tab['version']); ?>">
					<b><?php echo esc_html($modification_tab['label']); ?></b>
				</div>
				<?php endforeach; ?>
			</div>
            <?php endif; ?>

            <div class="exercise-item__info-details">
                <?php if (!empty($matter)): ?>
                <?php foreach ($matter as $item): ?>
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

				<?php if (!empty($timing)): ?>
				<div>
					<b>Время:</b>
					<?php foreach ($timing as $timing_idx => $value): ?>
					<?php if ($timing_idx > 0): ?>, <?php endif; ?>
					<span class="exercise-time-label"><?php echo esc_html(yoga_get_timing_label_short($timing_idx)); ?></span>
					<span class="exercise-time-value"><?php echo esc_html((string) intval($value)); ?></span>
					<span class="exercise-time-unit">мин.</span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

                <?php if ($details): ?>
                <div class="exercise-detail-rich"><?php
					echo function_exists('yoga_practice_format_detail_text')
						? yoga_practice_format_detail_text($details)
						: wp_kses_post(wpautop((string) $details));
				?></div>
                <?php endif; ?>
			</div>
		</div>

        <div class="exercise-item__media">
            <?php if (!empty($gallery)): ?>
            <?php
                $slider_class = 'exercise-slider';
                if (!$show_timer) {
                    $slider_class .= ' exercise-slider_full';
				}

                if (count($gallery) > 1) {
                    $slider_class .= ' exercise-slider_active';
				}
			?>

            <div class="<?php echo esc_attr($slider_class); ?>">
                <?php foreach ($gallery as $image): ?>
                <div class="exercise-slider__item">
                    <a
						href="<?php echo esc_url($image['url']); ?>"
						class="exercise-slider__lightbox"
						data-fancybox="<?php echo esc_attr($gallery_fancybox); ?>"
						<?php if (!empty($image['alt'])): ?>data-caption="<?php echo esc_attr($image['alt']); ?>"<?php endif; ?>
					>
						<span class="exercise-slider__media">
							<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
						</span>
					</a>
				</div>
                <?php endforeach; ?>
			</div>
            <?php endif; ?>

            <?php if ($show_timer): ?>
            <div class="exercise-timer" data-version="main">
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
					<?php if (!empty($timing)): ?>
					<?php
						$timing_count = count($timing);
						foreach ($timing as $value):
						if($timing_count == 2){
							$button_class = 'btn';
							}else{
							$button_class = ($timing_count == 1) ? 'btn btn_big' : 'btn btn_min';
						}
					?>
					<button type="button" class="<?php echo esc_attr($button_class); ?> timer-preset" data-duration="<?php echo esc_attr($value*60); ?>">
						<span><?php echo esc_html((string) intval($value)); ?> мин.</span>
					</button>
					<?php endforeach; ?>
					<?php endif; ?>

					<button type="button" class="btn timer-play-pause">
						<span>Старт</span>
					</button>
					<button type="button" class="btn timer-reset">
						<span>Сброс</span>
					</button>
				</div>
			</div>
            <?php endif; ?>
		</div>

        <div class="player">
            <div class="player__plug">
                <?php if ($media_file && $media_type !== 'none'): ?>
                <div class="exercise-player"
                data-version="main"
                data-media-type="<?php echo esc_attr($media_type); ?>"
                data-media-src="<?php echo esc_url($media_file['url'] ?? ''); ?>"
                data-allow-fullscreen="<?php echo $allow_fullscreen ? 'true' : 'false'; ?>"
                data-restrict-scrub="<?php echo $restrict_scrub ? 'true' : 'false'; ?>"
                data-auto-play="<?php echo $auto_play ? 'true' : 'false'; ?>">

                    <?php if ($media_type === 'audio'): ?>
                    <audio controls>
                        <source src="<?php echo esc_url($media_file['url'] ?? ''); ?>" type="audio/mp3">
					</audio>
                    <?php elseif ($media_type === 'video'): ?>
                    <video controls playsinline>
                        <source src="<?php echo esc_url($media_file['url'] ?? ''); ?>" type="video/mp4">
                        Ваш браузер не поддерживает видео тег.
					</video>
                    <?php endif; ?>
				</div>
                <?php endif; ?>
			</div>
		</div>


        <?php if ($content): ?>
        <div class="exercise-content">
            <?php
				echo function_exists('yoga_practice_format_rich_text')
					? yoga_practice_format_rich_text($content, true)
					: apply_filters('the_content', $content);
			?>
		</div>
        <?php endif; ?>
	</div>


    <?php if ($has_modifications): ?>
    <div class="exercise-item" data-version="mod" data-end-signal="<?php echo $end_signal_enabled ? 'true' : 'false'; ?>" data-end-signal-src="<?php echo esc_url($practice_timer_end_signal_url); ?>" style="display: none;">
        <div class="exercise-item__info">
            <?php if ($title): ?>
            <h3><?php echo esc_html($title); ?> (<?php echo esc_html($modification_label); ?>)</h3>
            <?php endif; ?>

            <?php if ($subtitle): ?>
            <h4><?php echo esc_html($subtitle); ?></h4>
            <?php endif; ?>

            <div class="exercise-switches">
                <div class="exercise-switches__item" data-target="main">
                    <b><?php echo esc_html($execution_label); ?></b>
				</div>
				<?php foreach ($modification_tabs as $modification_tab): ?>
				<div class="exercise-switches__item<?php echo $modification_tab['version'] === 'mod' ? ' active' : ''; ?>" data-target="<?php echo esc_attr($modification_tab['version']); ?>">
					<b><?php echo esc_html($modification_tab['label']); ?></b>
				</div>
				<?php endforeach; ?>
			</div>

            <div class="exercise-item__info-details">
                <?php if (!empty($matter_mod)): ?>
                <?php foreach ($matter_mod as $item): ?>
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

				<?php if (!empty($timing_mod)): ?>
				<div>
					<b>Время:</b>
					<?php foreach ($timing_mod as $timing_idx => $value): ?>
					<?php if ($timing_idx > 0): ?>, <?php endif; ?>
					<span class="exercise-time-label"><?php echo esc_html(yoga_get_timing_label_short($timing_idx)); ?></span>
					<span class="exercise-time-value"><?php echo esc_html((string) intval($value)); ?></span>
					<span class="exercise-time-unit">мин.</span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

                <?php if ($details_mod): ?>
                <div class="exercise-detail-rich"><?php
					echo function_exists('yoga_practice_format_detail_text')
						? yoga_practice_format_detail_text($details_mod)
						: wp_kses_post(wpautop((string) $details_mod));
				?></div>
                <?php endif; ?>
			</div>
		</div>

        <div class="exercise-item__media">
            <?php if (!empty($gallery_mod)): ?>
            <?php
                $slider_class_mod = 'exercise-slider';
                if (!$show_timer_mod) {
                    $slider_class_mod .= ' exercise-slider_full';
				}
                if (count($gallery_mod) > 1) {
                    $slider_class_mod .= ' exercise-slider_active';
				}

			?>
            <div class="<?php echo esc_attr($slider_class_mod); ?>">
                <?php foreach ($gallery_mod as $image): ?>
                <div class="exercise-slider__item">
                    <a
						href="<?php echo esc_url($image['url']); ?>"
						class="exercise-slider__lightbox"
						data-fancybox="<?php echo esc_attr($gallery_mod_fancybox); ?>"
						<?php if (!empty($image['alt'])): ?>data-caption="<?php echo esc_attr($image['alt']); ?>"<?php endif; ?>
					>
						<span class="exercise-slider__media">
							<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
						</span>
					</a>
				</div>
                <?php endforeach; ?>
			</div>
            <?php endif; ?>



            <?php if ($show_timer_mod): ?>
            <div class="exercise-timer" data-version="mod">
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
					<?php if (!empty($timing_mod)): ?>
					<?php
						$timing_count = count($timing_mod);
						foreach ($timing_mod as $value):
						if($timing_count == 2){
							$button_class = 'btn';
							}else{
							$button_class = ($timing_count == 1) ? 'btn btn_big' : 'btn btn_min';
						}
					?>
					<button type="button" class="<?php echo esc_attr($button_class); ?> timer-preset" data-duration="<?php echo esc_attr($value*60); ?>">
						<span><?php echo esc_html((string) intval($value)); ?> мин.</span>
					</button>
					<?php endforeach; ?>
					<?php endif; ?>

					<button type="button" class="btn timer-play-pause">
						<span>Старт</span>
					</button>
					<button type="button" class="btn timer-reset">
						<span>Сброс</span>
					</button>
				</div>
			</div>
            <?php endif; ?>

		</div>

        <div class="player">
				<div class="player__plug">
					<?php if ($media_file_mod && $media_type_mod !== 'none'): ?>
					<div class="exercise-player"
					data-version="mod"
					data-media-type="<?php echo esc_attr($media_type_mod); ?>"
					data-media-src="<?php echo esc_url($media_file_mod['url'] ?? ''); ?>"
					data-allow-fullscreen="<?php echo $allow_fullscreen ? 'true' : 'false'; ?>"
					data-restrict-scrub="<?php echo $restrict_scrub ? 'true' : 'false'; ?>"
					data-auto-play="<?php echo $auto_play ? 'true' : 'false'; ?>">

						<?php if ($media_type_mod === 'audio'): ?>
						<audio controls>
							<source src="<?php echo esc_url($media_file_mod['url'] ?? ''); ?>" type="audio/mp3">
						</audio>
						<?php elseif ($media_type_mod === 'video'): ?>
						<video controls playsinline>
							<source src="<?php echo esc_url($media_file_mod['url'] ?? ''); ?>" type="video/mp4">
							Ваш браузер не поддерживает видео тег.
						</video>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>

        <?php if ($content_mod): ?>
        <div class="exercise-content">
            <?php
				echo function_exists('yoga_practice_format_rich_text')
					? yoga_practice_format_rich_text($content_mod, true)
					: apply_filters('the_content', $content_mod);
			?>
		</div>
        <?php endif; ?>
	</div>
    <?php endif; ?>

	<?php foreach ($modification_tabs as $modification_tab): ?>
	<?php if ($modification_tab['version'] === 'mod' || !is_array($modification_tab['data'])) { continue; } ?>
	<?php
		$additional_modification = $modification_tab['data'];
		$additional_modification_version = (string) $modification_tab['version'];
		$additional_modification_label = (string) $modification_tab['label'];
		include get_template_directory() . '/template-parts/praktika-info/exercise-additional-modification.php';
	?>
	<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>
