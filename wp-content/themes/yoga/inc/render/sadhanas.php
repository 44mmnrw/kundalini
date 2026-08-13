<?php
/**
 * Sadhana account view helpers.
 *
 * @package Yoga
 */

if (!function_exists('yoga_get_sadhana_empty_layout')) {
	function yoga_get_sadhana_empty_layout(string $type, string $library_url, string $sprite_url): void {
		$is_completed = $type === 'completed';
		$title = $is_completed
			? 'У вас пока нет завершённых садхан'
			: 'У вас пока нет активных садхан';
		$description = $is_completed
			? 'Возьмите любую крию из каталога и нажмите «Взять в садхану» или завершите ранее начатую'
			: 'Возьмите любую крию из каталога и нажмите «Взять в садхану»';
		?>
		<div class="lk-sadhanas-empty" role="status">
			<div class="lk-sadhanas-empty__main">
				<span class="lk-sadhanas-empty__icon" aria-hidden="true">
					<svg><use href="<?php echo esc_url($sprite_url); ?>#sadhana-empty"></use></svg>
				</span>
				<div class="lk-sadhanas-empty__copy">
					<h3><?php echo esc_html($title); ?></h3>
					<p><?php echo esc_html($description); ?></p>
				</div>
			</div>
			<a class="lk-sadhanas-empty__button" href="<?php echo esc_url($library_url); ?>">
				<span>В библиотеку практик</span>
				<svg aria-hidden="true"><use href="<?php echo esc_url($sprite_url); ?>#arrow45-green"></use></svg>
			</a>
		</div>
		<?php
	}
}

if (!function_exists('yoga_sadhana_format_date')) {
	function yoga_sadhana_format_date(string $date): string {
		if ($date === '') {
			return '';
		}
		$date_object = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
		return $date_object instanceof DateTimeImmutable ? $date_object->format('d.m.Y') : '';
	}
}

if (!function_exists('yoga_render_practice_sadhana_counter')) {
	function yoga_render_practice_sadhana_counter(array $sadhana, int $practice_id, string $location = 'top'): void {
		$user_id = absint($sadhana['user_id'] ?? get_current_user_id());
		$completed_days = absint($sadhana['completed_days'] ?? 0);
		$total_days = max(1, absint($sadhana['target_days'] ?? 1));
		$progress = min(100, ($completed_days / $total_days) * 100);
		$next_day_at = $user_id > 0
			? (new DateTimeImmutable('tomorrow', yoga_sadhana_user_timezone($user_id)))->getTimestamp() * 1000
			: 0;
		$marked_today = $user_id > 0
			&& !empty($sadhana['last_marked_on'])
			&& (string) $sadhana['last_marked_on'] >= yoga_sadhana_today($user_id);
		?>
		<div class="praktika-sadhana-counter praktika-sadhana-counter--<?php echo esc_attr($location === 'bottom' ? 'bottom' : 'top'); ?>" data-practice-id="<?php echo esc_attr((string) $practice_id); ?>" data-sadhana-id="<?php echo esc_attr((string) absint($sadhana['id'] ?? 0)); ?>" data-completed-days="<?php echo esc_attr((string) $completed_days); ?>" data-total-days="<?php echo esc_attr((string) $total_days); ?>" data-marked-today="<?php echo $marked_today ? '1' : '0'; ?>" data-next-day-at="<?php echo esc_attr((string) $next_day_at); ?>">
			<div class="praktika-sadhana-counter__content">
				<div class="praktika-sadhana-counter__head">
					<div class="praktika-sadhana-counter__title">
						<svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#sadhana-calendar'); ?>"></use></svg>
						<strong aria-live="polite"><span class="praktika-sadhana-counter__completed"><?php echo esc_html((string) $completed_days); ?></span> <?php esc_html_e('из', 'yoga'); ?> <span class="praktika-sadhana-counter__total"><?php echo esc_html((string) $total_days); ?></span> <?php esc_html_e('дней', 'yoga'); ?></strong>
					</div>
					<p><?php esc_html_e('Садхана началась. Можно отметить сегодняшний день после практики.', 'yoga'); ?></p>
				</div>
				<div class="praktika-sadhana-counter__actions">
					<button class="praktika-sadhana-counter__mark<?php echo $marked_today ? ' is-marked' : ''; ?>" type="button"<?php echo $marked_today ? ' disabled' : ''; ?> aria-label="<?php echo $marked_today ? esc_attr(sprintf(__('Садхана. День %1$d из %2$d отмечен', 'yoga'), $completed_days, $total_days)) : esc_attr__('Отметить новый день', 'yoga'); ?>">
						<span class="praktika-sadhana-counter__mark-default"><?php esc_html_e('Отметить новый день', 'yoga'); ?></span>
						<span class="praktika-sadhana-counter__mark-state" aria-hidden="true">
							<span class="praktika-sadhana-counter__mark-label"><?php esc_html_e('САДХАНА', 'yoga'); ?></span>
							<span><?php esc_html_e('День', 'yoga'); ?> <span class="praktika-sadhana-counter__mark-completed"><?php echo esc_html((string) $completed_days); ?></span> <?php esc_html_e('из', 'yoga'); ?> <span class="praktika-sadhana-counter__mark-total"><?php echo esc_html((string) $total_days); ?></span></span>
							<svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#tariff-check'); ?>"></use></svg>
						</span>
					</button>
					<button class="praktika-sadhana-counter__reset yoga-sadhana-reset-trigger" type="button" aria-haspopup="dialog" aria-controls="yoga-sadhana-reset-modal"><?php esc_html_e('Сбросить прогресс', 'yoga'); ?></button>
				</div>
			</div>
			<div class="praktika-sadhana-counter__progress" aria-hidden="true"><span style="width: <?php echo esc_attr((string) $progress); ?>%"></span></div>
		</div>
		<?php
	}
}

if (!function_exists('yoga_get_practice_sadhana_counter_html')) {
	function yoga_get_practice_sadhana_counter_html(array $sadhana): string {
		ob_start();
		yoga_render_practice_sadhana_counter($sadhana, absint($sadhana['practice_id'] ?? 0));
		return (string) ob_get_clean();
	}
}

if (!function_exists('yoga_render_sadhana_card')) {
	function yoga_render_sadhana_card(array $sadhana, string $type, string $sprite_url = ''): void {
		$is_completed = $type === 'completed';
		$practice_id = absint($sadhana['practice_id'] ?? 0);
		$title = get_the_title($practice_id) ?: __('Удалённая практика', 'yoga');
		$practice_url = get_permalink($practice_id);
		$practice_url = is_string($practice_url) && $practice_url !== '' ? $practice_url : '#';
		$image = function_exists('yoga_get_practice_card_image_url') ? yoga_get_practice_card_image_url($practice_id, 'large') : '';
		if ($image === '') {
			$image = get_template_directory_uri() . '/assets/img/kriya-img_01.png';
		}
		$completed_days = absint($sadhana['completed_days'] ?? 0);
		$total_days = max(1, absint($sadhana['target_days'] ?? 1));
		$progress = $is_completed ? 100 : min(100, (int) floor(($completed_days / $total_days) * 100));
		$started = yoga_sadhana_format_date((string) ($sadhana['started_on'] ?? ''));
		$completed = yoga_sadhana_format_date((string) ($sadhana['completed_on'] ?? ''));
		?>
		<article class="lk-sadhana-card<?php echo $is_completed ? ' is-completed' : ''; ?>" data-sadhana-id="<?php echo esc_attr((string) absint($sadhana['id'] ?? 0)); ?>" data-practice-id="<?php echo esc_attr((string) $practice_id); ?>">
			<div class="lk-sadhana-card__head">
				<span class="lk-sadhana-card__status">Пройдено <?php echo esc_html((string) $progress); ?>%</span>
				<a class="lk-sadhana-card__link" href="<?php echo esc_url($practice_url); ?>" aria-label="Открыть практику <?php echo esc_attr($title); ?>">
					<svg aria-hidden="true"><use href="<?php echo esc_url($sprite_url); ?>#arrow45-black"></use></svg>
				</a>
			</div>
			<div class="lk-sadhana-card__copy">
				<h3><?php echo esc_html($title); ?></h3>
				<p><?php echo esc_html($completed_days . ' из ' . $total_days . ' дней'); ?></p>
			</div>
			<p class="lk-sadhana-card__date"><?php echo $is_completed ? esc_html($started . ' - ' . $completed) : esc_html('Начата ' . $started); ?></p>
			<div class="lk-sadhana-card__media"><img src="<?php echo esc_url($image); ?>" alt=""></div>
			<?php if ($is_completed) : ?>
				<button class="lk-sadhana-card__restart" type="button" data-sadhana-id="<?php echo esc_attr((string) absint($sadhana['id'] ?? 0)); ?>">Начать снова</button>
			<?php else : ?>
				<button class="lk-sadhana-card__cancel yoga-sadhana-reset-trigger" type="button" data-sadhana-id="<?php echo esc_attr((string) absint($sadhana['id'] ?? 0)); ?>" data-practice-id="<?php echo esc_attr((string) $practice_id); ?>" aria-haspopup="dialog" aria-controls="yoga-sadhana-reset-modal">Отменить</button>
			<?php endif; ?>
			<div class="lk-sadhana-card__progress" aria-label="Пройдено <?php echo esc_attr((string) $progress); ?> процентов">
				<span style="--sadhana-progress: <?php echo esc_attr((string) $progress); ?>%"></span>
			</div>
		</article>
		<?php
	}
}

if (!function_exists('yoga_get_sadhana_card_html')) {
	function yoga_get_sadhana_card_html(array $sadhana, string $type): string {
		$sprite_file = get_template_directory() . '/assets/svg/sprite.svg';
		$sprite_url = add_query_arg(
			'ver',
			file_exists($sprite_file) ? (string) filemtime($sprite_file) : wp_get_theme()->get('Version'),
			get_template_directory_uri() . '/assets/svg/sprite.svg'
		);
		ob_start();
		yoga_render_sadhana_card($sadhana, $type, $sprite_url);
		return (string) ob_get_clean();
	}
}
