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
                        <div class="ways">
                            <ul>
                                <li><a href="<?php echo home_url(); ?>" class="ways-item ref">Главная</a></li>
                                <li><a href="#" class="ways-item ref">Мои данные</a></li>
							</ul>
						</div>
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
                                        <input type="text" class="input input_phone" placeholder="+7 (999) 999 99 99" name="phone" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>">
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
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon.png" alt="">
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon_hide.png" alt="">
												</div>
											</div>
										</div>
                                        <div class="lk-form-item lk-form-item_newpass">
                                            <h5>Новый пароль</h5>
                                            <div class="input-password">
                                                <input type="password" class="input" name="new_password">
                                                <div class="input-password__btn input-password__btn_show active">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon.png" alt="">
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon_hide.png" alt="">
												</div>
											</div>
										</div>
                                        <div class="lk-form-item lk-form-item_newpassrepeat">
                                            <h5>Повторите пароль</h5>
                                            <div class="input-password">
                                                <input type="password" class="input" name="repeat_password">
                                                <div class="input-password__btn input-password__btn_show active">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon.png" alt="">
												</div>
                                                <div class="input-password__btn input-password__btn_hide">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/input-password-icon_hide.png" alt="">
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
						<div class="ways">
							<ul>
								<li>
									<a href="index.html" class="ways-item ref">
										Главная
									</a>
								</li>
								<li>
									<a href="#" class="ways-item ref">
										История практик
									</a>
								</li>
							</ul>
						</div>
						<h2>
							История пройденных практик
						</h2>
						<div class="lk-slide__content">
							<?php echo do_shortcode('[practice_history]'); ?>
						</div>
					</div>
					
					<div class="lk-slide" data-target="3">
						<div class="ways">
							<ul>
								<li>
									<a href="index.html" class="ways-item ref">
										Главная
									</a>
								</li>
								<li>
									<a href="#" class="ways-item ref">
										Избранное
									</a>
								</li>
							</ul>
						</div>
						<h2>
							Избранное
						</h2>
						<div class="lk-slide__content">
							<?php
							$favorites = get_user_meta($user_id, 'favorite_practices', true);
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
                                    <div class="kriya-fav fav active" data-practice-id="<?php echo $practice_id; ?>">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav.png" alt="" class="active">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav_active.png" alt="">
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
						<div class="ways">
							<ul>
								<li><a href="<?php echo home_url(); ?>" class="ways-item ref">Главная</a></li>
								<li><a href="#" class="ways-item ref">Рекомендации</a></li>
							</ul>
						</div>
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
															$is_favorite = in_array($practice_id, get_user_meta($user_id, 'favorite_practices', true) ?: array());
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
																	<div class="kriya-fav fav <?php echo $is_favorite ? 'active' : ''; ?>" data-practice-id="<?php echo $practice_id; ?>">
																		<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav.png" alt="" class="active">
																		<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav_active.png" alt="">
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
						<div class="ways">
							<ul>
								<li><a href="<?php echo home_url(); ?>" class="ways-item ref">Главная</a></li>
								<li><a href="#" class="ways-item ref">Мои вопросы</a></li>
							</ul>
						</div>
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
						<div class="ways">
							<ul>
								<li><a href="<?php echo home_url(); ?>" class="ways-item ref">Главная</a></li>
								<li><a href="#" class="ways-item ref">Настройки подписки</a></li>
							</ul>
						</div>
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
									<div class="lk-settings-part">
										<div class="subscribe-cta">
											<p>У вас нет активной подписки. Выберите подходящий тариф:</p>
											<a href="/product-category/tariffs/" class="btn">
												<span>Выбрать тариф</span>
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
											<form id="add-card-form">
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
<div class="sidebar">
	<div class="sidebar-inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="sidebar-logo">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-logo.png" alt="">
		</a>
        <div class="sidebar-menu">
			<div class="sidebar-menu__item active" data-target="1">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_01.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_01-active.png" alt="">
				</div>
				<span>
					Мои данные
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="2">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_02.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_02-active.png" alt="">
				</div>
				<span>
					История практик
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="3">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_03.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_03-active.png" alt="">
				</div>
				<span>
					Избранное
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="4">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_04.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_04-active.png" alt="">
				</div>
				<span>
					Рекомендации
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="5">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_05.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_05-active.png" alt="">
				</div>
				<span>
					Мои вопросы
				</span>
			</div>
			<div class="sidebar-menu__item" data-target="6">
				<div class="sidebar-menu__item-icon">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_06.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-menu-icon_06-active.png" alt="">
				</div>
				<span>
					Настройки подписки
				</span>
			</div>
			
		</div>
        <div class="sidebar-exit modal-call modal-call_logout">
			<div class="sidebar-exit__icon">
				<img src="<?php echo get_template_directory_uri(); ?>/assets/img/sidebar-exit.png" alt="" class="active">
			</div>
			<span>
				Выйти
			</span>
		</div>
	</div>
</div>
<script>
	jQuery(document).ready(function($) {
		// Инициализация Stripe Elements (пример)
		function initStripeElements() {
			if (typeof Stripe === 'undefined') return;
			
			var stripe = Stripe('<?php echo get_option("stripe_publishable_key"); ?>');
			var elements = stripe.elements();
			
			var cardNumber = elements.create('cardNumber');
			cardNumber.mount('#card-number-element');
			
			var cardExpiry = elements.create('cardExpiry');
			cardExpiry.mount('#card-expiry-element');
			
			var cardCvc = elements.create('cardCvc');
			cardCvc.mount('#card-cvc-element');
			
			// Обработка формы
			$('#add-card-form').on('submit', function(e) {
				e.preventDefault();
				
				stripe.createPaymentMethod({
					type: 'card',
					card: cardNumber,
					billing_details: {
					// Добавьте данные пользователя
				}
				}).then(function(result) {
				if (result.error) {
					showNotification(result.error.message, 'error');
					} else {
					// Отправка на сервер
					$.ajax({
						url: yoga_ajax.ajax_url,
						type: 'POST',
						data: {
							action: 'add_payment_method',
							payment_method_id: result.paymentMethod.id,
							security: yoga_ajax.nonce
						},
						success: function(response) {
							if (response.success) {
								showNotification('Карта успешно добавлена');
								$('.add-card-form').slideUp();
								// Обновить список карт
								location.reload();
								} else {
								showNotification(response.data, 'error');
							}
						}
					});
				}
			});
		});
	}
	
	// Инициализация при загрузке
	$(document).ready(function() {
		if ($('#card-number-element').length) {
			initStripeElements();
		}
	});
});
</script>
<?php
	get_footer();					