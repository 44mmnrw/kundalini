<?php
	/**
		* Template Part: Anchor 05 - Техника выполнения
	*/
	
	if (!isset($section['steps']) || empty($section['steps'])) {
		return;
	}
	
	$steps = $section['steps'];
?>

<span class="praktika-menu-anchor" id="anchor_05"></span>
<?php foreach ($steps as $index => $step): ?>
<?php if (isset($step['section_title']) && $step['section_title']): ?>
<h3 class="mtb"><?php echo esc_html($step['section_title']); ?></h3>
<?php endif; ?>

<?php if (isset($step['exercise_items']) && !empty($step['exercise_items'])): ?>

<?php foreach ($step['exercise_items'] as $exercise): ?>
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
	$gallery = $exercise['gallery'] ?? [];
	$gallery_mod = $exercise['gallery_mod'] ?? [];
	$content =  $exercise['content'] ?? []; // Контент будет через WYSIWYG
	$content_mod =  $exercise['content_mod'] ?? []; // Контент модификации
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

<div class="praktika-exercise" data-exercise-id="<?php echo esc_attr($index); ?>">
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
                    <?php echo esc_html($item['description']); ?>
                    <?php endif; ?>
				</div>
                <?php endforeach; ?>
                <?php endif; ?>
                
				<?php if (!empty($timing)): ?>
				<div>
					<b>Время:</b> 
					<?php foreach ($timing as $index => $value): ?>
					<?php 
						$label = '';
						if ($index === 0) $label = 'Мин';
						elseif ($index === 1) $label = 'Сред';
						elseif ($index === 2) $label = 'Макс';
						else $label = 'Доп';
					?>
					<?php if ($index > 0): ?>, <?php endif; ?>
					<strong><?php echo esc_html($label); ?></strong> <?php echo esc_html($value); ?>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
                
                <?php if ($details): ?>
                <div><b>Доп. детали:</b> <?php echo esc_html($details); ?></div>
                <?php endif; ?>
			</div>
		</div>
        
        <div class="exercise-item__media">
            <?php if (!empty($gallery)): ?>
            <?php 
                $slider_class = 'exercise-slider';
                // Добавляем класс _active если изображений больше одного
                if (count($gallery) > 1) {
                    $slider_class .= ' exercise-slider_active';
				}
			?>
            
            <div class="<?php echo esc_attr($slider_class); ?>">
                <?php foreach ($gallery as $image): ?>
                <?php if (isset($image['url'])): ?>
                <div class="exercise-slider__item">
                    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
				</div>
                <?php endif; ?>
                <?php endforeach; ?>
			</div>
            <?php endif; ?>
            
            <div class="exercise-timer" data-version="main">
                <div class="timer-main">
                    <b class="timer-main__title">Таймер</b>
                    <div class="timer-main__time">
                        <div class="timer-main__time-bg">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/timer-bg.png" alt="Таймер">
						</div>
                        <span class="timer-display">
                            <?php 
                                // Преобразуем строку в число
                                $duration_int = intval($duration);
                                $minutes = floor($duration_int / 60);
                                $seconds = $duration_int % 60;
                                printf('%02d:%02d', $minutes, $seconds);
							?>
						</span>
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
						<span><?php echo esc_html($value); ?> мин</span>
					</button>
					<?php endforeach; ?>
					<?php else: ?>
					<!-- Дефолтные значения, если timing не заполнен -->
					<button type="button" class="btn btn_min timer-preset" data-duration="180">
						<span>3 мин</span>
					</button>
					<button type="button" class="btn btn_min timer-preset" data-duration="420">
						<span>7 мин</span>
					</button>
					<button type="button" class="btn btn_min timer-preset" data-duration="660">
						<span>11 мин</span>
					</button>
					<?php endif; ?>
					
					<button type="button" class="btn timer-play-pause">
						<span>Старт</span>
					</button>
					<button type="button" class="btn timer-reset">
						<span>Сброс</span>
					</button>
				</div>
			</div>
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
            <?php echo apply_filters('the_content', $content); ?>
		</div>
        <?php endif; ?>
	</div>
    
    <!-- Модифицированная версия -->
    <?php if ($has_modifications): ?>
    <div class="exercise-item" data-version="mod" style="display: none;">
        <div class="exercise-item__info">
            <?php if ($title): ?>
            <h4><?php echo esc_html($title); ?> (Модификация)</h4>
            <?php endif; ?>
            
            <?php if ($subtitle): ?>
            <p class="exercise-subtitle"><?php echo esc_html($subtitle); ?></p>
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
                    <?php echo esc_html($item['description']); ?>
                    <?php endif; ?>
				</div>
                <?php endforeach; ?>
                <?php endif; ?>
                
				<?php if (!empty($timing_mod)): ?>
				<div>
					<b>Время:</b> 
					<?php foreach ($timing_mod as $index => $value): ?>
					<?php 
						$label = '';
						if ($index === 0) $label = 'Мин';
						elseif ($index === 1) $label = 'Сред';
						elseif ($index === 2) $label = 'Макс';
						else $label = 'Доп';
					?>
					<?php if ($index > 0): ?>, <?php endif; ?>
					<strong><?php echo esc_html($label); ?></strong> <?php echo esc_html($value); ?>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
                
                <?php if ($details): ?>
                <div><b>Доп. детали:</b> <?php echo esc_html($details); ?></div>
                <?php endif; ?>
			</div>
		</div>
        
        <div class="exercise-item__media">
            <?php if (!empty($gallery_mod)): ?>
            <?php 
                $slider_class_mod = 'exercise-slider';
                if (count($gallery_mod) > 1) {
                    $slider_class_mod .= ' exercise-slider_active';
				}
				//var_dump($gallery_mod);
			?>
            <div class="<?php echo esc_attr($slider_class_mod); ?>">
                <?php foreach ($gallery_mod as $image): ?>
                <?php if (isset($image['url'])): ?>
                <div class="exercise-slider__item">
                    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
				</div>
                <?php endif; ?>
                <?php endforeach; ?>
			</div>
            <?php endif; ?>
            
            
            
            <div class="exercise-timer" data-version="mod">
                <div class="timer-main">
                    <b class="timer-main__title">Таймер</b>
                    <div class="timer-main__time">
                        <div class="timer-main__time-bg">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/timer-bg.png" alt="Таймер">
						</div>
                        <span class="timer-display">
                            <?php 
                                $duration_mod_int = intval($duration_mod);
                                $minutes = floor($duration_mod_int / 60);
                                $seconds = $duration_mod_int % 60;
                                printf('%02d:%02d', $minutes, $seconds);
							?>
						</span>
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
						<span><?php echo esc_html($value); ?> мин</span>
					</button>
					<?php endforeach; ?>
					<?php else: ?>
					<!-- Дефолтные значения, если timing не заполнен -->
					<button type="button" class="btn btn_min timer-preset" data-duration="180">
						<span>3 мин</span>
					</button>
					<button type="button" class="btn btn_min timer-preset" data-duration="420">
						<span>7 мин</span>
					</button>
					<button type="button" class="btn btn_min timer-preset" data-duration="660">
						<span>11 мин</span>
					</button>
					<?php endif; ?>
					
					<button type="button" class="btn timer-play-pause">
						<span>Старт</span>
					</button>
					<button type="button" class="btn timer-reset">
						<span>Сброс</span>
					</button>
				</div>
			</div>
			
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
            <?php echo apply_filters('the_content', $content_mod); ?>
		</div>
        <?php endif; ?>
	</div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>
