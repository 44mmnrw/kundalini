<?php
	/**
		* Template Name: Личный кабинет
	*/
	include(get_template_directory() . '/header-lk.php');
	
	if (!is_user_logged_in()) {
		echo '<div class="container">';
		echo '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">авторизуйтесь</a> для доступа к личному кабинету.</p>';
		echo '</div>';
		get_footer();
		exit;
	}
	
	$current_user = wp_get_current_user();
	$user_id = get_current_user_id();
?>


<section class="section-lk" id="section-lk">
    <div class="container">
        <div class="row">
            <div class="lk">
                <div class="lk__slides">
                    <!-- Слайд "Мои данные" -->
                    <div class="lk-slide active" data-target="1">
                        <h2>Мои данные</h2>
                        <div class="lk-slide__content">
                            <form action="#" class="lk-form" id="profile-form" enctype="multipart/form-data">
                                <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>
                                
                                <div class="lk-form__photo">
                                    <div class="photo-input">
                                        <div class="photo-input-custom">
                                            <div class="photo-input-custom__inner">
                                                <div class="photo-input-custom__inner-photo">
													<?php 
														$current_user = wp_get_current_user();
														$avatar_id = get_field('user_avatar', 'user_' . $current_user->ID);
														
														if ($avatar_id) {
															echo wp_get_attachment_image($avatar_id, 'thumbnail', false, array('class' => 'avatar'));
															} else {
															// Fallback на стандартный аватар WordPress
															echo get_avatar($current_user->ID, 96);
														}
													?>
												</div>
                                                <b class="photo-input-custom__inner-title">Загрузить фото</b>
                                                <p class="photo-input-custom__inner-text">.jpg, .png не более 10мб</p>
											</div>
                                            
										</div>
                                        <div class="photo-input-delete">
                                            <span>Удалить фото</span>
										</div>
										<input type="file" id="avatar-upload" name="avatar" accept=".jpg,.png" style="display: none;">
									</div>
								</div>  
                                
                                <div class="lk-form__main">
                                    <div class="lk-form-item">
                                        <h5>Имя</h5>
                                        <input type="text" class="input" required placeholder="Имя" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
									</div>
                                    <div class="lk-form-item">
                                        <h5>Фамилия</h5>
                                        <input type="text" class="input" required placeholder="Фамилия" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
									</div>
                                    <div class="lk-form-item">
                                        <h5>E-mail</h5>
                                        <input type="email" class="input" required placeholder="E-mail" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
									</div>
                                    <div class="lk-form-item">
                                        <h5>Телефон</h5>
                                        <input type="text" class="input input_phone" name="phone" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>">
									</div>
                                    <div class="lk-form-item">
                                        <h5>Дата рождения</h5>
                                        <input type="text" class="input input_birth" placeholder="__.__.____" name="birthdate" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'birthdate', true)); ?>">
									</div>
                                    <div class="lk-form-item">
                                        <h5>Пол</h5>
                                        <div class="lk-gender">
                                            <?php
												$gender = get_user_meta($current_user->ID, 'gender', true);
											?>
                                            <label class="lk-gender-item <?php echo ($gender == 'male') ? 'active' : ''; ?>">
                                                <input type="radio" name="gender" value="male" <?php checked($gender, 'male'); ?>>
                                                <div class="lk-gender-item__btn">М</div>
											</label>
                                            <label class="lk-gender-item <?php echo ($gender == 'female') ? 'active' : ''; ?>">
                                                <input type="radio" name="gender" value="female" <?php checked($gender, 'female'); ?>>
                                                <div class="lk-gender-item__btn">Ж</div>
											</label>
										</div>
									</div>
                                    
                                    <div class="lk-form-password">
                                        <h2>Изменить пароль</h2>
                                        <div class="lk-form-item">
                                            <h5>Текущий пароль</h5>
                                            <div class="input-password">
                                                <input type="password" class="input" name="current_password"  placeholder="Текущий пароль">
                                                <div class="input-password__btn input-password__btn_show active">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-open'); ?>"></use>
                                                    </svg>
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-closed'); ?>"></use>
                                                    </svg>
												</div>
											</div>
										</div>
                                        <div class="lk-form-item lk-form-item_newpass">
                                            <h5>Новый пароль</h5>
                                            <div class="input-password">
                                                <input type="password" class="input" name="new_password">
                                                <div class="input-password__btn input-password__btn_show active">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-open'); ?>"></use>
                                                    </svg>
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-closed'); ?>"></use>
                                                    </svg>
												</div>
											</div>
										</div>
                                        <div class="lk-form-item lk-form-item_newpassrepeat">
                                            <h5>Повторите пароль</h5>
                                            <div class="input-password">
                                                <input type="password" class="input" name="repeat_password">
                                                <div class="input-password__btn input-password__btn_show active">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-open'); ?>"></use>
                                                    </svg>
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <svg aria-hidden="true" focusable="false">
                                                        <use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#password-eye-closed'); ?>"></use>
                                                    </svg>
												</div>
                                                <span class="input-password__placeholder">Пароли не совпадают</span>
											</div>
										</div>
									</div>
                                    
                                    <div class="lk-form-safe">
                                        <input type="submit" id="lk-safe-btn">
                                        <label for="lk-safe-btn" class="btn">
                                            <span>Сохранить</span>
										</label>
                                        <p class="lk-form-safe__text">Изменения сохранены</p>
									</div>
								</div>
							</form>
						</div>
					</div>
                    
                    <!-- Остальные слайды будут здесь -->
					<div class="lk-slide" data-target="2">
						<h2>
							История пройденных практик
						</h2>
						<div class="lk-slide__content">
							<?php echo do_shortcode('[practice_history]'); ?>
						</div>
					</div>
					
					<div class="lk-slide" data-target="3">
						<h2>
							Избранное
						</h2>
						<div class="lk-slide__content">
							<?php
							$favorites = get_user_meta($user_id, 'favorite_practices', true);
							if (is_string($favorites)) {
								$favorites = array_filter(array_map('trim', explode(',', $favorites)));
							}
							if (!is_array($favorites)) {
								$favorites = array();
							}
							$favorites = array_values(array_unique(array_filter(array_map('intval', $favorites))));
							if (empty($favorites)) :
								echo '<div class="no-favorites">У вас пока нет избранных практик</div>';
							else :
							?>
							<div class="lk-kriyi">
								<div class="kriyi">
									<div class="kriyi__items">
										<?php
										foreach ($favorites as $practice_id) {
                    $practice = get_post($practice_id);
                    
                    // Проверяем, что запись существует и имеет правильный тип
                    if ($practice && $practice->post_type == 'practice') {
                        // Получаем уровень практики
                        $level = get_the_terms($practice_id, 'practice-type');
                        $level_name = !empty($level) ? $level[0]->name : 'Не указан';
                        
                        // Проверяем, является ли практика избранной
                        $is_favorite = in_array($practice_id, $favorites);
                        ?>
                        <div class="kriyi-item">
                            <div class="kriyi-item__inner">
                                <a href="<?php echo get_permalink($practice_id); ?>"></a>
                                <span class="kriya-level"><?php echo esc_html($level_name); ?></span>
                                <div class="kriya-info">
                                    <h3><?php echo get_the_title($practice_id); ?></h3>
                                    <p><?php echo get_the_excerpt($practice_id); ?></p>
                                </div>
                                <div class="kriya-media">
                                    <div class="kriya-img">
                                        <?php if (has_post_thumbnail($practice_id)): ?>
                                        <?php echo get_the_post_thumbnail($practice_id, 'medium', array('alt' => get_the_title($practice_id))); ?>
                                        <?php else: ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="<?php echo get_the_title($practice_id); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="kriya-fav fav active" data-practice-id="<?php echo $practice_id; ?>" role="button" tabindex="0" aria-pressed="true" aria-label="Удалить из избранного">
										<span class="kriya-fav__icon" aria-hidden="true">
											<svg><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
											<svg class="active"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
										</span>
										<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
										<span class="kriya-fav__text kriya-fav__text--remove">Удалить из избранного</span>
                                    </div>
                                    <div class="kriya-btn">
                                        <a href="<?php echo get_permalink($practice_id); ?>" class="kriya-btn__arrow">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
                                        </a>
                                    </div>
                                </div>      
                            </div>
                        </div>
                        <?php
					}
				}
				?>
									</div>
								</div>
							</div>
							<?php endif; ?>
						</div>
					</div>
					
					<!-- Слайд "Рекомендации" -->
					<div class="lk-slide" data-target="4">
						<h2>Рекомендации</h2>
						<div class="lk-slide__content">
							<?php
								if (is_user_logged_in()) {
									
									$recommended_practices = get_recommended_practices($user_id);
									
									if (!empty($recommended_practices)) {
									?>
									<div class="lk-kriyi">
										<div class="kriyi">
											<div class="kriyi__items">
												<?php
													foreach ($recommended_practices as $practice_id) {
														$practice = get_post($practice_id);
														if ($practice && $practice->post_type == 'practice') {
															$level = get_the_terms($practice_id, 'practice-type');
															$level_name = !empty($level) ? $level[0]->name : 'Не указан';
															$user_favorites = get_user_meta($user_id, 'favorite_practices', true);
															if (!is_array($user_favorites)) {
																$user_favorites = array();
															}
															$user_favorites = array_map('intval', $user_favorites);
															$is_favorite = in_array((int) $practice_id, $user_favorites, true);
														?>
														<div class="kriyi-item">
															<div class="kriyi-item__inner">
																<a href="<?php echo get_permalink($practice_id); ?>"></a>
																<span class="kriya-level"><?php echo esc_html($level_name); ?></span>
																<div class="kriya-info">
																	<h3><?php echo get_the_title($practice_id); ?></h3>
																	<p><?php echo get_the_excerpt($practice_id); ?></p>
																</div>
																<div class="kriya-media">
																	<div class="kriya-img">
																		<?php if (has_post_thumbnail($practice_id)): ?>
																		<?php echo get_the_post_thumbnail($practice_id, 'medium', array('alt' => get_the_title($practice_id))); ?>
																		<?php else: ?>
																		<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="<?php echo get_the_title($practice_id); ?>">
																		<?php endif; ?>
																	</div>
																	<div class="kriya-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo $practice_id; ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Удалить из избранного' : 'В избранное'); ?>">
																		<span class="kriya-fav__icon" aria-hidden="true">
																			<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart"></use></svg>
																			<svg class="<?php echo $is_favorite ? 'active' : ''; ?>"><use href="<?php echo get_template_directory_uri(); ?>/assets/svg/sprite.svg#noun-heart-filled"></use></svg>
																		</span>
																		<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
																		<span class="kriya-fav__text kriya-fav__text--remove">Удалить из избранного</span>
																	</div>
																	<div class="kriya-btn">
																		<a href="<?php echo get_permalink($practice_id); ?>" class="kriya-btn__arrow">
																			<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
																			<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
																		</a>
																	</div>
																</div>      
															</div>
														</div>
														<?php
														}
													}
												?>
											</div>
										</div>
									</div>
									<?php
										} else {
										echo '<div class="no-recommendations">';
										echo '<p>Пока нет рекомендаций. Завершите несколько практик, чтобы получить персональные рекомендации.</p>';
										echo '<a href="' . get_post_type_archive_link('practice') . '" class="btn">Посмотреть все практики</a>';
										echo '</div>';
									}
									} else {
									echo '<p>Пожалуйста, авторизуйтесь для просмотра рекомендаций.</p>';
								}
							?>
						</div>
					</div>
					
					
					<!-- Слайд "Мои вопросы" -->
					<div class="lk-slide" data-target="5">
						<h2>Мои вопросы</h2>
						<div class="lk-slide__content">
							<div class="lk-questions-form">
								<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="question-form">
									<?php wp_nonce_field('submit_question', 'question_nonce'); ?>
									<input type="hidden" name="action" value="submit_question">
									<textarea name="question_text" placeholder="Задайте ваш вопрос" required class="input"></textarea>
									<input type="submit" id="lk-questions-submit">
									<label for="lk-questions-submit" class="btn">
										Задать вопрос
									</label>
								</form>
							</div>
							
							<div class="lk-questions">
								<?php
									if (is_user_logged_in()) {
										$user_id = get_current_user_id();
										$questions = get_user_questions($user_id);
										
										if (!empty($questions)) {
											$visible_questions = array_slice($questions, 0, 4);
											$hidden_questions = array_slice($questions, 4);
											
											foreach ($visible_questions as $question) {
												display_question_item($question);
											}
											
											if (!empty($hidden_questions)) {
												foreach ($hidden_questions as $question) {
													display_question_item($question, true);
												}
											}
											
											if (count($questions) > 4) {
												echo '<div class="btn show-more-questions">';
												echo '<span class="active">Показать еще</span>';
												echo '<span>Свернуть</span>';
												echo '</div>';
											}
											} else {
											echo '<p class="no-questions">У вас пока нет заданных вопросов.</p>';
										}
										} else {
										echo '<p>Пожалуйста, авторизуйтесь для просмотра ваших вопросов.</p>';
									}
								?>
							</div>
						</div>
					</div>
					
					<!-- Слайд "Настройки подписки" -->
					<div class="lk-slide" data-target="6">
						<div class="lk-slide__content">
							<div class="lk-settings">
								<div class="lk-settings__slide lk-settings__slide_main active" data-target="1">
									<h2>Настройки подписки</h2>
									<div class="lk-settings-part">
										<div class="lk-settings-item lk-settings-item_main">
											<div class="lk-settings-item__col">
												<p class="lk-settings-item__col-text">Ваш тариф:</p>
												<div class="personal-status">
													<img src="<?php echo get_template_directory_uri(); ?>/assets/img/personal-status-icon_settings.png" alt="" class="personal-status__img">
													<span>
														<?php
															$current_subscription = get_user_active_subscription();
															echo $current_subscription ? $current_subscription['name'] : 'Не активен';
														?>
													</span>
												</div>
											</div>
											<div class="lk-settings-item__col">
												<p class="lk-settings-item__col-text">Действует до:</p>
												<time>
													<?php
														echo $current_subscription ? date('d.m.Y', strtotime($current_subscription['end_date'])) : '—';
													?>
												</time>
											</div>
										</div>
									</div>
									
									<div class="lk-settings-part">
										<h4>Способы оплаты</h4>
										<div class="lk-settings-item lk-settings-item_action" data-target="2">
											<div class="lk-settings-item__col">
												<div class="lk-settings-item__col-icon">
													<img src="<?php echo get_template_directory_uri(); ?>/assets/img/lk-settings-icon.png" alt="">
												</div>
												<p class="lk-settings-item__col-text">Карты</p>
											</div>
											<div class="lk-settings-item__col">
												<div class="lk-settings-item__col-action">
													<div class="lk-settings-item__col-action-numbers">
														<span>
															<?php
																$saved_cards = get_user_saved_cards();
																echo count($saved_cards);
															?>
														</span>
													</div>
													<div class="lk-settings-item__col-action-arrow">
														<img src="<?php echo get_template_directory_uri(); ?>/assets/img/lk-settings-arrow.png" alt="">
													</div>
												</div>
											</div>
										</div>
									</div>
									
									<div class="lk-settings-part">
										<h4>История покупок</h4>
										<?php
											$orders = get_user_orders_history();
											if (!empty($orders)) {
												foreach ($orders as $order) {
												?>
												<div class="lk-settings-item">
													<div class="lk-settings-item__col">
														<time><?php echo date('d.m.Y', strtotime($order['date'])); ?></time>
													</div>
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-text">
															<b><?php echo esc_html($order['product_name']); ?></b>
														</div>
														<p class="lk-settings-item__col-text"><?php echo wc_price($order['total']); ?></p>
													</div>
												</div>
												<?php
												}
												} else {
												echo '<p>У вас пока нет завершенных заказов.</p>';
											}
										?>
									</div>
									
									<?php if (!$current_subscription): ?>
									<?php
										$tariffs_url = home_url('/product-category/tariffs/');
										$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
										if ($tariffs_term && !is_wp_error($tariffs_term)) {
											$term_link = get_term_link($tariffs_term);
											if (!is_wp_error($term_link)) {
												$tariffs_url = $term_link;
											}
										}
									?>
									<div class="lk-settings-part">
										<div class="subscribe-cta">
											<p>У вас нет активной подписки. Выберите подходящий тариф:</p>
											<a href="<?php echo esc_url($tariffs_url); ?>" class="btn">
												<span><?php echo esc_html(yoga_get_purchase_cta_text()); ?></span>
											</a>
										</div>
									</div>
									<?php endif; ?>
								</div>
								
								<div class="lk-settings__slide lk-settings__slide_payment" data-target="2">
									<div class="form-back" data-target="1">
										<span>назад</span>
									</div>
									<h2>Способы оплаты</h2>
									<div class="lk-settings-part lk-settings-part_cards">
										<?php
											$saved_cards = get_user_saved_cards();
											if (!empty($saved_cards)) {
												foreach ($saved_cards as $card) {
												?>
												<div class="lk-settings-item lk-settings-item_action">
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-icon">
															<img src="<?php echo get_template_directory_uri() . '/assets/img/lk-payment-icon_' . $card['type'] . '.png'; ?>" alt="<?php echo $card['brand']; ?>">
														</div>
														<p class="lk-settings-item__col-text">
															<?php echo $card['brand'] . ' **' . $card['last4']; ?>
														</p>
													</div>
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-action">
															<div class="lk-settings-item__col-action-options" data-card-id="<?php echo $card['id']; ?>">
																<img src="<?php echo get_template_directory_uri(); ?>/assets/img/lk-payment-options.png" alt="">
															</div>
														</div>
													</div>
												</div>
												<?php
												}
												} else {
												echo '<p>У вас нет сохраненных карт.</p>';
											}
										?>
										
										<div class="lk-settings-item lk-settings-item_addcard" id="add-new-card">
											<div class="lk-settings-item__col">
												<div class="lk-settings-item__col-icon">
													<img src="<?php echo get_template_directory_uri(); ?>/assets/img/lk-payment-icon_add.png" alt="">
												</div>
												<p class="lk-settings-item__col-text">Добавить карту</p>
											</div>
											<div class="lk-settings-item__col"></div>
										</div>
										
										<!-- Форма добавления новой карты -->
										<div class="add-card-form" style="display: none;">
											<form id="add-card-form" data-stripe-key="<?php echo esc_attr((string) get_option('stripe_publishable_key')); ?>">
												<?php wp_nonce_field('add_payment_method', 'payment_nonce'); ?>
												<div class="form-row">
													<label>Номер карты</label>
													<div id="card-number-element" class="stripe-input"></div>
												</div>
												<div class="form-row">
													<label>Срок действия</label>
													<div id="card-expiry-element" class="stripe-input"></div>
												</div>
												<div class="form-row">
													<label>CVC</label>
													<div id="card-cvc-element" class="stripe-input"></div>
												</div>
												<div class="form-actions">
													<button type="submit" class="btn">Сохранить карту</button>
													<button type="button" class="btn btn-cancel">Отмена</button>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</div>
</section>
<?php
$lk_sidebar_sprite = esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg');
$lk_secondary = function_exists('yoga_lk_sidebar_secondary_nav_urls') ? yoga_lk_sidebar_secondary_nav_urls() : array(
	'library' => home_url('/'),
	'tariffs' => home_url('/'),
	'about' => home_url('/'),
	'blog' => home_url('/'),
	'contacts' => home_url('/'),
	'faq' => home_url('/'),
);
?>
<div class="sidebar">
	<div class="sidebar-inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="sidebar-logo">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-logo.png" alt="">
		</a>
        <div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
        <div class="sidebar-menu">
			<div class="sidebar-menu__item active" data-target="1">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-user" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					Мои данные
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="2">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-history" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					История практик
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="3">
				<div class="sidebar-menu__item-icon sidebar-menu__item-icon--heart">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 17.4 15.4852" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-heart" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					Избранное
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="4">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="-0.6 -0.6 18.2 18.2" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-smile" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					Рекомендации
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="5">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-question" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					Мои вопросы
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="6">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-settings" width="100%" height="100%"></use>
					</svg>
				</div>
				<span>
					Настройки подписки
				</span>
			</div>
			
		</div>
		</div>

		<hr class="sidebar-menu-sep" aria-hidden="true">

		<div class="sidebar-menu-lk-group sidebar-menu-lk-group--secondary">
		<nav class="sidebar-menu-secondary" aria-label="<?php esc_attr_e('Навигация по сайту', 'yoga'); ?>">
			<a class="sidebar-menu-secondary__link sidebar-menu-secondary__link--library" href="<?php echo esc_url($lk_secondary['library']); ?>">
				<span><?php esc_html_e('Библиотека практик', 'yoga'); ?></span>
				<svg class="sidebar-menu-secondary__chevron" viewBox="0 0 9 16" aria-hidden="true" focusable="false">
					<use href="<?php echo $lk_sidebar_sprite; ?>#lk-library-chevron" width="100%" height="100%"></use>
				</svg>
			</a>
			<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['tariffs']); ?>">
				<span><?php esc_html_e('Тарифы и подписка', 'yoga'); ?></span>
			</a>
			<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['about']); ?>">
				<span><?php esc_html_e('О нас', 'yoga'); ?></span>
			</a>
			<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['blog']); ?>">
				<span><?php esc_html_e('Блог', 'yoga'); ?></span>
			</a>
			<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['contacts']); ?>">
				<span><?php esc_html_e('Контакты', 'yoga'); ?></span>
			</a>
			<a class="sidebar-menu-secondary__link" href="<?php echo esc_url($lk_secondary['faq']); ?>">
				<span><?php esc_html_e('FAQ', 'yoga'); ?></span>
			</a>
		</nav>
		</div>

		<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

        <div class="sidebar-exit modal-call modal-call_logout">
			<div class="sidebar-exit__icon">
				<svg class="sidebar-exit__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<use href="<?php echo $lk_sidebar_sprite; ?>#lk-sidebar-logout" width="100%" height="100%"></use>
				</svg>
			</div>
			<span>
				Выйти
			</span>
		</div>
	</div>
</div>
<?php
	get_footer();					