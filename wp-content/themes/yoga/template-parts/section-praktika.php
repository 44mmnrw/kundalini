<?php
/**
 * Переиспользуемый шаблонный блок: section praktika.
 *
 * @package Yoga
 */
$practice_level_raw = trim((string) get_field('practice_level'));
$practice_level_label = function_exists('yoga_normalize_practice_level_label')
	? yoga_normalize_practice_level_label($practice_level_raw)
	: $practice_level_raw;
$sections = get_field('practice_sections');
if (!is_array($sections)) {
	$sections = array();
}
$practice_id = (int) get_the_ID();
$section_praktika_classes = array('section-praktika');
if (!empty($section_praktika_extra_class)) {
	$section_praktika_classes[] = sanitize_html_class((string) $section_praktika_extra_class);
}
?>
<section class="<?php echo esc_attr(implode(' ', $section_praktika_classes)); ?>" id="section-praktika">
    <div class="container">
        <div class="row">
            <div class="praktika">
                <h2 class="ways-heading praktika-heading"><?php echo esc_html(get_the_title()); ?></h2>
                <div class="praktika-details">
					<div class="praktika-details__lead">
						<span class="praktika-details__lvl">
							<?php echo esc_html($practice_level_label); ?>
						</span>
						<span class="praktika-details__time">
							<svg class="praktika-details__time-icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#time-read-icon'); ?>"></use></svg>
							<?php echo get_field('practice_time') ?: '7 минут'; ?>
						</span>
					</div>
                    <?php
					$user_id = get_current_user_id();
					$is_favorite = in_array(get_the_ID(), get_user_meta($user_id, 'favorite_practices', true) ?: array(), true);
					?>
                    <div class="praktika-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo esc_attr((string) get_the_ID()); ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Удалить' : 'В избранное'); ?>">
						<span class="praktika-fav__main">
							<span class="praktika-fav__icon" aria-hidden="true">
								<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#<?php echo $is_favorite ? 'site-heart-filled' : 'site-heart'; ?>"></use></svg>
							</span>
							<span class="praktika-fav__labels" aria-hidden="true">
								<span class="praktika-fav__text praktika-fav__text--add">В избранное</span>
								<span class="praktika-fav__text praktika-fav__text--remove">Удалить</span>
							</span>
						</span>
					</div>
				</div>
				<div class="praktika-info">
							<?php
								if ($sections) {
									foreach ($sections as $section_index => $section) {
										$layout = (string) ($section['acf_fc_layout'] ?? '');
										$anchor_id = function_exists('yoga_get_practice_section_anchor_id')
											? yoga_get_practice_section_anchor_id($section, (int) $section_index)
											: ($section['anchor_id'] ?? ('anchor_0' . ($section_index + 1)));
										$section_key = 'section-' . ($section_index + 1);
										$section_title = function_exists('yoga_get_practice_section_display_title')
											? yoga_get_practice_section_display_title($section, $layout)
											: ($section['section_title'] ?? $layout);
										$section_can_view = !function_exists('yoga_can_view_practice_section_layout')
											|| yoga_can_view_practice_section_layout($layout, $practice_id);

										if (!$section_can_view) {
											if (
												!function_exists('yoga_should_hide_practice_section_paywall')
												|| !yoga_should_hide_practice_section_paywall($section, get_current_user_id())
											) {
												include locate_template('template-parts/praktika-info/section-paywall.php');
											}
											continue;
										}

										if (function_exists('yoga_can_view_practice_section') && !yoga_can_view_practice_section($section, get_current_user_id())) {
											if (
												!function_exists('yoga_should_hide_practice_section_paywall')
												|| !yoga_should_hide_practice_section_paywall($section, get_current_user_id())
											) {
												include locate_template('template-parts/praktika-info/section-paywall.php');
											}
											continue;
										}

										switch ($layout) {

											case 'anchor_01':

											include(locate_template('template-parts/praktika-info/anchor_01.php'));


											break;

											case 'anchor_02':

											include(locate_template('template-parts/praktika-info/anchor_02.php'));

											break;

											case 'anchor_03':

											include(locate_template('template-parts/praktika-info/anchor_03.php'));
											break;

											case 'anchor_04':

											include(locate_template('template-parts/praktika-info/anchor_04.php'));
											break;

											case 'anchor_05':


											include(locate_template('template-parts/praktika-info/anchor_05.php'));
											break;

											case 'anchor_06':

											include(locate_template('template-parts/praktika-info/anchor_06.php'));
											break;




											default:

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
												$menu_layout_locked = function_exists('yoga_can_view_practice_section_layout')
													&& !yoga_can_view_practice_section_layout($menu_layout, $practice_id);
												$menu_tariff_locked = function_exists('yoga_can_view_practice_section')
													&& !yoga_can_view_practice_section($section, get_current_user_id());
												$menu_locked = $menu_layout_locked || $menu_tariff_locked;
												if (
													$menu_locked
													&& function_exists('yoga_should_hide_practice_section_paywall')
													&& yoga_should_hide_practice_section_paywall($section, get_current_user_id())
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
									<?php if (!function_exists('yoga_can_view_practice_questions_form') || yoga_can_view_practice_questions_form(get_current_user_id())) : ?>
                                    <li>
                                        <a class="ref" href="#section-form-questions" data-section-key="section-form-questions">
                                            Задать вопрос
										</a>
									</li>
									<?php endif; ?>
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
</section>
