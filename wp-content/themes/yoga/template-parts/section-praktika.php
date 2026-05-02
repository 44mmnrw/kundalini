<section class="section-praktika" id="section-praktika">
    <div class="container">
        <div class="row">
            <div class="praktika">
                <div class="praktika-details">
                    <span class="praktika-details__lvl">
                        <?= get_field('practice_level') ?>
					</span>
                    <span class="praktika-details__time">
                        <?php echo get_field('practice_time') ?: '7 минут'; ?>
					</span>
					<?php
					$user_id = get_current_user_id();
					$is_favorite = in_array(get_the_id(), get_user_meta($user_id, 'favorite_practices', true) ?: array());
					?>
                    <div class="praktika-fav fav" data-practice-id="<?php echo get_the_id(); ?>">
                        <div class="praktika-fav__icon">
                            <svg class="<?php echo !$is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
                            <svg class="<?php echo $is_favorite ? 'active' : ''; ?>" aria-hidden="true"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
						</div>
                        <span>В избранное</span>
					</div>
				</div>
                <div class="praktika__main">
                        <div class="praktika-info">
							<?php
								$sections = get_field('practice_sections');
								if ($sections) {
									foreach ($sections as $section) {
										$layout = $section['acf_fc_layout'];
										$anchor_id = $section['anchor_id'];
										
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
										// Меню навигации по секциям
										if ($sections) {
											foreach ($sections as $index => $section) {
												$section_id = $section['anchor_id'] ?: 'anchor_0' . ($index + 1);
												$section_title = $section['section_title'];
											?>
                                            <li>
                                                <a class="ref" href="#<?php echo esc_attr($section_id); ?>">
                                                    <?php echo esc_html($section_title); ?>
												</a>
											</li>
                                            <?php
											}
										}
									?>
                                    <li>
                                        <a class="ref" href="#section-form-questions">
                                            Задать вопрос
										</a>
									</li>
								</ul>
							</nav>
                            <?php
								$download_file = get_field('practice_download');
								if ($download_file) {
								?>
                                <a href="<?php echo esc_url($download_file); ?>" download class="btn">
                                    <span>Cкачать протокол практики</span>
								</a>
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