<?php
$practice_level_raw = trim((string) get_field('practice_level'));
$practice_level_label = function_exists('yoga_normalize_practice_level_label')
	? yoga_normalize_practice_level_label($practice_level_raw)
	: $practice_level_raw;
$practice_level_slug = function_exists('yoga_get_practice_level_slug')
	? yoga_get_practice_level_slug($practice_level_raw)
	: '';
$same_level_url = '';
$difficulty_term_id = 0;

if ($practice_level_slug !== '') {
	$difficulty_term = get_term_by('slug', $practice_level_slug, 'practice-difficulty');
	if ($difficulty_term instanceof WP_Term && !is_wp_error($difficulty_term)) {
		$difficulty_term_id = (int) $difficulty_term->term_id;
	}
}

$sections = get_field('practice_sections');
if (!is_array($sections)) {
	$sections = array();
}
$practice_id = (int) get_the_ID();

if ($difficulty_term_id <= 0) {
	$difficulty_terms = get_terms(array(
		'taxonomy' => 'practice-difficulty',
		'hide_empty' => false,
		'orderby' => 'term_id',
		'order' => 'ASC',
	));

	if (!empty($difficulty_terms) && !is_wp_error($difficulty_terms)) {
		foreach ($difficulty_terms as $term) {
			$term_label = function_exists('yoga_get_practice_difficulty_label')
				? yoga_get_practice_difficulty_label($term)
				: (string) $term->name;
			$term_label_normalized = function_exists('yoga_normalize_practice_level_label')
				? yoga_normalize_practice_level_label($term_label)
				: $term_label;
			if ($term_label_normalized === $practice_level_label) {
				$difficulty_term_id = (int) $term->term_id;
				break;
			}
		}

		if ($difficulty_term_id <= 0 && count($difficulty_terms) >= 3) {
			$fallback_order_map = array(
				'beginner' => 0,
				'intermediate' => 1,
				'advanced' => 2,
			);
			$fallback_index = $fallback_order_map[$practice_level_slug] ?? null;
			if ($fallback_index !== null && isset($difficulty_terms[$fallback_index])) {
				$difficulty_term_id = (int) $difficulty_terms[$fallback_index]->term_id;
			}
		}
	}
}

$library_path = function_exists('yoga_get_practice_primary_term_path')
	? (string) yoga_get_practice_primary_term_path((int) get_the_ID())
	: '';
$library_url = $library_path !== ''
	? home_url('/library/' . trim($library_path, '/') . '/')
	: home_url('/library/');

if ($difficulty_term_id > 0 && $practice_level_slug !== '') {
	// Используем универсальный параметр difficulty, чтобы не попадать в 404 на некоторых rewrite-настройках.
	$same_level_url = add_query_arg('difficulty', $practice_level_slug, $library_url);
} elseif ($practice_level_slug !== '') {
	$same_level_url = add_query_arg('difficulty', $practice_level_slug, $library_url);
} else {
	$same_level_url = $library_url;
}
?>
<section class="section-praktika" id="section-praktika">
    <div class="container">
        <div class="row">
            <div class="praktika">
                <div class="praktika-details">
					<div class="praktika-details__lead">
						<span class="praktika-details__lvl">
							<?php if ($same_level_url !== '') : ?>
								<a class="praktika-details__lvl-link" href="<?php echo esc_url($same_level_url); ?>">
									<?php echo esc_html($practice_level_label); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html($practice_level_label); ?>
							<?php endif; ?>
						</span>
						<span class="praktika-details__time">
							<?php echo get_field('practice_time') ?: '7 минут'; ?>
						</span>
					</div>
                    <?php
					$user_id = get_current_user_id();
					$is_favorite = in_array(get_the_ID(), get_user_meta($user_id, 'favorite_practices', true) ?: array(), true);
					?>
                    <div class="praktika-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo esc_attr((string) get_the_ID()); ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Убрать' : 'В избранное'); ?>">
						<span class="praktika-fav__main">
							<span class="praktika-fav__icon" aria-hidden="true">
								<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
								<svg class="<?php echo $is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
							</span>
							<span class="praktika-fav__labels">
								<span class="praktika-fav__text praktika-fav__text--add">В избранное</span>
								<span class="praktika-fav__text praktika-fav__text--remove">Убрать</span>
							</span>
						</span>
					</div>
				</div>
                <div class="praktika__main">
                        <div class="praktika-info">
							<?php
								if ($sections) {
									foreach ($sections as $section_index => $section) {
										$layout = (string) ($section['acf_fc_layout'] ?? '');
										$anchor_id = function_exists('yoga_get_practice_section_anchor_id')
											? yoga_get_practice_section_anchor_id($section, (int) $section_index)
											: ($section['anchor_id'] ?? ('anchor_0' . ($section_index + 1)));
										$section_key = 'section-' . ($section_index + 1);
										$section_can_view = !function_exists('yoga_can_view_practice_section_layout')
											|| yoga_can_view_practice_section_layout($layout, $practice_id);

										if (!$section_can_view) {
											$section_title = function_exists('yoga_get_practice_section_display_title')
												? yoga_get_practice_section_display_title($section, $layout)
												: ($section['section_title'] ?? $layout);
											include locate_template('template-parts/praktika-info/section-paywall.php');
											continue;
										}

										if (function_exists('yoga_can_view_practice_section') && !yoga_can_view_practice_section($section, get_current_user_id())) {
											continue;
										}

										switch ($layout) {
											
											case 'anchor_01':
											// Anchor 01 - О крийе
											include(locate_template('template-parts/praktika-info/anchor_01.php'));
											
											
											break;
											
											case 'anchor_02':
											// Anchor 02 - Эффекты крийи
											include(locate_template('template-parts/praktika-info/anchor_02.php'));
											
											break;
											
											case 'anchor_03':
											// Anchor 03 - Философия практики
											include(locate_template('template-parts/praktika-info/anchor_03.php'));
											break;
											
											case 'anchor_04':
											// Anchor 04 - Философия практики
											include(locate_template('template-parts/praktika-info/anchor_04.php'));
											break;
											
											case 'anchor_05':
											//var_dump($section);
											// Anchor 05 - Философия практики
											include(locate_template('template-parts/praktika-info/anchor_05.php'));
											break;
											
											case 'anchor_06':
											// Anchor 06 - Философия практики
											include(locate_template('template-parts/praktika-info/anchor_06.php'));
											break;
											
											// Добавьте case для остальных anchor'ов по аналогии
											// anchor_04, anchor_05, anchor_06...
											
											default:
											// Дефолтная обработка
											if ($section['title']) {
												echo '<h3>' . esc_html($section['title']) . '</h3>';
											}
											break;
										}
									}
								}
							?>
						</div>
						
                    <div class="praktika-menu">
                        <div class="praktika-fixed">
                            <h3>Содержание</h3>
                            <nav>
                                <ul>
                                    <?php
										if ($sections) {
											foreach ($sections as $index => $section) {
												$menu_layout = (string) ($section['acf_fc_layout'] ?? '');
												$menu_locked = function_exists('yoga_can_view_practice_section_layout')
													&& !yoga_can_view_practice_section_layout($menu_layout, $practice_id);

												if (
													!$menu_locked
													&& function_exists('yoga_can_view_practice_section')
													&& !yoga_can_view_practice_section($section, get_current_user_id())
												) {
													continue;
												}

												$section_id = function_exists('yoga_get_practice_section_anchor_id')
													? yoga_get_practice_section_anchor_id($section, (int) $index)
													: ($section['anchor_id'] ?: 'anchor_0' . ($index + 1));
												$section_key = 'section-' . ($index + 1);
												$section_title = function_exists('yoga_get_practice_section_display_title')
													? yoga_get_practice_section_display_title($section, $menu_layout)
													: ($section['section_title'] ?? '');
											?>
                                            <li<?php echo $menu_locked ? ' class="praktika-menu__item--locked"' : ''; ?>>
                                                <a class="ref" href="#<?php echo esc_attr($section_id); ?>" data-section-key="<?php echo esc_attr($section_key); ?>">
                                                    <?php echo esc_html($section_title); ?>
                                                    <?php if ($menu_locked) : ?>
														<span class="praktika-menu__lock" aria-hidden="true"></span>
													<?php endif; ?>
												</a>
											</li>
                                            <?php
											}
										}
									?>
                                    <li>
                                        <a class="ref" href="#section-form-questions" data-section-key="section-form-questions">
                                            Задать вопрос
										</a>
									</li>
								</ul>
                            </nav>
                            <?php
								$practice_id = (int) get_the_ID();
								if (
									$practice_id > 0
									&& function_exists('yoga_get_practice_download_source')
									&& yoga_get_practice_download_source($practice_id) !== null
									&& function_exists('yoga_viewer_has_full_practice_sections')
									&& yoga_viewer_has_full_practice_sections(null, $practice_id)
								) {
									$user_id            = get_current_user_id();
									$already_downloaded = function_exists('yoga_user_has_downloaded_practice')
										&& yoga_user_has_downloaded_practice($user_id, $practice_id);
									$can_download       = function_exists('yoga_user_can_download_practice')
										&& yoga_user_can_download_practice($user_id, $practice_id);
									$downloads_remaining = function_exists('yoga_get_user_downloads_remaining')
										? yoga_get_user_downloads_remaining($user_id)
										: null;
									$remaining_label = $downloads_remaining === null
										? __('безлимит', 'yoga')
										: (string) max(0, (int) $downloads_remaining);
									$download_classes   = 'praktika-download';
									if (!$can_download) {
										$download_classes .= ' praktika-download--exhausted';
									}
									?>
                                <div
                                    class="<?php echo esc_attr($download_classes); ?>"
                                    data-practice-id="<?php echo esc_attr((string) $practice_id); ?>"
                                    data-download-url="<?php echo esc_url(yoga_get_practice_download_url($practice_id)); ?>"
                                    data-can-download="<?php echo $can_download ? '1' : '0'; ?>"
                                    data-already-downloaded="<?php echo $already_downloaded ? '1' : '0'; ?>"
                                    data-remaining-downloads="<?php echo $downloads_remaining === null ? 'unlimited' : esc_attr((string) max(0, (int) $downloads_remaining)); ?>"
                                >
                                        <?php if ($can_download) : ?>
                                    <a href="<?php echo esc_url(yoga_get_practice_download_url($practice_id)); ?>" class="btn praktika-download__btn">
                                        <span><?php echo esc_html__('Скачать протокол практики', 'yoga'); ?></span>
                                    </a>
                                        <?php else :
                                            $limit_reached = $downloads_remaining !== null && (int) $downloads_remaining <= 0;
                                            $disabled_label = $limit_reached
                                                ? __('Скачать протокол практики', 'yoga')
                                                : yoga_get_practice_already_downloaded_message();
                                            ?>
                                    <span class="btn praktika-download__btn" aria-disabled="true">
                                        <span><?php echo esc_html($disabled_label); ?></span>
                                    </span>
                                            <?php if ($limit_reached) : ?>
                                    <p class="praktika-download__note"><?php echo esc_html(yoga_get_download_limit_exceeded_message()); ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
									<p class="praktika-download__remaining">
										<?php echo esc_html__('Осталось скачиваний:', 'yoga'); ?>
										<span><?php echo esc_html($remaining_label); ?></span>
									</p>
                                </div>
                                        <?php
								}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
