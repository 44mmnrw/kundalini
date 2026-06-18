<?php
	/**
	 * Шаблон: якорь 05 — техника выполнения
	 *
	 * @var string $anchor_id
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
		/**
		 * @return array<int, array{url: string, alt: string}>
		 */
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
?>

<span class="praktika-menu-anchor js-praktika-section-marker" id="<?php echo esc_attr($anchor_id); ?>" data-section-key="<?php echo esc_attr(isset($section_key) ? (string) $section_key : ''); ?>"></span>
<?php foreach ($steps as $index => $step): ?>
<?php if (isset($step['section_title']) && $step['section_title']): ?>
<h3 class="mtb"><?php echo esc_html($step['section_title']); ?></h3>
<?php endif; ?>

<?php if (isset($step['exercise_items']) && !empty($step['exercise_items'])): ?>

<?php foreach ($step['exercise_items'] as $ex_idx => $exercise): ?>
<?php
	$has_modifications = $exercise['has_modifications'] ?? false;
	$title = $exercise['title'] ?? '';
	$subtitle = $exercise['subtitle'] ?? '';
	$matter = $exercise['matter'] ?? '';
	$details = $exercise['details'] ?? '';
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
	$content =  $exercise['content'] ?? []; // Основной контент из поля WYSIWYG
	$content_mod =  $exercise['content_mod'] ?? []; // Контент модификации (WYSIWYG)
	$allow_fullscreen = true;
	$restrict_scrub = false;
	$auto_play = true;
	
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
    <!-- Основная версия -->
    <div class="exercise-item active" data-version="main">
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
                    <b>Основная</b>
				</div>
                <div class="exercise-switches__item" data-target="mod">
                    <b>Модификация</b>
				</div>
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
                <div class="exercise-detail-rich"><b>Доп. детали:</b> <?php
					echo function_exists('yoga_practice_format_rich_text')
						? yoga_practice_format_rich_text($details)
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
                // Добавляем класс _active, если изображений больше одного
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
						data-yoga-copy-allow="1"
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
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/timer-bg.png" alt="Таймер">
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
    
    <!-- Модифицированная версия -->
    <?php if ($has_modifications): ?>
    <div class="exercise-item" data-version="mod" style="display: none;">
        <div class="exercise-item__info">
            <?php if ($title): ?>
            <h3><?php echo esc_html($title); ?> (Модификация)</h3>
            <?php endif; ?>
            
            <?php if ($subtitle): ?>
            <h4><?php echo esc_html($subtitle); ?></h4>
            <?php endif; ?>
            
            <div class="exercise-switches">
                <div class="exercise-switches__item" data-target="main">
                    <b>Основная</b>
				</div>
                <div class="exercise-switches__item active" data-target="mod">
                    <b>Модификация</b>
				</div>
			</div>
            
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
                
                <?php if ($details): ?>
                <div class="exercise-detail-rich"><b>Доп. детали:</b> <?php
					echo function_exists('yoga_practice_format_rich_text')
						? yoga_practice_format_rich_text($details)
						: wp_kses_post(wpautop((string) $details));
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
				//var_dump($gallery_mod);
			?>
            <div class="<?php echo esc_attr($slider_class_mod); ?>">
                <?php foreach ($gallery_mod as $image): ?>
                <div class="exercise-slider__item">
                    <a
						href="<?php echo esc_url($image['url']); ?>"
						class="exercise-slider__lightbox"
						data-fancybox="<?php echo esc_attr($gallery_mod_fancybox); ?>"
						data-yoga-copy-allow="1"
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
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/timer-bg.png" alt="Таймер">
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
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>
