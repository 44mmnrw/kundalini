<?php
/**
 * Шаблон страницы: lk.
 *
 * @package Yoga
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
	$unread_question_answers_count = function_exists('yoga_get_unread_question_answer_notifications')
		? count(yoga_get_unread_question_answer_notifications((int) $user_id))
		: 0;
	$unread_notifications_count = function_exists('yoga_get_unread_user_notifications')
		? count(yoga_get_unread_user_notifications((int) $user_id))
		: 0;
	$email_verified = function_exists('yoga_is_user_email_verified') && yoga_is_user_email_verified($user_id);
	$user_timezone = get_user_meta($user_id, 'timezone', true);
	$user_timezone = is_string($user_timezone) ? $user_timezone : '';
	$timezone_options = function_exists('yoga_get_russian_timezone_options')
		? yoga_get_russian_timezone_options()
		: array();
	$requested_lk_section = function_exists('yoga_get_requested_lk_section') ? yoga_get_requested_lk_section() : '';
	$initial_lk_target = function_exists('yoga_get_initial_lk_target') ? yoga_get_initial_lk_target() : '1';
	$user_avatar_id = function_exists('yoga_get_user_avatar_id') ? yoga_get_user_avatar_id($user_id) : 0;
	$lk_sprite_file = get_template_directory() . '/assets/svg/sprite.svg';
	$lk_sprite_url = add_query_arg(
		'ver',
		file_exists($lk_sprite_file) ? (string) filemtime($lk_sprite_file) : wp_get_theme()->get('Version'),
		get_template_directory_uri() . '/assets/svg/sprite.svg'
	);
	$lk_secondary_nav_urls = function_exists('yoga_lk_sidebar_secondary_nav_urls')
		? yoga_lk_sidebar_secondary_nav_urls()
		: array();
	$lk_library_url = (string) ($lk_secondary_nav_urls['library'] ?? home_url('/'));
?>


<section class="section-lk" id="section-lk" data-initial-target="<?php echo esc_attr($initial_lk_target); ?>" data-server-routed="<?php echo $requested_lk_section !== '' ? '1' : '0'; ?>">
    <div class="lk-layout">
        <main class="lk-layout__content">
            <div class="container">
                <div class="row">
            <div class="lk">
                <div class="lk__slides">

					<div class="lk-slide<?php echo $initial_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
						<div class="lk-page-header">
							<h1 class="lk-page-title">Мои данные</h1>
						</div>
                        <div class="lk-slide__content">
                            <form action="#" class="lk-form" id="profile-form" enctype="multipart/form-data">
                                <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>

								<div class="lk-form__photo">
									<div class="photo-input">
										<div class="photo-input-custom">
											<div class="photo-input-custom__inner">
								<div class="photo-input-custom__inner-photo<?php echo $user_avatar_id > 0 ? ' has-avatar' : ''; ?>" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Upload photo', 'yoga' ); ?>">
									<button type="button" class="photo-input-delete" aria-label="<?php esc_attr_e('Удалить аватар', 'yoga'); ?>"<?php echo $user_avatar_id > 0 ? '' : ' hidden'; ?>>
										<svg viewBox="0 0 9 9" aria-hidden="true" focusable="false"><use href="<?php echo esc_url($lk_sprite_url); ?>#lk-avatar-delete"></use></svg>
									</button>
									<?php if ($user_avatar_id > 0) : ?>
										<?php echo wp_get_attachment_image($user_avatar_id, 'thumbnail', false, array('class' => 'avatar', 'alt' => '')); ?>
									<?php else : ?>
										<svg class="photo-input-custom__icon" viewBox="0 0 687.88 550.44" aria-hidden="true" focusable="false">
											<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-upload-camera'); ?>"></use>
										</svg>
									<?php endif; ?>
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
											<h5>эл. почта<span>*</span></h5>
												<input type="email" class="input" required placeholder="эл. почта" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
								<div class="lk-email-confirmation<?php echo $email_verified ? ' is-verified' : ''; ?>">
									<p><?php echo $email_verified ? 'эл. почта подтверждена' : 'эл. почта не подтверждена'; ?></p>
									<?php if (!$email_verified) : ?>
										<a href="#" class="lk-email-confirmation__link">Подтвердить эл. почту</a>
										<div class="lk-email-verification" hidden>
											<p>Введите 6-значный код из письма</p>
											<div class="lk-email-verification__controls">
												<input type="text" class="input lk-email-verification__code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
												<button type="button" class="btn lk-email-verification__verify"><span>Подтвердить</span></button>
											</div>
											<button type="button" class="lk-email-verification__resend">Отправить код повторно</button>
											<p class="lk-email-verification__message" role="status" aria-live="polite"></p>
										</div>
									<?php endif; ?>
								</div>
											</div>
											<div class="lk-form-item lk-form-item_timezone">
												<h5>Часовой пояс (для садхан)<span>*</span></h5>
												<div class="lk-timezone-select">
													<select name="timezone" required>
														<option value=""><?php esc_html_e('Не выбрано', 'yoga'); ?></option>
															<?php foreach ($timezone_options as $timezone_value => $timezone_label) : ?>
																<option value="<?php echo esc_attr($timezone_value); ?>" <?php selected($user_timezone, $timezone_value); ?>>
																	<?php echo esc_html($timezone_label); ?>
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
												<input type="password" class="input" name="current_password">
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


					<div class="lk-slide lk-slide--practice-history<?php echo $initial_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
						<div class="lk-page-header">
							<h2 class="lk-page-title">История пройденных практик</h2>
						</div>
						<div class="lk-slide__content">
							<?php


							$practice_history_preview = array(
								array('level' => 'Начинающий', 'title' => 'Сат Крийя', 'excerpt' => 'усиливает поток энергии Кундалини, укрепляет нервную систему'),
								array('level' => 'Средний', 'title' => 'Содаршан Чакра Крийя', 'excerpt' => 'очищает карму, улучшает ментальную концентрацию, балансирует нервную систему и повышает осознанность'),
								array('level' => 'Начинающий', 'title' => 'Набхи Крийя', 'excerpt' => 'укрепляет мышцы живота, активирует пупочный центр, улучшает пищеварение и энергетический баланс'),
								array('level' => 'Продвинутый', 'title' => 'Сурья Крийя', 'excerpt' => 'заряжает энергией, улучшает обмен веществ, развивает силу и выносливость'),
								array('level' => 'Начинающий', 'title' => 'Крийя для Солнечного сплетения и сердца', 'excerpt' => 'заряжает энергией, улучшает обмен веществ, развивает силу и выносливость'),
								array('level' => 'Средний', 'title' => 'Крийя Гибкость позвоночника', 'excerpt' => 'укрепляет спину, улучшает циркуляцию энергии, стимулирует нервную систему'),
								array('level' => 'Средний', 'title' => 'Крийя "Баланс праны и апаны"', 'excerpt' => 'уравновешивает потоки энергии в теле, очищает организм, помогает эмоциональному балансу'),
								array('level' => 'Средний', 'title' => 'Крийя для очищения лимфатической системы', 'excerpt' => 'стимулирует лимфоток, улучшает детоксикацию, поддерживает иммунитет'),
							);
							$practice_history_image = get_template_directory_uri() . '/assets/img/kriya-img_01.png';
							?>
							<div class="practice-history-preview" data-history-source="preview">
								<div class="practice-history-preview__list">
									<?php foreach ($practice_history_preview as $history_item): ?>
										<article class="practice-history-card">
											<div class="practice-history-card__content">
												<span class="practice-history-card__level"><?php echo esc_html($history_item['level']); ?></span>
												<div class="practice-history-card__copy">
													<h3><?php echo esc_html($history_item['title']); ?></h3>
													<p><?php echo esc_html($history_item['excerpt']); ?></p>
												</div>
												<span class="practice-history-card__access"><span aria-hidden="true">&#128274;</span> Доступно на платном тарифе</span>
											</div>
											<div class="practice-history-card__media">
												<img src="<?php echo esc_url($practice_history_image); ?>" alt="">
												<span class="practice-history-card__arrow" aria-hidden="true">&#8599;</span>
											</div>
										</article>
									<?php endforeach; ?>
								</div>
								<button class="practice-history-preview__more" type="button" disabled aria-disabled="true">Показать ещё</button>
							</div>
						</div>
					</div>

					<div class="lk-slide<?php echo $initial_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
						<div class="lk-page-header">
							<h2 class="lk-page-title">Мои садханы</h2>
						</div>
						<div class="lk-slide__content"></div>
					</div>

					<div class="lk-slide lk-slide--notifications<?php echo $initial_lk_target === '8' ? ' active' : ''; ?>" data-target="8">
						<div class="lk-page-header">
							<h2 class="lk-page-title">Уведомления</h2>
							<div class="lk-notifications-page__actions">
								<button class="lk-notifications-page__settings" type="button" data-target="9" aria-label="<?php esc_attr_e('Настройки уведомлений', 'yoga'); ?>"><svg aria-hidden="true"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#lk-sidebar-settings'); ?>"></use></svg></button>
								<?php if ($unread_notifications_count > 0): ?>
									<button class="lk-notifications-page__read-all" type="button"><?php esc_html_e('Прочитать все', 'yoga'); ?></button>
								<?php endif; ?>
							</div>
						</div>
						<div class="lk-slide__content">
							<?php $notifications = function_exists('yoga_get_user_notifications') ? yoga_get_user_notifications((int) $user_id, 100) : array(); ?>
							<?php if (empty($notifications)): ?>
								<div class="lk-notifications-empty" role="status">
									<span class="lk-notifications-empty__icon" aria-hidden="true">
										<svg><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#notification-bell-icon'); ?>"></use></svg>
									</span>
									<div class="lk-notifications-empty__text">
										<h3><?php esc_html_e('Здесь пока ничего нет', 'yoga'); ?></h3>
										<p><?php esc_html_e('Здесь появятся уведомления', 'yoga'); ?></p>
									</div>
								</div>
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
										if ($type === 'comment_reply') {
											$icon = 'notification-comment-reply-icon';
										}
										if ($type === 'payment_card_expiring') {
											$icon = 'notification-payment-card-icon';
										}
										if ($type === 'subscription_expiring') {
											$icon = 'notification-subscription-expiring-icon';
										}
										?>
										<a class="lk-notification lk-notification--<?php echo esc_attr($type ?: 'default'); ?><?php echo $is_unread ? ' lk-notification--unread' : ''; ?>" data-notification-id="<?php echo esc_attr((string) ($notification['id'] ?? '')); ?>" data-notification-type="<?php echo esc_attr($type); ?>" href="<?php echo esc_url($url ?: '#'); ?>">
											<span class="lk-notification__head">
												<span class="lk-notification__icon"><svg aria-hidden="true"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . $icon); ?>"></use></svg></span>
												<strong><?php echo esc_html($title); ?></strong>
											</span>
											<span class="lk-notification__message"><?php echo esc_html($message); ?></span>
											<span class="lk-notification__meta"><?php if ($created_at): ?><time><?php echo esc_html(human_time_diff(strtotime($created_at), current_time('timestamp')) . ' ' . __('назад', 'yoga')); ?></time><?php endif; ?><?php if ($is_unread): ?><i aria-hidden="true"></i><?php endif; ?></span>
											<?php if ($type === 'payment_card_expiring'): ?>
												<span class="lk-notification__action"><?php esc_html_e('Обновить карту', 'yoga'); ?></span>
											<?php endif; ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="lk-slide lk-slide--notification-settings<?php echo $initial_lk_target === '9' ? ' active' : ''; ?>" data-target="9">
						<div class="lk-slide__content">
							<div class="notification-settings">
								<div class="notification-settings__title"><button type="button" class="notification-settings__back" aria-label="<?php esc_attr_e('Назад к уведомлениям', 'yoga'); ?>"><svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use></svg></button><h2><?php esc_html_e('Настройки уведомлений', 'yoga'); ?></h2></div>
								<div class="notification-settings__columns"><span><?php esc_html_e('Тип уведомления', 'yoga'); ?></span><span><?php esc_html_e('На сайте', 'yoga'); ?></span><span><?php esc_html_e('На почту', 'yoga'); ?></span></div>
								<?php $notification_settings = array(
									array('Системные', 'Технические уведомления о вашем аккаунте и оплате. Часть из них отключить нельзя.', array(
										array('Подписка скоро заканчивается', 'За 3 дня до окончания', 1, 1, 'subscription_expiring_site', 'subscription_expiring_email'),
										array('Срок действия карты истекает или истёк', '', 1, 0, 'payment_card_expiring_site', 'payment_card_expiring_email'),
										array('Подписка закончилась', '', 1, 1, 'subscription_ended_site', 'subscription_ended_email'),
									)),
									array('Садхана', 'Всё, что касается садхан', array(
										array('Поздравление с прогрессом', 'На 7, 21, 40, 90, 120 днях', 1, 1, '', ''),
										array('Садхана прервана', '', 0, 0, '', ''),
										array('Садхана завершена', '', 1, 1, '', ''),
									)),
									array('Сообщения', 'Ответы преподавателя, поддержки и других пользователей.', array(
										array('Ответ преподавателя или поддержки', '', 1, 0, 'question_answer_site', 'question_answer_email'),
										array('Ответ на ваш комментарий от другого пользователя', '', 0, 1, 'comment_reply_site', 'comment_reply_email'),
									)),
									array('Новости', 'Новостные письма рассылаем только на почту. Отписаться можно в любой момент.', array(
										array('Новые крийи и медитации', '', null, 1, '', 'new_practices_email'),
										array('Новые статьи в блоге', '', null, 0, '', 'new_articles_email'),
										array('Акции и спецпредложения', '', null, 1, '', 'promotions_email'),
									)),
								); foreach ($notification_settings as $category): ?>
									<section class="notification-settings__category">
										<div class="notification-settings__category-head"><h3><?php echo esc_html($category[0]); ?></h3><p><?php echo esc_html($category[1]); ?></p></div>
										<?php foreach ($category[2] as $row): ?>
											<?php
											$site_key = (string) ($row[4] ?? '');
											$email_key = (string) ($row[5] ?? '');
											$site_enabled = $site_key !== '' ? yoga_notification_preference((int) $user_id, $site_key, (bool) $row[2]) : (bool) $row[2];
											$email_enabled = $email_key !== '' ? yoga_notification_preference((int) $user_id, $email_key, (bool) $row[3]) : (bool) $row[3];
											?>
											<div class="notification-settings__row">
												<div><strong><?php echo esc_html($row[0]); ?></strong><?php if ($row[1] !== ''): ?><span><?php echo esc_html($row[1]); ?></span><?php endif; ?></div>
												<div class="notification-settings__toggles">
													<?php if ($row[2] !== null): ?><button type="button" class="notification-toggle<?php echo $site_enabled ? ' is-on' : ''; ?>"<?php if ($site_key !== ''): ?> data-preference-key="<?php echo esc_attr($site_key); ?>"<?php else: ?> disabled aria-disabled="true"<?php endif; ?> aria-pressed="<?php echo $site_enabled ? 'true' : 'false'; ?>"></button><?php else: ?><i></i><?php endif; ?>
													<button type="button" class="notification-toggle<?php echo $email_enabled ? ' is-on' : ''; ?>"<?php if ($email_key !== ''): ?> data-preference-key="<?php echo esc_attr($email_key); ?>"<?php else: ?> disabled aria-disabled="true"<?php endif; ?> aria-pressed="<?php echo $email_enabled ? 'true' : 'false'; ?>"></button>
												</div>
											</div>
										<?php endforeach; ?>
									</section>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="lk-slide<?php echo $initial_lk_target === '3' ? ' active' : ''; ?>" id="lk-slide-favorites" data-target="3">
						<?php
							$favorites = get_user_meta($user_id, 'favorite_practices', true);
							if (is_string($favorites)) {
								$favorites = array_filter(array_map('trim', explode(',', $favorites)));
							}
							if (!is_array($favorites)) {
								$favorites = array();
							}
							$favorites = array_values(array_unique(array_filter(array_map('intval', $favorites))));
						?>
						<div class="lk-page-header">
							<h2 class="lk-page-title">Избранное</h2>
							<?php if (!empty($favorites)) : ?>
								<button class="lk-favorites-clear" type="button">Очистить</button>
							<?php endif; ?>
						</div>
						<div class="lk-slide__content">
							<?php
							if (empty($favorites)) :
								?>
								<div class="no-favorites lk-favorites-empty">
									<div class="lk-favorites-empty__message">
										<span class="lk-favorites-empty__icon" aria-hidden="true"><svg><use href="<?php echo esc_url($lk_sprite_url); ?>#site-heart"></use></svg></span>
										<div class="lk-favorites-empty__text">
											<h3>Здесь пока ничего нет</h3>
											<p>Здесь появятся крийи, когда вы их добавите в избранное</p>
										</div>
									</div>
									<a class="lk-favorites-empty__button" href="<?php echo esc_url($lk_library_url); ?>">
										<span>В библиотеку практик</span>
										<i aria-hidden="true"><svg><use href="<?php echo esc_url($lk_sprite_url); ?>#button-diagonal-arrow"></use></svg></i>
									</a>
								</div>
								<?php
							else :
							?>
							<div class="lk-kriyi">
								<div class="kriyi">
									<div class="kriyi__items">
										<?php
										foreach ($favorites as $practice_id) {
                    $practice = get_post($practice_id);


                    if ($practice && $practice->post_type == 'practice') {

                        $level = get_the_terms($practice_id, 'practice-type');
                        $level_name = !empty($level) ? $level[0]->name : 'Не указан';


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
											<svg><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-heart-filled'); ?>"></use></svg>
										</span>
                                    </div>
                                    <div class="kriya-btn">
                                        <a href="<?php echo get_permalink($practice_id); ?>" class="kriya-btn__arrow">
                                            <svg class="kriya-btn__icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use></svg>
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


					<div class="lk-slide<?php echo $initial_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
						<div class="lk-page-header">
							<h2 class="lk-page-title">Рекомендации</h2>
						</div>
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
																																		<svg><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . ($is_favorite ? 'site-heart-filled' : 'site-heart')); ?>"></use></svg>
																		</span>
																	</div>
																	<div class="kriya-btn">
																		<a href="<?php echo get_permalink($practice_id); ?>" class="kriya-btn__arrow">
																										<svg class="kriya-btn__icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use></svg>
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



					<div class="lk-slide<?php echo $initial_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
						<div class="lk-page-header">
							<h2 class="lk-page-title">Мои вопросы</h2>
						</div>
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
									if (is_user_logged_in() && function_exists('yoga_render_user_questions_list')) {
										yoga_render_user_questions_list((int) get_current_user_id());
										} else {
										echo '<p>Пожалуйста, авторизуйтесь для просмотра ваших вопросов.</p>';
									}
								?>
							</div>
						</div>
					</div>


					<div class="lk-slide<?php echo $initial_lk_target === '6' ? ' active' : ''; ?>" id="lk-slide-settings" data-target="6">
						<div class="lk-page-header">
							<h2 class="lk-page-title">Настройки подписки</h2>
						</div>
						<div class="lk-slide__content">
							<div class="lk-settings">
								<div class="lk-settings__slide lk-settings__slide_main active" data-target="1">
									<?php
									$current_subscription = get_user_active_subscription();
									$subscription_end_label = $current_subscription
										? date('d.m.Y', strtotime($current_subscription['end_date']))
										: '—';
									$ytr_auto_renew_active = class_exists('YTR_LK')
										? YTR_LK::user_has_renewable_payment_setup($user_id)
										: (class_exists('YTR_User') && YTR_User::is_auto_renew_enabled($user_id));
									$tariffs_url = home_url('/product-category/tariffs/');
									$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
									if ($tariffs_term && !is_wp_error($tariffs_term)) {
										$term_link = get_term_link($tariffs_term);
										if (!is_wp_error($term_link)) {
											$tariffs_url = $term_link;
										}
									}
									?>
									<?php if ($current_subscription) : ?>
									<div class="lk-settings-part lk-settings-part_status">
										<div class="lk-settings-item lk-settings-item_main">
											<div class="lk-settings-item__col">
												<p class="lk-settings-item__col-text">Ваш тариф:</p>
												<div class="personal-status">
											<svg class="personal-status__icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#personal-status-crown'); ?>"></use></svg>
													<span>
														<?php echo esc_html($current_subscription ? $current_subscription['name'] : 'Не активен'); ?>
													</span>
												</div>
											</div>
											<div class="lk-settings-item__col lk-settings-item__col_subscription-meta">
												<p class="lk-settings-item__col-text">Действует до:</p>
												<time><?php echo esc_html($subscription_end_label); ?></time>
												<?php if ($current_subscription && $ytr_auto_renew_active) : ?>
													<button
														type="button"
														class="lk-cancel-subscription-btn"
														id="ytr-cancel-subscription-btn"
														data-access-end="<?php echo esc_attr($subscription_end_label); ?>"
													>Отменить подписку</button>
												<?php endif; ?>
											</div>
										</div>
									</div>
									<?php endif; ?>

									<?php if ($current_subscription && !$ytr_auto_renew_active) :
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

									<div class="lk-settings-part lk-settings-part_payment-methods">
										<h4>Способы оплаты</h4>
										<div class="lk-settings-item lk-settings-item_action" data-target="2">
											<div class="lk-settings-item__col">
											<div class="lk-settings-item__col-icon">
													<svg class="lk-settings-payment-icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#notification-payment-card-icon'); ?>"></use></svg>
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
												<svg aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use></svg>
											</div>
												</div>
											</div>
										</div>
									</div>

									<div class="lk-settings-part lk-settings-part_purchase-history">
										<h4>История покупок</h4>
										<div class="lk-settings-purchases">
										<?php
											$orders = get_user_orders_history();
											if (!empty($orders)) {
												foreach ($orders as $order) {
												?>
												<div class="lk-settings-item lk-settings-purchase">
													<time><?php echo esc_html(date('d.m.Y', strtotime($order['date']))); ?></time>
													<strong><?php echo esc_html($order['product_name']); ?></strong>
													<span><?php echo wp_kses_post(wc_price($order['total'])); ?></span>
												</div>
												<?php
												}
												} else {
												?>
												<div class="lk-settings-empty-history">
													<p>Покупок пока не было. Оформите подписку на один из наших тарифов, чтобы покупка добавилась в список.</p>
													<a href="<?php echo esc_url($tariffs_url); ?>" class="lk-settings-tariffs-btn">
														<span>Выбрать тариф</span>
														<span class="lk-settings-tariffs-btn__icon" aria-hidden="true">
															<svg class="lk-settings-tariffs-btn__arrow" width="16" height="16" viewBox="0 0 16 16" focusable="false">
																		<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
															</svg>
														</span>
													</a>
												</div>
												<?php
											}
										?>
										</div>
									</div>

								</div>

								<div class="lk-settings__slide lk-settings__slide_payment" data-target="2">
									<div class="form-back" data-target="1">
										<svg class="form-back__icon" width="9" height="16" aria-hidden="true" focusable="false">
											<use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#site-arrow'); ?>"></use>
										</svg>
										<span>назад</span>
									</div>
									<h2>Способы оплаты</h2>
									<div class="lk-settings-part lk-settings-part_cards">
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
														</p>
													</div>
													<div class="lk-settings-item__col">
														<div class="lk-settings-item__col-action">
															<div class="lk-settings-item__col-action-options" aria-hidden="true">
																<svg class="lk-settings-item__col-action-option" width="4" height="4" viewBox="0 0 4 4" fill="none"><circle cx="2" cy="2" r="2" fill="currentColor"/></svg>
																<svg class="lk-settings-item__col-action-option" width="4" height="4" viewBox="0 0 4 4" fill="none"><circle cx="2" cy="2" r="2" fill="currentColor"/></svg>
																<svg class="lk-settings-item__col-action-option" width="4" height="4" viewBox="0 0 4 4" fill="none"><circle cx="2" cy="2" r="2" fill="currentColor"/></svg>
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
											<span class="lk-add-card__icon" aria-hidden="true"></span>
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
        </main>
        <aside class="sidebar" aria-label="Personal account navigation">
	<div class="sidebar-inner">
        <div class="sidebar-menu-lk-group sidebar-menu-lk-group--primary">
        <nav class="sidebar-menu" aria-label="<?php esc_attr_e('Разделы личного кабинета', 'yoga'); ?>">
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '1' ? ' active' : ''; ?>" data-target="1">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-user', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Мои данные</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '2' ? ' active' : ''; ?>" data-target="2">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-history', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">История практик</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '7' ? ' active' : ''; ?>" data-target="7">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-lotus', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Мои садханы</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '3' ? ' active' : ''; ?>" data-target="3">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-heart', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Избранное</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '4' ? ' active' : ''; ?>" data-target="4">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-smile', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Рекомендации</span>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '5' ? ' active' : ''; ?>" data-target="5">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-question', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Мои вопросы</span>
				<?php if ($unread_question_answers_count > 0) : ?>
					<span class="sidebar-menu__count" aria-label="<?php echo esc_attr(sprintf(_n('%d непрочитанный ответ', '%d непрочитанных ответов', $unread_question_answers_count, 'yoga'), $unread_question_answers_count)); ?>"><?php echo esc_html((string) $unread_question_answers_count); ?></span>
				<?php endif; ?>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '8' ? ' active' : ''; ?>" data-target="8">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk_bell', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Уведомления</span>
				<?php if ($unread_notifications_count > 0) : ?>
					<span class="sidebar-menu__count" aria-label="<?php echo esc_attr(sprintf(_n('%d непрочитанное уведомление', '%d непрочитанных уведомлений', $unread_notifications_count, 'yoga'), $unread_notifications_count)); ?>"><?php echo esc_html((string) $unread_notifications_count); ?></span>
				<?php endif; ?>
			</div>
			<div class="sidebar-menu__item<?php echo $initial_lk_target === '6' ? ' active' : ''; ?>" data-target="6">
				<div class="sidebar-menu__item-icon">
					<?php yoga_render_lk_menu_icon('lk-sidebar-settings', 'sidebar-menu__item-svg'); ?>
				</div>
				<span class="sidebar-menu__label">Настройки подписки</span>
			</div>
		</nav>
		</div>

		<hr class="sidebar-menu-sep sidebar-menu-sep--before-logout" aria-hidden="true">

        <div class="sidebar-exit modal-call modal-call_logout">
			<div class="sidebar-exit__icon">
				<?php yoga_render_lk_menu_icon('lk-sidebar-logout', 'sidebar-exit__svg'); ?>
			</div>
			<span class="sidebar-menu__label">Выйти</span>
		</div>
	</div>
        </aside>
    </div>
</section>
<?php
	get_footer();
