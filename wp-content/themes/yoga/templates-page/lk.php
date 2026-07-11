<?php
	/**
		* Template Name: Личный кабинет
	*/
	get_header();
	
	if (!is_user_logged_in()) {
		echo '<div class="container">';
		echo '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">авторизуйтесь</a> для доступа к личному кабинету.</p>';
		echo '</div>';
		get_footer();
		exit;
	}
	
	$current_user = wp_get_current_user();
	$user_id = get_current_user_id();
	$user_timezone = get_user_meta($user_id, 'timezone', true);
	$user_timezone = is_string($user_timezone) ? $user_timezone : '';
	$timezone_options = timezone_identifiers_list();
	$requested_lk_section = function_exists('yoga_get_requested_lk_section') ? yoga_get_requested_lk_section() : '';
	$initial_lk_target = function_exists('yoga_get_initial_lk_target') ? yoga_get_initial_lk_target() : '1';
?>


<section class="section-lk" id="section-lk" data-initial-target="<?php echo esc_attr($initial_lk_target); ?>" data-server-routed="<?php echo $requested_lk_section !== '' ? '1' : '0'; ?>">
    <div class="container">
        <div class="row">
            <div class="lk">
                <div class="lk__slides">
                    <!-- Слайд "Мои данные" -->
					<div class="lk-slide<?php echo $initial_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
                        <div class="lk-slide__content">
                            <h1 class="lk-slide__title">Мои данные</h1>
                            <form action="#" class="lk-form" id="profile-form" enctype="multipart/form-data">
                                <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>

								<div class="lk-form__photo">
									<div class="photo-input">
										<div class="photo-input-custom" role="button" tabindex="0">
											<div class="photo-input-custom__inner">
												<div class="photo-input-custom__inner-photo" aria-hidden="true">
													<svg class="photo-input-custom__icon" viewBox="0 0 24 24" focusable="false">
														<use href="#lk-upload-camera"></use>
													</svg>
												</div>
												<div class="photo-input-custom__copy">
													<b class="photo-input-custom__inner-title">Загрузить фото</b>
													<p class="photo-input-custom__inner-text">.jpg, .png не более 10мб</p>
												</div>
											</div>
										</div>
										<input type="file" id="avatar-upload" name="avatar" accept=".jpg,.png" style="display: none;">
									</div>
								</div>

                                <div class="lk-form__main">
									<div class="lk-form-fields">
										<div class="lk-form-row">
											<div class="lk-form-item">
												<h5>Имя<span>*</span></h5>
												<input type="text" class="input" required placeholder="Имя" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
											</div>
											<div class="lk-form-item">
												<h5>Фамилия<span>*</span></h5>
												<input type="text" class="input" required placeholder="Фамилия" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
											</div>
										</div>
										<div class="lk-form-row">
											<div class="lk-form-item lk-form-item_email">
												<h5>E-mail<span>*</span></h5>
												<input type="email" class="input" required placeholder="E-mail" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
												<div class="lk-email-confirmation">
													<p>E-mail не подтверждён</p>
													<a href="#" class="lk-email-confirmation__link">Подтвердить e-mail</a>
												</div>
											</div>
											<div class="lk-form-item lk-form-item_timezone">
												<h5>Часовой пояс (для садхан)<span>*</span></h5>
												<div class="lk-timezone-select">
													<select name="timezone" required>
														<option value=""><?php esc_html_e('Не выбрано', 'yoga'); ?></option>
														<?php foreach ($timezone_options as $timezone_option) : ?>
															<option value="<?php echo esc_attr($timezone_option); ?>" <?php selected($user_timezone, $timezone_option); ?>>
																<?php echo esc_html($timezone_option); ?>
															</option>
														<?php endforeach; ?>
													</select>
												</div>
											</div>
										</div>
									</div>

                                    <div class="lk-form-password">
                                        <h2>Изменить пароль</h2>
										<div class="lk-form-row lk-form-row_passwords">
											<div class="lk-form-item">
												<h5>Текущий пароль</h5>
												<div class="input-password">
													<input type="password" class="input" name="current_password" placeholder="••••••••">
													<div class="input-password__btn input-password__btn_show active">
														<svg aria-hidden="true" focusable="false">
															<use href="#password-eye-open"></use>
														</svg>
													</div>
													<div class="input-password__btn input-password__btn_hide">
														<svg aria-hidden="true" focusable="false">
															<use href="#password-eye-closed"></use>
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
															<use href="#password-eye-open"></use>
														</svg>
													</div>
													<div class="input-password__btn input-password__btn_hide">
														<svg aria-hidden="true" focusable="false">
															<use href="#password-eye-closed"></use>
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
															<use href="#password-eye-open"></use>
														</svg>
													</div>
													<div class="input-password__btn input-password__btn_hide">
														<svg aria-hidden="true" focusable="false">
															<use href="#password-eye-closed"></use>
														</svg>
													</div>
													<span class="input-password__placeholder">Пароли не совпадают</span>
												</div>
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
					<div class="lk-slide<?php echo $initial_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
						<h2>
							История пройденных практик
						</h2>
						<div class="lk-slide__content">
							<?php echo do_shortcode('[practice_history]'); ?>
						</div>
					</div>

					<div class="lk-slide<?php echo $initial_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
						<h2>
							Мои садханы
						</h2>
						<div class="lk-slide__content">
							<?php echo do_shortcode('[practice_history]'); ?>
						</div>
					</div>

					<div class="lk-slide lk-slide--notifications<?php echo $initial_lk_target === '8' ? ' active' : ''; ?>" data-target="8">
						<h2>
							Уведомления
						</h2>
						<div class="lk-slide__content">
							<div class="lk-notifications-page__actions">
								<button class="lk-notifications-page__settings" type="button" data-target="9" aria-label="<?php esc_attr_e('Настройки уведомлений', 'yoga'); ?>"><svg aria-hidden="true"><use href="#lk-sidebar-settings"></use></svg></button>
								<button class="lk-notifications-page__read-all" type="button"><?php esc_html_e('Прочитать все', 'yoga'); ?></button>
							</div>
							<?php $notifications = function_exists('yoga_get_user_notifications') ? yoga_get_user_notifications((int) $user_id) : array(); ?>
							<?php if (empty($notifications)): ?>
								<div class="lk-notifications-empty">Ничего нет...</div>
							<?php else: ?>
								<div class="lk-notifications-list">
									<?php foreach ($notifications as $notification): ?>
										<?php
										$title = (string) ($notification['title'] ?? '');
										$message = (string) ($notification['message'] ?? '');
										$url = (string) ($notification['url'] ?? '');
										$created_at = (string) ($notification['created_at'] ?? '');
										$type = (string) ($notification['type'] ?? '');
										if ($type === 'question_answer' && function_exists('yoga_get_notification_read_url')) {
											$url = yoga_get_notification_read_url($notification, 'questions');
										}
										$is_unread = empty($notification['read_at']);
										$icon = $type === 'question_answer' ? 'notification-teacher-reply-icon' : 'notification-bell-icon';
										?>
										<a class="lk-notification lk-notification--<?php echo esc_attr($type ?: 'default'); ?><?php echo $is_unread ? ' lk-notification--unread' : ''; ?>" data-notification-id="<?php echo esc_attr((string) ($notification['id'] ?? '')); ?>" data-notification-type="<?php echo esc_attr($type); ?>" href="<?php echo esc_url($url ?: '#'); ?>">
											<span class="lk-notification__head">
												<span class="lk-notification__icon"><svg aria-hidden="true"><use href="#<?php echo esc_attr($icon); ?>"></use></svg></span>
												<strong><?php echo esc_html($title); ?></strong>
												<span class="lk-notification__meta"><?php if ($created_at): ?><time><?php echo esc_html(human_time_diff(strtotime($created_at), current_time('timestamp')) . ' ' . __('назад', 'yoga')); ?></time><?php endif; ?><?php if ($is_unread && $type !== 'question_answer'): ?><i aria-hidden="true"></i><?php endif; ?></span>
											</span>
											<span class="lk-notification__message"><?php echo esc_html($message); ?></span>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="lk-slide lk-slide--notification-settings<?php echo $initial_lk_target === '9' ? ' active' : ''; ?>" data-target="9">
						<div class="lk-slide__content">
							<div class="notification-settings">
								<div class="notification-settings__title"><button type="button" class="notification-settings__back" aria-label="<?php esc_attr_e('Назад к уведомлениям', 'yoga'); ?>"><svg aria-hidden="true"><use href="#notification-settings-back"></use></svg></button><h2><?php esc_html_e('Настройки уведомлений', 'yoga'); ?></h2></div>
								<div class="notification-settings__columns"><span><?php esc_html_e('Тип уведомления', 'yoga'); ?></span><span><?php esc_html_e('На сайте', 'yoga'); ?></span><span><?php esc_html_e('На почту', 'yoga'); ?></span></div>
								<?php $notification_settings = array(
									array('Системные', 'Технические уведомления о вашем аккаунте и оплате. Часть из них отключить нельзя.', array(array('Подписка скоро заканчивается', 'За 3 дня до окончания', 1, 1), array('Срок действия карты истекает или истёк', '', 1, 0), array('Подписка закончилась', '', 1, 1))),
									array('Садхана', 'Всё, что касается садхан', array(array('Поздравление с прогрессом', 'На 7, 21, 40, 90, 120 днях', 1, 1), array('Садхана прервана', '', 0, 0), array('Садхана завершена', '', 1, 1))),
									array('Сообщения', 'Всё, что касается садхан', array(array('Ответ преподавателя или поддержки', '', 1, 0), array('Ответ на ваш комментарий от другого пользователя', '', 0, 1))),
									array('Новости', 'Новостные письма рассылаем только на почту. Отписаться можно в любой момент.', array(array('Новые крийи и медитации', '', null, 1), array('Новые статьи в блоге', '', null, 0), array('Акции и спецпредложения', '', null, 1))),
								); foreach ($notification_settings as $category): ?>
									<section class="notification-settings__category"><div class="notification-settings__category-head"><h3><?php echo esc_html($category[0]); ?></h3><p><?php echo esc_html($category[1]); ?></p></div><?php foreach ($category[2] as $row): ?><div class="notification-settings__row"><div><strong><?php echo esc_html($row[0]); ?></strong><?php if ($row[1] !== ''): ?><span><?php echo esc_html($row[1]); ?></span><?php endif; ?></div><div class="notification-settings__toggles"><?php if ($row[2] !== null): ?><button type="button" class="notification-toggle<?php echo $row[2] ? ' is-on' : ''; ?>" aria-pressed="<?php echo $row[2] ? 'true' : 'false'; ?>"></button><?php else: ?><i></i><?php endif; ?><button type="button" class="notification-toggle<?php echo $row[3] ? ' is-on' : ''; ?>" aria-pressed="<?php echo $row[3] ? 'true' : 'false'; ?>"></button></div></div><?php endforeach; ?></section>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					
					<div class="lk-slide<?php echo $initial_lk_target === '3' ? ' active' : ''; ?>" id="lk-slide-favorites" data-target="3">
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
										<?php
										$lk_practice_img = yoga_get_practice_card_image_url((int) $practice_id, 'medium');
										if ($lk_practice_img !== '') :
										?>
                                        <img src="<?php echo esc_url($lk_practice_img); ?>" alt="<?php echo esc_attr(get_the_title($practice_id)); ?>">
										<?php endif; ?>
                                    </div>
                                    <div class="kriya-fav fav active" data-practice-id="<?php echo $practice_id; ?>" role="button" tabindex="0" aria-pressed="true" aria-label="Убрать">
										<span class="kriya-fav__icon" aria-hidden="true">
											<svg><use href="#noun-heart"></use></svg>
											<svg class="active"><use href="#noun-heart-filled"></use></svg>
										</span>
										<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
										<span class="kriya-fav__text kriya-fav__text--remove">Убрать</span>
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
					<div class="lk-slide<?php echo $initial_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
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
																		<?php
																		$lk_rec_img = yoga_get_practice_card_image_url((int) $practice_id, 'medium');
																		if ($lk_rec_img !== '') :
																		?>
																		<img src="<?php echo esc_url($lk_rec_img); ?>" alt="<?php echo esc_attr(get_the_title($practice_id)); ?>">
																		<?php endif; ?>
																	</div>
																	<div class="kriya-fav fav<?php echo $is_favorite ? ' active' : ''; ?>" data-practice-id="<?php echo $practice_id; ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($is_favorite ? 'Убрать' : 'В избранное'); ?>">
																		<span class="kriya-fav__icon" aria-hidden="true">
																			<svg class="<?php echo !$is_favorite ? 'active' : ''; ?>"><use href="#noun-heart"></use></svg>
																			<svg class="<?php echo $is_favorite ? 'active' : ''; ?>"><use href="#noun-heart-filled"></use></svg>
																		</span>
																		<span class="kriya-fav__text kriya-fav__text--add">В избранное</span>
																		<span class="kriya-fav__text kriya-fav__text--remove">Убрать</span>
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
					<div class="lk-slide<?php echo $initial_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
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
					<div class="lk-slide<?php echo $initial_lk_target === '6' ? ' active' : ''; ?>" id="lk-slide-settings" data-target="6">
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

									<?php
									$ytr_auto_renew_active = class_exists('YTR_LK')
										? YTR_LK::user_has_renewable_payment_setup($user_id)
										: (class_exists('YTR_User') && YTR_User::is_auto_renew_enabled($user_id));
									if ($current_subscription && $ytr_auto_renew_active) :
										$subscription_end_label = date('d.m.Y', strtotime($current_subscription['end_date']));
									?>
									<div class="lk-settings-part lk-settings-part_cancel">
										<button
											type="button"
											class="btn lk-cancel-subscription-btn"
											id="ytr-cancel-subscription-btn"
											data-access-end="<?php echo esc_attr($subscription_end_label); ?>"
										>
											<span><?php esc_html_e('Отменить автопродление', 'yoga'); ?></span>
										</button>
										<p class="lk-settings-item__col-text lk-cancel-subscription-hint">
											<?php
											printf(
												/* translators: %s: subscription end date */
												esc_html__('Отключит автопродление. Доступ сохранится до %s.', 'yoga'),
												esc_html($subscription_end_label)
											);
											?>
										</p>
									</div>
									<?php elseif ($current_subscription) :
										$subscription_end_label = date('d.m.Y', strtotime($current_subscription['end_date']));
										$ytr_status_text      = class_exists('YTR_LK')
											? YTR_LK::get_auto_renew_status_text($user_id, $subscription_end_label)
											: '';
										$ytr_status_off       = class_exists('YTR_LK') && YTR_LK::was_auto_renew_cancelled($user_id);
									?>
									<div class="lk-settings-part lk-settings-part_cancel" id="ytr-auto-renew-status">
										<div class="lk-auto-renew-status<?php echo $ytr_status_off ? ' lk-auto-renew-status_off' : ''; ?>" role="status">
											<p class="lk-auto-renew-status__title">
												<?php
												echo $ytr_status_off
													? esc_html__('Автопродление отключено', 'yoga')
													: esc_html__('Автопродление не подключено', 'yoga');
												?>
											</p>
											<?php if ($ytr_status_text !== '') : ?>
												<p class="lk-auto-renew-status__text"><?php echo esc_html($ytr_status_text); ?></p>
											<?php endif; ?>
										</div>
									</div>
									<?php endif; ?>
									
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
										<p class="lk-settings-item__col-text" style="margin-bottom: 1rem;">
											Полные данные карты на сайте не хранятся. Карту можно привязать через ЮKassa кнопкой «Добавить карту» или она сохранится автоматически при оплате тарифа с галочкой сохранения способа оплаты.
											<?php if ($current_subscription) : ?>
												<br>Чтобы отключить автопродление, удалите карту с пометкой «Для автопродления». Доступ сохранится до <?php echo esc_html(date('d.m.Y', strtotime($current_subscription['end_date']))); ?>.
											<?php endif; ?>
										</p>
										<?php
											$saved_cards = get_user_saved_cards();
											$ytr_auto_renew = class_exists('YTR_User') && YTR_User::is_auto_renew_enabled($user_id);
											if (!empty($saved_cards)) {
												foreach ($saved_cards as $card) {
													$icon_type = preg_replace('/[^a-z0-9_-]/', '', (string) ($card['type'] ?? 'default'));
													if ($icon_type === '') {
														$icon_type = 'default';
													}
													$is_auto_card = !empty($card['is_auto'])
														|| ($ytr_auto_renew && count($saved_cards) === 1);
												?>
												<div
													class="lk-settings-item lk-settings-item_card"
													role="button"
													tabindex="0"
													data-card-id="<?php echo esc_attr($card['id']); ?>"
													data-is-auto="<?php echo $is_auto_card ? '1' : '0'; ?>"
													data-last4="<?php echo esc_attr($card['last4']); ?>"
													data-brand-name="<?php echo esc_attr($card['brand']); ?>"
													data-icon-type="<?php echo esc_attr($icon_type); ?>"
													aria-label="<?php echo esc_attr(sprintf(__('Управление картой %s', 'yoga'), $card['brand'] . ' •••• ' . $card['last4'])); ?>"
												>
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-icon">
															<?php yoga_lk_render_payment_card_icon($icon_type, (string) $card['brand']); ?>
														</div>
														<p class="lk-settings-item__col-text">
															<?php echo esc_html($card['brand'] . ' •••• ' . $card['last4']); ?>
															<?php if ($is_auto_card) : ?>
																<br><small>Для автопродления</small>
															<?php endif; ?>
														</p>
													</div>
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-action">
															<div class="lk-settings-item__col-action-options" aria-hidden="true">
																<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/lk-payment-options.png'); ?>" alt="">
															</div>
														</div>
													</div>
												</div>
												<?php
												}
											} else {
												echo '<p>У вас нет сохранённых карт. Нажмите «Добавить карту» для привязки через ЮKassa или оплатите тариф банковской картой.</p>';
											}
										?>

										<button type="button" class="lk-add-card js-ytr-bind-card" id="add-new-card">
											<span class="lk-add-card__icon" aria-hidden="true">
												<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/lk-payment-icon_add.png'); ?>" alt="" width="50" height="50">
											</span>
											<span class="lk-add-card__text">Добавить карту</span>
										</button>
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
        <div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
        <nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-user" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Мои данные</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-history" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">История практик</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 16" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-lotus" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Мои садханы</span>
				<span class="sidebar-menu__badge" aria-label="<?php esc_attr_e('6 садхан', 'yoga'); ?>">6</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '3' ? ' active' : ''; ?>" data-target="3">
				<div class="sidebar-menu__item-icon sidebar-menu__item-icon--heart">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 17.4 15.4852" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-heart" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Избранное</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="-0.6 -0.6 18.2 18.2" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-smile" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Рекомендации</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-question" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Мои вопросы</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '8' ? ' active' : ''; ?>" data-target="8">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 22 22" aria-hidden="true" focusable="false">
						<use href="#notification-bell-icon" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Уведомления</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '6' ? ' active' : ''; ?>" data-target="6">
				<div class="sidebar-menu__item-icon">
					<svg class="sidebar-menu__item-svg" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<use href="#lk-sidebar-settings" width="100%" height="100%"></use>
					</svg>
				</div>
				<span class="sidebar-menu__label">Настройки подписки</span>
			</div>
		</nav>
		</div>

		<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

        <div class="sidebar-exit modal-call modal-call_logout">
			<div class="sidebar-exit__icon">
				<svg class="sidebar-exit__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<use href="#lk-sidebar-logout" width="100%" height="100%"></use>
				</svg>
			</div>
			<span class="sidebar-menu__label">Выйти</span>
		</div>
	</div>
</div>
<?php
	get_footer();
