<?php
	@ini_set( 'upload_max_size' , '256M' );
	@ini_set( 'post_max_size', '256M');
	@ini_set( 'max_execution_time', '300' );
	// Регистрация меню
	function my_theme_setup() {
		register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'yoga' ),
        'footer'  => __( 'Footer Menu', 'yoga' ),
		) );
		add_theme_support( 'post-thumbnails' );
	}
	add_action( 'after_setup_theme', 'my_theme_setup' );
	
	// Подключение стилей и скриптов
	function my_theme_scripts() {
		$theme_uri = get_template_directory_uri();
		
		// Стили
		wp_enqueue_style( 'main-style', $theme_uri . '/assets/css/style.css', array(), '1.0.0' );
		wp_enqueue_style( 'mulish-style', $theme_uri . '/assets/css/mulish.css', array(), '1.0.0' );
		wp_enqueue_style( 'animate-style', $theme_uri . '/assets/css/animate.css', array(), '1.0.0' );
		
		// Скрипты (jQuery уже входит в состав WordPress)
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'spincrement', $theme_uri . '/assets/js/jquery.spincrement.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'machheight', $theme_uri . '/assets/js/machheight.js', array('jquery'), null, true );
		wp_enqueue_script( 'wow', $theme_uri . '/assets/js/wow.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'slick', $theme_uri . '/assets/slick/slick.min.js', array('jquery'), null, true );
		wp_enqueue_script( 'maskedinput', $theme_uri . '/assets/js/jquery.maskedinput.js', array('jquery'), null, true );
		
		wp_enqueue_script( 'fancybox', $theme_uri . '/assets/libs/fancybox/jquery.fancybox.min.js', array('jquery', 'slick'), null, true );
		
		wp_enqueue_script( 'main-script', $theme_uri . '/assets/js/script.js', array('jquery', 'slick', 'fancybox'), '1.0.0', true );
		
		// Plyr CSS
		wp_enqueue_style('plyr-css', get_template_directory_uri() . '/assets/css/plyr.css');
		
		wp_enqueue_style('plyr-audio-custom', get_template_directory_uri() . '/assets/css/plyr-custom.css');
		
		// Plyr JS - загружаем первым
		wp_enqueue_script('plyr-js', get_template_directory_uri() . '/assets/js/plyr.min.js', array(), '3.7.8', true);
		
		// Кастомный скрипт - зависит от plyr-js и jQuery
		wp_enqueue_script('practice-player', get_template_directory_uri() . '/assets/js/practice-player.js', 
        array('plyr-js', 'jquery'), '1.0.0', true);
		
		
		
		
		// Локализация базовых строк (переводы/подписи)
		wp_localize_script('practice-js', 'practiceI18n', [
		'pause' => 'Пауза',
		'play' => 'Пуск',
		'next' => 'Далее',
		'prev' => 'Назад',
		'stage' => 'Этап',
		'locked' => 'Доступ только по подписке',
		'demo_over' => 'Демо-фрагмент завершён',
		]);
	}
	add_action( 'wp_enqueue_scripts', 'my_theme_scripts' );
	
	// Опции ACF
	if( function_exists('acf_add_options_page') ) {
		acf_add_options_page(array(
        'page_title'    => 'Общие настройки темы',
        'menu_title'    => 'Настройки темы',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
		));
	}
	
	// Обработчик AJAX для формы подписки
	function yoga_subscribe_handler() {
		// Проверка nonce для безопасности
		if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
			wp_die('Ошибка безопасности');
		}
		
		$email = sanitize_email($_POST['email']);
		
		if (!is_email($email)) {
			wp_send_json_error('Некорректный email адрес');
		}
		
		// Здесь можно добавить логику подписки:
		// - Добавление в базу данных
		// - Интеграция с сервисом рассылок (Mailchimp, SendPulse и т.д.)
		// - Отправка уведомления администратору
		
		// Пример: сохранение в опции WordPress
		$subscribers = get_option('yoga_subscribers', array());
		if (!in_array($email, $subscribers)) {
			$subscribers[] = $email;
			update_option('yoga_subscribers', $subscribers);
			
			// Отправка email администратору (опционально)
			$admin_email = get_option('admin_email');
			$subject = 'Новый подписчик на сайте ' . get_bloginfo('name');
			$message = "Новый email подписчика: $email\n";
			$message .= "Время: " . current_time('mysql') . "\n";
			wp_mail($admin_email, $subject, $message);
		}
		
		wp_send_json_success('Подписка успешно оформлена');
	}
	add_action('wp_ajax_yoga_subscribe', 'yoga_subscribe_handler');
	add_action('wp_ajax_nopriv_yoga_subscribe', 'yoga_subscribe_handler');
	
	// Локализация AJAX параметров
	function yoga_ajax_localization() {
		
		$current_user = wp_get_current_user();
		
		wp_localize_script('main-script', 'yoga_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yoga_ajax_nonce'),
        'user_id' => get_current_user_id(),
        'user_logged_in' => is_user_logged_in(),
        'user_email' => $current_user->user_email,
        'site_url' => home_url(),
        'post_id' => get_the_ID()
		));
	}
	add_action('wp_enqueue_scripts', 'yoga_ajax_localization');
	
// Функция шаблона комментария
function custom_comment_template($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    $is_own_comment = (is_user_logged_in() && get_current_user_id() == $comment->user_id);
    ?>
    <div class="praktika-comment <?php echo ($depth > 0) ? 'sub-answer' : ''; ?>" id="comment-<?php comment_ID(); ?>">
        <div class="praktika-comment-item <?php echo $is_own_comment ? 'praktika-comment-item_own' : ''; ?>">
            <div class="praktika-comment-item__main">
                <div class="praktika-comment-img">
                    <?php echo get_avatar($comment, 60, '', '', array('class' => 'avatar')); ?>
                </div>
                <b class="praktika-comment-name">
                    <?php comment_author(); ?>
                </b>
                <span class="praktika-comment-time">
                    <?php printf(_x('%s назад', '%s = human-readable time difference', 'textdomain'), human_time_diff(get_comment_time('U'), current_time('timestamp'))); ?>
                </span>
                <div class="praktika-comment-item__main-action">
                    <?php if ($is_own_comment): ?>
                       <!-- <div class="your-comm">
                            <div class="your-comm__btn your-comm__btn_edit" onclick="toggleEditForm(<?php echo $comment->comment_ID; ?>)">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/edit-icon.png" alt="Редактировать">
                            </div>
                            <div class="your-comm__btn your-comm__btn_del" onclick="deleteComment(<?php echo $comment->comment_ID; ?>)">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/del-icon.png" alt="Удалить">
                            </div>
                        </div>-->
                    <?php else: ?>
                        <div class="answer-btn" onclick="toggleReplyForm(<?php echo $comment->comment_ID; ?>)">
                            <span>Ответить</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="praktika-comment-item__text">
                <?php 
                if ($comment->comment_parent != 0) {
                    $parent_comment = get_comment($comment->comment_parent);
                    if ($parent_comment) {
                        echo '<b>@' . $parent_comment->comment_author . '</b> ';
                    }
                }
                comment_text(); 
                ?>
            </div>
            
            <!-- Форма редактирования (только для своих комментариев) -->
            <?php if ($is_own_comment): ?>
            <form class="praktika-comment-item__edit hidden" id="edit-form-<?php echo $comment->comment_ID; ?>">
                <div class="answer-main">
                    <textarea name="comment_content" class="input textarea-resize" rows="1"><?php echo esc_textarea($comment->comment_content); ?></textarea>
                    <button type="button" class="btn" onclick="updateComment(<?php echo $comment->comment_ID; ?>)">
                        Обновить
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <!-- Форма ответа -->
        <div class="praktika-comment__answer hidden" id="reply-form-<?php echo $comment->comment_ID; ?>">
            <div class="answer-main">
                <div class="answer-main__image">
                    <?php echo get_avatar(get_current_user_id(), 40); ?>
                </div>
                <textarea name="reply_content" class="input textarea-resize" placeholder="Ваш ответ" rows="1"></textarea>
                <button type="button" class="btn" >
                    Отправить
                </button>
            </div>
        </div>
    </div>
    <?php
}

// Обработка AJAX комментариев
add_action('wp_ajax_submit_custom_comment', 'handle_custom_comment');
add_action('wp_ajax_nopriv_submit_custom_comment', 'handle_custom_comment');

function handle_custom_comment() {
    // Проверка nonce - используем ваш 'yoga_ajax_nonce'
    if (!wp_verify_nonce($_POST['comment_security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    // Определяем автора используя ваши данные
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = $current_user->display_name ?: $current_user->user_login;
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Гость';
        $comment_author_email = '';
        $user_id = 0;
    }
    
    // Данные комментария
    $comment_data = array(
        'comment_post_ID' => intval($_POST['post_id']),
        'comment_content' => sanitize_textarea_field($_POST['comment']),
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'comment_author_url' => '',
        'user_id' => $user_id,
        'comment_approved' => 1,
    );
    
    // Вставляем комментарий
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        wp_send_json_success('Комментарий добавлен');
    } else {
        wp_send_json_error('Ошибка при добавлении комментария');
    }
}

// Обработка ответов на комментарии
add_action('wp_ajax_submit_comment_reply', 'handle_comment_reply');
add_action('wp_ajax_nopriv_submit_comment_reply', 'handle_comment_reply');

function handle_comment_reply() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    // Определяем автора
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = $current_user->display_name ?: $current_user->user_login;
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Гость';
        $comment_author_email = '';
        $user_id = 0;
    }
    
    $comment_data = array(
        'comment_post_ID' => intval($_POST['post_id']),
        'comment_content' => sanitize_textarea_field($_POST['content']),
        'comment_parent' => intval($_POST['parent_id']),
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'user_id' => $user_id,
        'comment_approved' => 1,
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        wp_send_json_success('Ответ добавлен');
    } else {
        wp_send_json_error('Ошибка при добавлении ответа');
    }
}

// Обновление комментариев (только для зарегистрированных пользователей)
add_action('wp_ajax_update_comment', 'handle_comment_update');

function handle_comment_update() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);
    
    // Проверяем, что пользователь может редактировать комментарий
    if (!current_user_can('edit_comment', $comment_id) || $comment->user_id != get_current_user_id()) {
        wp_send_json_error('Недостаточно прав для редактирования комментария');
    }
    
    $comment_data = array(
        'comment_ID' => $comment_id,
        'comment_content' => sanitize_textarea_field($_POST['content']),
    );
    
    $result = wp_update_comment($comment_data);
    
    if ($result) {
        wp_send_json_success('Комментарий обновлен');
    } else {
        wp_send_json_error('Ошибка при обновлении комментария');
    }
}

// Удаление комментариев (только для зарегистрированных пользователей)
add_action('wp_ajax_delete_comment', 'handle_comment_delete');

function handle_comment_delete() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('Ошибка безопасности');
    }
    
    $comment_id = intval($_POST['comment_id']);
    
    // Проверяем, что пользователь может удалять комментарий
    if (!current_user_can('edit_comment', $comment_id) || get_comment($comment_id)->user_id != get_current_user_id()) {
        wp_send_json_error('Недостаточно прав для удаления комментария');
    }
    
    $result = wp_delete_comment($comment_id, true);
    
    if ($result) {
        wp_send_json_success('Комментарий удален');
    } else {
        wp_send_json_error('Ошибка при удалении комментария');
    }
}


	
	// Разрешить комментарии для custom post type 'practice'
	function enable_comments_for_practice($open, $post_id) {
		$post = get_post($post_id);
		if ($post->post_type == 'practice') {
			return true;
		}
		return $open;
	}
	add_filter('comments_open', 'enable_comments_for_practice', 10, 2);
	
	// Включить поддержку комментариев для custom post type
	function add_comments_support_for_practice() {
		add_post_type_support('practice', 'comments');
	}
	add_action('init', 'add_comments_support_for_practice');
	
	// Кастомизация аватаров
	add_filter('avatar_defaults', 'custom_avatar_defaults');
	function custom_avatar_defaults($avatar_defaults) {
		$avatar_defaults[get_template_directory_uri() . '/assets/img/default-avatar.png'] = 'Default Avatar';
		return $avatar_defaults;
	}
	
	// Время комментариев на русском
	function russian_comment_time($date, $d, $comment) {
		if (!is_admin()) {
			return human_time_diff(get_comment_time('U'), current_time('timestamp')) . ' назад';
		}
		return $date;
	}
	add_filter('get_comment_date', 'russian_comment_time', 10, 3);
	
	// Обработка AJAX формы контактов
	add_action('wp_ajax_process_contact_form', 'process_contact_form');
	add_action('wp_ajax_nopriv_process_contact_form', 'process_contact_form');
	
	function process_contact_form() {
		// Проверка nonce
		if (!wp_verify_nonce($_POST['contacts_nonce_field'], 'contacts_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
		}
		
		// Валидация и санитизация данных
		$name = sanitize_text_field($_POST['contacts_name']);
		$email = sanitize_email($_POST['contacts_email']);
		$phone = sanitize_text_field($_POST['contacts_phone']);
		$message = sanitize_textarea_field($_POST['contacts_message']);
		
		if (empty($name) || empty($email) || empty($phone) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
		}
		
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
		}
		
		// Отправка email администратору
		//$to = get_option('admin_email');
		$to = 'sshell72@yandex.ru';
		$subject = 'Новое сообщение с формы контактов';
		$body = "
        Имя: $name
        Email: $email
        Телефон: $phone
        Сообщение: $message
		";
		
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$sent = wp_mail($to, $subject, nl2br($body), $headers);
		
		if ($sent) {
			// Сохранение в базу данных (опционально)
			save_contact_message($name, $email, $phone, $message);
			
			wp_send_json_success(array('message' => 'Сообщение отправлено успешно!'));
			} else {
			wp_send_json_error(array('message' => 'Ошибка при отправке сообщения'));
		}
	}
	
	// Сохранение сообщения в базу данных
	function save_contact_message($name, $email, $phone, $message) {
		$post_data = array(
        'post_title' => 'Сообщение от ' . $name,
        'post_content' => $message,
        'post_type' => 'contact_message',
        'post_status' => 'private',
        'meta_input' => array(
		'contact_email' => $email,
		'contact_phone' => $phone,
		'contact_date' => current_time('mysql')
        )
		);
		
		wp_insert_post($post_data);
	}
	
	// Обработка AJAX подписки
	add_action('wp_ajax_process_subscription', 'process_subscription');
	add_action('wp_ajax_nopriv_process_subscription', 'process_subscription');
	
	function process_subscription() {
		if (!wp_verify_nonce($_POST['nonce'], 'subscription_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
		}
		
		$email = sanitize_email($_POST['email']);
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
		}
		
		$saved = save_subscription_email($email);
		
		if ($saved) {
			wp_mail(
            get_option('admin_email'),
            'Новая подписка на сайте',
            'Новый email для подписки: ' . $email
			);
			
			wp_send_json_success(array('message' => 'Подписка оформлена успешно!'));
			} else {
			wp_send_json_error(array('message' => 'Ошибка при сохранении подписки'));
		}
	}
	
	function save_subscription_email($email) {
		$existing_emails = get_option('subscription_emails', array());
		
		if (!in_array($email, $existing_emails)) {
			$existing_emails[] = $email;
			return update_option('subscription_emails', $existing_emails);
		}
		
		return true;
	}
	
	class Custom_Menu_Walker extends Walker_Nav_Menu {
		private $item_counter = 0; // Счетчик пунктов меню
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			// Проверяем, является ли это первый пункт меню
			$is_first_item = ($this->item_counter === 0);
			
			// Добавляем класс main-menu-active-item только первому пункту
			$active_class = $is_first_item ? 'main-menu-active-item' : '';
			
			$output .= '<li class="' . $active_class . '">';
			
			// Создаем ссылку
			$attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
			$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
			$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
			$attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
			
			/* AxeCode.tech (этап 1): нормализация $args в walker для совместимости с PHP 8.x. */
			$args_before = is_object($args) ? ($args->before ?? '') : (is_array($args) ? ($args['before'] ?? '') : '');
			$args_link_before = is_object($args) ? ($args->link_before ?? '') : (is_array($args) ? ($args['link_before'] ?? '') : '');
			$args_link_after = is_object($args) ? ($args->link_after ?? '') : (is_array($args) ? ($args['link_after'] ?? '') : '');
			$args_after = is_object($args) ? ($args->after ?? '') : (is_array($args) ? ($args['after'] ?? '') : '');
			$item_output = $args_before;
			$item_output .= '<a class="ref"' . $attributes . '>';
			$item_output .= $args_link_before . apply_filters('the_title', $item->title, $item->ID) . $args_link_after;
			
			// Добавляем иконки только для первого пункта
			if ($is_first_item) {
				$item_output .= '<div class="ref-icon">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon.png" alt="" class="active">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon_violet.png" alt="">';
				$item_output .= '</div>';
			}
			
			$item_output .= '</a>';
			$item_output .= $args_after;
			
			$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
			
			// Увеличиваем счетчик после обработки элемента
			$this->item_counter++;
		}
		
		// Сбрасываем счетчик при начале нового уровня меню
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::start_lvl($output, $depth, $args);
		}
		
		// Сбрасываем счетчик при завершении уровня меню
		function end_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::end_lvl($output, $depth, $args);
		}
	}
	
	class Mobile_Menu_Walker extends Walker_Nav_Menu {
		private $item_count = 0;
		
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_count = 0; // Сбрасываем счетчик для новых уровней
		}
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			$this->item_count++;
			
			// Добавляем классы
			$class_names = 'mobile-menu-main-item';
			
			// Добавляем special класс для первого пункта
			if ($this->item_count === 1) {
				$class_names .= ' mobile-menu-main-item_sw';
			}
			
			$output .= '<li class="' . $class_names . '">';
			
			// Для первого пункта не добавляем ссылку, для остальных - добавляем
			if ($this->item_count !== 1) {
				$attributes = !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
				$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
				$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
				
				$output .= '<a' . $attributes . '></a>';
			}
			
			// Добавляем span с текстом
			$output .= '<span>' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
		}
		
		function end_el(&$output, $item, $depth = 0, $args = array()) {
			$output .= '</li>';
		}
	}
	
	
	// Кастомный walker
	class Footer_Walker extends Walker_Nav_Menu {
		function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			$classes     = empty( $item->classes ) ? array() : (array) $item->classes;
			$is_active   = in_array( 'current-menu-item', $classes ) || in_array( 'current_page_item', $classes );
			$active_class = $is_active ? ' active' : '';
			
			$output .= '<li>';
			$output .= '<a class="footer-menu-item' . $active_class . '" href="' . esc_url( $item->url ) . '">';
			$output .= esc_html( $item->title );
			$output .= '</a>';
			$output .= '</li>';
		}
	}
	
	// Обработка AJAX формы FAQ
	add_action('wp_ajax_faq_contact_form', 'handle_faq_contact_form');
	add_action('wp_ajax_nopriv_faq_contact_form', 'handle_faq_contact_form');
	
	function handle_faq_contact_form() {
		// Проверка nonce
		if (!wp_verify_nonce($_POST['faq_nonce'], 'faq_contact_nonce')) {
			wp_send_json_error(array('message' => 'Ошибка безопасности'));
			exit;
		}
		
		// Валидация данных
		$name = sanitize_text_field($_POST['name']);
		$email = sanitize_email($_POST['email']);
		$message = sanitize_textarea_field($_POST['message']);
		
		// Проверка обязательных полей
		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Пожалуйста, заполните все поля'));
			exit;
		}
		
		// Проверка email
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'Пожалуйста, введите корректный email'));
			exit;
		}
		
		// Отправка email администратору
		$to = get_option('admin_email');
		$subject = 'Новый вопрос из раздела FAQ: ' . $name;
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$body = "
        <h3>Новый вопрос из раздела FAQ</h3>
        <p><strong>Имя:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Вопрос:</strong></p>
        <p>" . nl2br($message) . "</p>
        <hr>
        <p><small>Сообщение отправлено с сайта " . get_bloginfo('name') . "</small></p>
		";
		
		// Отправка email
		$email_sent = wp_mail($to, $subject, $body, $headers);
		
		if ($email_sent) {
			// Сохранение в базу данных
			$post_id = wp_insert_post(array(
            'post_title' => 'Вопрос от ' . $name,
            'post_content' => $message,
            'post_type' => 'faq_question',
            'post_status' => 'private',
            'meta_input' => array(
			'contact_email' => $email,
			'contact_date' => current_time('mysql')
            )
			));
			
			wp_send_json_success(array(
            'message' => get_field('faq_form_success_message', 'option') ?: 'Ваш вопрос отправлен! Мы ответим вам в ближайшее время.'
			));
			} else {
			wp_send_json_error(array('message' => 'Ошибка при отправке сообщения'));
		}
		
		exit;
	}
	
	// Создание Custom Post Type для вопросов
	function register_faq_question_cpt() {
		register_post_type('faq_question', array(
        'labels' => array(
		'name' => '??????? FAQ',
		'singular_name' => '??????',
		'menu_name' => '??????? FAQ',
		'add_new' => '???????? ??????',
		'add_new_item' => '???????? ????? ??????',
		'edit_item' => '????????????? ??????',
		'new_item' => '????? ??????',
		'view_item' => '??????????? ??????',
		'search_items' => '????? ????????',
		'not_found' => '??????? ?? ???????',
		'not_found_in_trash' => '??????? ? ??????? ?? ???????'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-format-chat'
		));
	}
	add_action('init', 'register_faq_question_cpt');
	
	// Добавление метаполей для вопросов
	function add_faq_question_meta() {
		add_meta_box(
        'faq_question_meta',
        'Информация о вопросе',
        'display_faq_question_meta',
        'faq_question',
        'normal',
        'high'
		);
	}
	add_action('add_meta_boxes', 'add_faq_question_meta');
	
	function display_faq_question_meta($post) {
		$email = get_post_meta($post->ID, 'contact_email', true);
		$date = get_post_meta($post->ID, 'contact_date', true);
	?>
    <p><strong>Email:</strong> <?php echo esc_html($email); ?></p>
    <p><strong>Дата:</strong> <?php echo esc_html($date); ?></p>
    <?php
	}
	
	// Сохранение метаполей
	function save_faq_question_meta($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!current_user_can('edit_post', $post_id)) return;
		
		if (isset($_POST['contact_email'])) {
			update_post_meta($post_id, 'contact_email', sanitize_email($_POST['contact_email']));
		}
	}
	add_action('save_post_faq_question', 'save_faq_question_meta');
	
	// === Сложность ===
	/* register_taxonomy('practice-difficulty', ['practice'], [
		'label' => 'Сложность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Продолжительность ===
		register_taxonomy('practice-duration', ['practice'], [
		'label' => 'Продолжительность',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // Показывать колонку в списке записей
		]);
		
		// === Цель ===
		register_taxonomy('practice-goal', ['practice'], [
		'label' => 'Цели',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Важно для отображения в новом редакторе
		'rewrite' => ['slug' => 'goal'],
		'show_admin_column' => true, // Показывать колонку в списке записей
	]); */
	
	add_action('wp_ajax_filter_practices', 'filter_practices_callback');
	add_action('wp_ajax_nopriv_filter_practices', 'filter_practices_callback');
	
	function filter_practices_callback() {
		$filters = $_POST['filters'] ?? [];
		$search  = sanitize_text_field($_POST['search'] ?? '');
		$term_id = intval($_POST['term_id'] ?? 0);
		
		$tax_query = [];
		if ($term_id) {
			$tax_query[] = [
            'taxonomy' => 'practice-type',
            'field'    => 'term_id',
            'terms'    => $term_id,
			];
		}
		
		foreach ($filters as $taxonomy => $terms) {
			if (!empty($terms)) {
				$tax_query[] = [
                'taxonomy' => sanitize_text_field($taxonomy),
                'field'    => 'slug',
                'terms'    => array_map('sanitize_text_field', $terms),
				];
			}
		}
		
		$args = [
        'post_type'      => 'practice',
        'posts_per_page' => -1,
		];
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}
		if (!empty($search)) {
			$args['s'] = $search;
		}
		
		$query = new WP_Query($args);
		
		if ($query->have_posts()) {
			while ($query->have_posts()) {
			$query->the_post(); ?>
            <div class="library-item">
                <div class="library-item__bg"></div>
                <div class="library-item__cat">
                    <?php
						$cats = wp_get_post_terms(get_the_ID(), 'practice-type');
						if ($cats) echo esc_html($cats[0]->name);
					?>
                    <a href="<?php the_permalink(); ?>" target="_blank"></a>
				</div>
                <p class="library-item__text"><?php echo get_the_excerpt(); ?></p>
                <div class="library-item__img">
                    <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
				</div>
                <div class="library-item__btn">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/library-item-btn.png" alt="">
				</div>
                <a href="<?php the_permalink(); ?>" class="library-item__link"></a>
			</div>
			<?php }
			wp_reset_postdata();
			} else {
			echo '<p>Нет практик по выбранным фильтрам.</p>';
		}
		
		wp_die();
	}
	
	// AJAX обработчик для фильтрации практик
	add_action('wp_ajax_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	add_action('wp_ajax_nopriv_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	
	function filter_practices_callback_kriyi() {
		// Проверяем nonce для безопасности
		check_ajax_referer('practice_filter_nonce', 'nonce');
		
		// Параметры по умолчанию
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => -1,
        'post_status' => 'publish'
		);
		
		// Фильтр по категории (term_id)
		if (!empty($_POST['term_id'])) {
			$args['tax_query'] = array(
            array(
			'taxonomy' => 'practice-type',
			'field' => 'term_id',
			'terms' => intval($_POST['term_id'])
            )
			);
		}
		
		// Поиск по названию и описанию
		if (!empty($_POST['search'])) {
			$args['s'] = sanitize_text_field($_POST['search']);
		}
		
		// Фильтры по таксономиям
		if (!empty($_POST['filters'])) {
			$filters = $_POST['filters'];
			
			if (!isset($args['tax_query'])) {
				$args['tax_query'] = array('relation' => 'AND');
				} else {
				$args['tax_query']['relation'] = 'AND';
			}
			
			foreach ($filters as $taxonomy => $terms) {
				if (!empty($terms)) {
					$args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => array_map('intval', $terms),
                    'operator' => 'IN'
					);
				}
			}
		}
		
		// Сортировка
		if (!empty($_POST['sort'])) {
			switch ($_POST['sort']) {
				case 'newness':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
				case 'popularity':
				default:
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'views_count';
                $args['order'] = 'DESC';
                break;
			}
		}
		
		$query = new WP_Query($args);
		$count = $query->found_posts;
		
		// Формируем HTML ответ
		ob_start();
		
		if ($query->have_posts()) :
        $item_count = 0;
        while ($query->have_posts()) : $query->the_post();
		$item_count++;
		$practice_level = get_field('level') ?: 'Начинающий';
		$practice_description = get_field('short_description') ?: get_the_excerpt();
		$practice_image = get_field('image') ?: get_template_directory_uri() . '/assets/img/kriya-img_01.png';
		$is_favorite = get_field('is_favorite') ?: false;
		$hidden_class = ($item_count > 10) ? 'hidden' : '';
	?>
	
	<div class="kriyi-item <?php echo $hidden_class; ?>">
		<div class="kriyi-item__inner">
			<a href="<?php the_permalink(); ?>"></a>
			<span class="kriya-level"><?php echo esc_html($practice_level); ?></span>
			<div class="kriya-info">
				<h3><?php the_title(); ?></h3>
				<p><?php echo esc_html($practice_description); ?></p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img">
					<img src="<?php echo esc_url($practice_image); ?>" alt="<?php the_title(); ?>">
				</div>
				<div class="kriya-fav">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav.png" alt="" class="<?php echo !$is_favorite ? 'active' : ''; ?>">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav_active.png" alt="" class="<?php echo $is_favorite ? 'active' : ''; ?>">
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>   
				</div>
			</div>      
		</div>
	</div>
	
	<?php
        endwhile;
        
        // Дополнительные элементы если результатов больше 10
        if ($count > 10) :
		for ($i = 0; $i < 2; $i++) :
	?>
	<div class="kriyi-item kriyi-item_last hidden">
		<div class="kriyi-item__inner">
			<a href="#"></a>
			<span class="kriya-level">Начинающий</span>
			<div class="kriya-info">
				<h3>Остальные крийи</h3>
				<p>Показать все доступные практики</p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="Остальные крийи">
				</div>
				<div class="kriya-fav">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav.png" alt="" class="active">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav_active.png" alt="">
				</div>
				<div class="kriya-btn">
					<div class="kriya-btn__arrow">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
						<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
					</div>   
				</div>
			</div> 
		</div>
	</div>
	<?php
		endfor;
        endif;
        
		else :
        echo '<p class="no-practices">По вашему запросу ничего не найдено.</p>';
		endif;
		
		$html = ob_get_clean();
		
		// Возвращаем HTML и количество результатов
		wp_send_json_success(array(
        'html' => $html,
        'count' => $count
		));
		
		wp_die();
	}
	
	// Очистка корзины и добавление товара с редиректом
	add_action('template_redirect', 'handle_tariff_add_to_cart');
	function handle_tariff_add_to_cart() {
	if (!function_exists('WC') || !function_exists('wc_get_checkout_url')) {
		return;
	}

		// Проверяем, добавляется ли товар категории tariffs
		if (isset($_POST['add-to-cart']) && is_numeric($_POST['add-to-cart']) && isset($_POST['woocommerce-add-to-cart-nonce'])) {
			
			// Проверяем nonce
			if (!wp_verify_nonce($_POST['woocommerce-add-to-cart-nonce'], 'woocommerce-add-to-cart')) {
				return;
			}
			
			$product_id = intval($_POST['add-to-cart']);
			
			if (has_term('tariffs', 'product_cat', $product_id)) {
				// Очищаем корзину
				WC()->cart->empty_cart();
				
				// Добавляем товар
				$added = WC()->cart->add_to_cart($product_id);
				
				if ($added) {
					// Редирект на checkout
					wp_redirect(wc_get_checkout_url());
					exit;
				}
			}
		}
	}
	
	// Отключаем стандартную обработку WooCommerce для тарифов
	add_filter('woocommerce_add_to_cart_redirect', 'disable_standard_redirect_for_tariffs', 10, 1);
	function disable_standard_redirect_for_tariffs($url) {
		if (isset($_POST['add-to-cart']) && is_numeric($_POST['add-to-cart'])) {
			$product_id = intval($_POST['add-to-cart']);
			if (has_term('tariffs', 'product_cat', $product_id)) {
				return ''; // Отключаем стандартный редирект
			}
		}
		return $url;
	}
	
	
	// Подключаем скрипты и стили WooCommerce
	function theme_woocommerce_support() {
		add_theme_support('woocommerce');
		
		// Подключаем скрипты для checkout
	}
	add_action('after_setup_theme', 'theme_woocommerce_support');
	
	// Убедимся, что все необходимые скрипты загружены
	function theme_enqueue_checkout_scripts() {
		if (function_exists('is_checkout') && is_checkout()) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('wc-checkout');
			wp_enqueue_script('wc-country-select');
			wp_enqueue_script('wc-address-i18n');
		}
	}
	add_action('wp_enqueue_scripts', 'theme_enqueue_checkout_scripts');
	
	// Проверяем и исправляем возможные проблемы с checkout
	add_action('template_redirect', 'fix_checkout_issues');
	function fix_checkout_issues() {
	if (!function_exists('is_checkout') || !function_exists('WC')) {
		return;
	}

		if (is_checkout() && WC()->cart && !WC()->cart->is_empty()) {
			// Убедимся, что сессия WooCommerce активна
			if (WC()->session && !WC()->session->has_session()) {
				WC()->session->set_customer_session_cookie(true);
			}
		}
	}
	
	// Отладочная информация
	add_action('wp_footer', 'debug_checkout_info');
	function debug_checkout_info() {
	if (!function_exists('is_checkout') || !function_exists('WC')) {
		return;
	}
		if (is_checkout() && WC()->cart) {
			echo '<!-- Debug: Checkout page -->';
			echo '<!-- Debug: Cart items: ' . count(WC()->cart->get_cart()) . ' -->';
			echo '<!-- Debug: Nonce field: ' . (wp_verify_nonce('test', 'woocommerce-process_checkout') ? 'OK' : 'Missing') . ' -->';
		}
	}
	
	// Исправление nonce проверки для checkout
	add_filter('woocommerce_verify_nonce', 'fix_checkout_nonce_verification', 10, 2);
	function fix_checkout_nonce_verification($result, $action) {
		/* AxeCode.tech (безопасность, этап 1): сохраняем штатную проверку nonce WooCommerce без обхода. */
		if ($action === 'woocommerce-process_checkout') {
			return $result;
		}
		return $result;
	}
	
	// Альтернативное решение: создаем свою обработку checkout
	add_action('template_redirect', 'handle_custom_checkout');
	function handle_custom_checkout() {
	if (!function_exists('WC') || !function_exists('wc_add_notice')) {
		return;
	}
		if (is_page('checkout') && !empty($_POST['woocommerce-process-checkout-nonce'])) {
			
			// Проверяем nonce
			if (wp_verify_nonce($_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout')) {
				
				// Обрабатываем заказ
				WC()->checkout()->process_checkout();
				
				} else {
				wc_add_notice('Ошибка безопасности. Пожалуйста, попробуйте еще раз.', 'error');
			}
		}
	}
	
	
	// Добавляем возможности для пользователей
	function add_custom_capabilities() {
		$subscriber = get_role('subscriber');
		$subscriber->add_cap('read_private_practices');
		$subscriber->add_cap('edit_user_profile');
	}
	add_action('init', 'add_custom_capabilities');
	
	// Обработка обновления профиля
	/* function update_user_profile() {
		if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
        wp_die('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
        wp_die('Вы не авторизованы');
		}
		
		$user_id = get_current_user_id();
		$user_data = array('ID' => $user_id);
		
		// Обновление основных данных
		if (!empty($_POST['first_name'])) {
        $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
		}
		
		if (!empty($_POST['last_name'])) {
        $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
		}
		
		if (!empty($_POST['email'])) {
        $user_data['user_email'] = sanitize_email($_POST['email']);
		}
		
		wp_update_user($user_data);
		
		// Обновление метаполей
		if (!empty($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
		}
		
		if (!empty($_POST['birthdate'])) {
        update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
		}
		
		if (!empty($_POST['gender'])) {
        update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
		}
		
		// Обработка смены пароля
		if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
        if ($_POST['new_password'] === $_POST['repeat_password']) {
		$user = get_user_by('id', $user_id);
		
		if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
		wp_set_password($_POST['new_password'], $user_id);
		}
        }
		}
		
		// Обработка загрузки аватара
		if (!empty($_FILES['avatar'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('avatar', 0);
        
        if (!is_wp_error($attachment_id)) {
		update_user_meta($user_id, 'simple_local_avatar', $attachment_id);
        }
		}
		
		wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
		exit;
		}
		add_action('admin_post_update_user_profile', 'update_user_profile');
	add_action('admin_post_nopriv_update_user_profile', 'update_user_profile'); */
	// AJAX обработчик для обновления профиля
	function yoga_update_profile_ajax() {
		// Логируем запрос для отладки
		error_log('AJAX update_profile called');
		error_log('POST data: ' . print_r($_POST, true));
		error_log('FILES data: ' . print_r($_FILES, true));
		
		// Проверяем nonce
		if (!isset($_POST['nonce'])) {
			error_log('Nonce not set');
			wp_send_json_error('Nonce не установлен', 400);
		}
		
		if (!wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			error_log('Nonce verification failed');
			wp_send_json_error('Ошибка безопасности: неверный nonce', 403);
		}
		
		if (!is_user_logged_in()) {
			error_log('User not logged in');
			wp_send_json_error('Вы не авторизованы', 401);
		}
		
		$user_id = get_current_user_id();
		$response = array();
		
		try {
			$user_data = array('ID' => $user_id);
			
			// Обновление основных данных
			if (!empty($_POST['first_name'])) {
				$user_data['first_name'] = sanitize_text_field($_POST['first_name']);
			}
			
			if (!empty($_POST['last_name'])) {
				$user_data['last_name'] = sanitize_text_field($_POST['last_name']);
			}
			
			if (!empty($_POST['email'])) {
				$user_data['user_email'] = sanitize_email($_POST['email']);
			}
			
			wp_update_user($user_data);
			
			// Обновление метаполей
			if (!empty($_POST['phone'])) {
				update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
			}
			
			if (!empty($_POST['birthdate'])) {
				update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
			}
			
			if (!empty($_POST['gender'])) {
				update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
			}
			
			// Обработка смены пароля
			if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
				if ($_POST['new_password'] === $_POST['repeat_password']) {
					$user = get_user_by('id', $user_id);
					
					if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
						wp_set_password($_POST['new_password'], $user_id);
					}
				}
			}
			
			// Обработка загрузки аватара
			// Обработка загрузки аватара
			if (!empty($_FILES['avatar'])) {
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				
				/* $current_user = wp_get_current_user();
				$user_id = $current_user->ID; */
				
				$attachment_id = media_handle_upload('avatar', 0);
				
				$attachment = get_post($attachment_id);
				if ($attachment && $attachment->post_type === 'attachment') {
					$mime_type = get_post_mime_type($attachment_id);
					if (strpos($mime_type, 'image/') === 0) {
						// Обновляем поле ACF для текущего пользователя
						$result = update_field('user_avatar', $attachment_id, 'user_' . $user_id);
						
						if ($result) {
							wp_send_json_success('Аватар успешно обновлен через ACF');
							} else {
							wp_send_json_error('Ошибка при обновлении поля ACF');
						}
						} else {
						wp_send_json_error("Файл не является изображением: $mime_type");
					}
				}
			}
			
			wp_send_json_success($result);
			
			} catch (Exception $e) {
			error_log('Exception in update_profile: ' . $e->getMessage());
			wp_send_json_error('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
		}
	}
	add_action('wp_ajax_update_user_profile', 'yoga_update_profile_ajax');
	// Обработчик удаления аватара
	function delete_avatar_ajax() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			wp_send_json_error('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('Не авторизован');
		}
		
		$user_id = get_current_user_id();
		delete_user_meta($user_id, 'simple_local_avatar');
		
		wp_send_json_success('Аватар удален');
	}
	add_action('wp_ajax_delete_avatar', 'delete_avatar_ajax');
	
	// Шорткод для истории практик
	function practice_history_shortcode() {
		if (!is_user_logged_in()) return '';
		
		$user_id = get_current_user_id();
		$completed_practices = get_user_meta($user_id, 'completed_practices', true);
		
		if (empty($completed_practices)) {
			return '<p>Вы еще не завершили ни одной практики.</p>';
		}
		
		ob_start();
	?>
    <div class="lk-kriyi">
        <div class="kriyi">
            <div class="kriyi__items">
                <?php 
					foreach ($completed_practices as $practice_id) {
						$practice = get_post($practice_id);
						if ($practice) {
							$level = get_the_terms($practice_id, 'practice-type');
							$level_name = !empty($level) ? $level[0]->name : 'Не указан';
						?>
                        <div class="kriyi-item">
                            <div class="kriyi-item__inner">
                                <a href="<?php echo get_permalink($practice_id); ?>"></a>
                                <span class="kriya-level"><?php echo $level_name; ?></span>
                                <div class="kriya-info">
                                    <h3><?php echo get_the_title($practice_id); ?></h3>
                                    <p><?php echo get_the_excerpt($practice_id); ?></p>
								</div>
                                <div class="kriya-media">
                                    <div class="kriya-img">
                                        <?php if (has_post_thumbnail($practice_id)): ?>
										<?php echo get_the_post_thumbnail($practice_id, 'medium'); ?>
                                        <?php else: ?>
										<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="">
                                        <?php endif; ?>
									</div>
                                    <div class="kriya-fav">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav.png" alt="" class="active">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-fav_active.png" alt="">
									</div>
                                    <div class="kriya-btn">
                                        <div class="kriya-btn__arrow">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow.png" alt="" class="active">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-btn-arrow_active.png" alt="">
										</div>   
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
		return ob_get_clean();
	}
	add_shortcode('practice_history', 'practice_history_shortcode');
	
	// Шорткод для истории заказов и подписок
	function subscription_settings_shortcode() {
		if (!is_user_logged_in()) return '';
		
		$user_id = get_current_user_id();
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => 'completed',
        'limit' => -1
		));
		
		ob_start();
	?>
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
									/* AxeCode.tech: безопасный вызов для окружений без WooCommerce Subscriptions. */
									$active_subscriptions = function_exists('wcs_get_users_subscriptions')
										? wcs_get_users_subscriptions($user_id)
										: array();
									if (!empty($active_subscriptions)) {
										foreach ($active_subscriptions as $subscription) {
											if ($subscription->has_status('active')) {
												echo get_the_title($subscription->get_id());
												break;
											}
										}
										} else {
										echo 'Не активен';
									}
								?>
							</span>
						</div>
					</div>
                    <div class="lk-settings-item__col">
                        <p class="lk-settings-item__col-text">Действует до:</p>
                        <time>
                            <?php
								if (!empty($active_subscriptions)) {
									foreach ($active_subscriptions as $subscription) {
										if ($subscription->has_status('active')) {
											echo $subscription->get_date('end');
											break;
										}
									}
									} else {
									echo '—';
								}
							?>
						</time>
					</div>
				</div>
			</div>
            
            <div class="lk-settings-part">
                <h4>История покупок</h4>
                <?php
					if (!empty($orders)) {
						foreach ($orders as $order) {
						?>
                        <div class="lk-settings-item">
                            <div class="lk-settings-item__col">
                                <time><?php echo $order->get_date_created()->format('d.m.Y'); ?></time>
							</div>
                            <div class="lk-settings-item__col">
                                <div class="lk-settings-item__col-text">
                                    <b><?php echo $order->get_status(); ?></b>
								</div>
                                <p class="lk-settings-item__col-text"><?php echo $order->get_formatted_order_total(); ?></p>
							</div>
						</div>
                        <?php
						}
						} else {
						echo '<p>У вас пока нет завершенных заказов.</p>';
					}
				?>
			</div>
		</div>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('subscription_settings', 'subscription_settings_shortcode');
	
	// Функция для получения рекомендованных практик
	function get_recommended_practices($user_id) {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		$favorite_practices = get_user_meta($user_id, 'favorite_practices', true) ?: array();
		
		// Если пользователь новый, показываем популярные практики
		if (empty($completed_practices) && empty($favorite_practices)) {
			return get_popular_practices();
		}
		
		// Получаем практики на основе предпочтений пользователя
		$recommended = array();
		
		// 1. По уровню сложности (на основе завершенных практик)
		$user_levels = get_user_practice_levels($user_id);
		if (!empty($user_levels)) {
			$level_practices = get_practices_by_levels($user_levels, 6);
			$recommended = array_merge($recommended, $level_practices);
		}
		
		// 2. Похожие на избранные
		if (!empty($favorite_practices)) {
			$similar_practices = get_similar_practices($favorite_practices, 4);
			$recommended = array_merge($recommended, $similar_practices);
		}
		
		// 3. Новые практики, которые пользователь еще не проходил
		$new_practices = get_new_practices($user_id, 3);
		$recommended = array_merge($recommended, $new_practices);
		
		// Убираем дубликаты и уже завершенные практики
		$recommended = array_unique($recommended);
		$recommended = array_diff($recommended, $completed_practices);
		
		// Ограничиваем количество рекомендаций
		return array_slice($recommended, 0, 12);
	}
	
	// Вспомогательные функции
	function get_user_practice_levels($user_id) {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		$levels = array();
		
		foreach ($completed_practices as $practice_id) {
			$practice_levels = wp_get_post_terms($practice_id, 'practice-type', array('fields' => 'ids'));
			$levels = array_merge($levels, $practice_levels);
		}
		
		return array_count_values($levels);
	}
	
	function get_practices_by_levels($level_counts, $limit = 6) {
		arsort($level_counts);
		$most_common_levels = array_slice(array_keys($level_counts), 0, 2);
		
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'tax_query' => array(
		array(
		'taxonomy' => 'practice-type',
		'field' => 'term_id',
		'terms' => $most_common_levels,
		)
        ),
        'fields' => 'ids'
		);
		
		$practices = get_posts($args);
		return $practices;
	}
	
	function get_similar_practices($favorite_practice_ids, $limit = 4) {
		if (empty($favorite_practice_ids)) return array();
		
		$similar = array();
		
		foreach ($favorite_practice_ids as $practice_id) {
			$practice_levels = wp_get_post_terms($practice_id, 'practice-type', array('fields' => 'ids'));
			
			if (!empty($practice_levels)) {
				$args = array(
                'post_type' => 'practice',
                'posts_per_page' => 2,
                'post__not_in' => $favorite_practice_ids,
                'tax_query' => array(
				array(
				'taxonomy' => 'practice-type',
				'field' => 'term_id',
				'terms' => $practice_levels,
				)
                ),
                'fields' => 'ids'
				);
				
				$similar_practices = get_posts($args);
				$similar = array_merge($similar, $similar_practices);
			}
		}
		
		return array_slice($similar, 0, $limit);
	}
	
	function get_new_practices($user_id, $limit = 3) {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'post__not_in' => $completed_practices,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids'
		);
		
		return get_posts($args);
	}
	
	function get_popular_practices($limit = 8) {
		// Можно реализовать систему подсчета популярности на основе просмотров
		// Пока просто возвращаем последние практики
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids'
		);
		
		return get_posts($args);
	}
	
	
	// Функция для получения вопросов пользователя
	function get_user_questions($user_id) {
		$args = array(
        'post_type' => 'question',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
		);
		
		return get_posts($args);
	}
	
	// Функция для отображения вопроса
	function display_question_item($question, $hidden = false) {
		$question_id = $question->ID;
		$answer = get_post_meta($question_id, '_answer', true);
		$answer_date = get_post_meta($question_id, '_answer_date', true);
		$admin_id = get_post_meta($question_id, '_answer_admin', true);
		$admin_name = $admin_id ? get_the_author_meta('display_name', $admin_id) : 'Администратор';
		
		$status_class = $answer ? '' : 'lk-questions-item_new';
		$hidden_class = $hidden ? 'hidden' : '';
	?>
    <div class="lk-questions-item <?php echo $status_class . ' ' . $hidden_class; ?>">
        <div class="lk-question">
            <div class="lk-question__time">
                <time><?php echo get_the_date('d.m.Y', $question_id); ?></time>
                <time><?php echo get_the_time('H:i', $question_id); ?></time>
			</div>
            <div class="lk-question__text">
                <p><?php echo esc_html($question->post_content); ?></p>
			</div>
            <?php if (!$answer): ?>
            <span class="lk-question__status">Ожидает ответа</span>
            <?php endif; ?>
		</div>
        
        <?php if ($answer): ?>
        <div class="lk-question lk-question_answer">
            <div class="lk-question__time">
                <b>Ответ <?php echo esc_html($admin_name); ?></b>
                <time><?php echo date('d.m.Y', strtotime($answer_date)); ?></time>
                <time><?php echo date('H:i', strtotime($answer_date)); ?></time>
			</div>
            <div class="lk-question__text">
                <p><?php echo wp_kses_post($answer); ?></p>
			</div>
		</div>
        <?php endif; ?>
	</div>
    <?php
	}
	
	// Обработчик отправки вопроса
	function handle_question_submission() {
		if (!isset($_POST['question_nonce']) || !wp_verify_nonce($_POST['question_nonce'], 'submit_question')) {
			wp_die('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_die('Вы не авторизованы');
		}
		
		$question_text = sanitize_textarea_field($_POST['question_text']);
		
		if (empty($question_text)) {
			wp_die('Вопрос не может быть пустым');
		}
		
		$user_id = get_current_user_id();
		
		// Создаем пост вопроса
		$question_data = array(
        'post_title' => 'Вопрос от пользователя ' . $user_id,
        'post_content' => $question_text,
        'post_status' => 'publish',
        'post_type' => 'question',
        'post_author' => $user_id
		);
		
		$question_id = wp_insert_post($question_data);
		
		if (is_wp_error($question_id)) {
			wp_die('Ошибка при сохранении вопроса');
		}
		
		// Отправляем уведомление администратору
		$admin_email = get_option('admin_email');
		$user = get_userdata($user_id);
		$subject = 'Новый вопрос в личном кабинете';
		$message = "Пользователь {$user->display_name} задал новый вопрос:\n\n";
		$message .= $question_text . "\n\n";
		$message .= "Ссылка для ответа: " . admin_url("post.php?post={$question_id}&action=edit");
		
		wp_mail($admin_email, $subject, $message);
		
		wp_redirect(add_query_arg('question_submitted', 'true', wp_get_referer()));
		exit;
	}
	add_action('admin_post_submit_question', 'handle_question_submission');
	add_action('admin_post_nopriv_submit_question', 'handle_question_submission');
	
	// Регистрируем тип записи для вопросов
	function register_question_post_type() {
		$args = array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => array('title', 'editor'),
        'labels' => array(
		'name' => '??????? FAQ',
		'singular_name' => '??????',
		'menu_name' => '??????? FAQ',
		'add_new' => '???????? ??????',
		'add_new_item' => '???????? ????? ??????',
		'edit_item' => '????????????? ??????',
		'new_item' => '????? ??????',
		'view_item' => '??????????? ??????',
		'search_items' => '????? ????????',
		'not_found' => '??????? ?? ???????',
		'not_found_in_trash' => '??????? ? ??????? ?? ???????'
        )
		);
		
		register_post_type('question', $args);
	}
	add_action('init', 'register_question_post_type');
	
	// Добавляем метабокс для ответа на вопрос
	function add_question_answer_meta_box() {
		add_meta_box(
        'question_answer',
        'Ответ на вопрос',
        'render_question_answer_meta_box',
        'question',
        'normal',
        'high'
		);
	}
	add_action('add_meta_boxes', 'add_question_answer_meta_box');
	
	function render_question_answer_meta_box($post) {
		$answer = get_post_meta($post->ID, '_answer', true);
		$answer_date = get_post_meta($post->ID, '_answer_date', true);
		$admin_id = get_post_meta($post->ID, '_answer_admin', true);
		
		wp_nonce_field('save_question_answer', 'answer_nonce');
	?>
    <div style="margin-bottom: 15px;">
        <label for="question_answer" style="display: block; margin-bottom: 5px; font-weight: bold;">Ответ:</label>
        <?php
			wp_editor($answer, 'question_answer', array(
            'textarea_name' => 'question_answer',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true
			));
		?>
	</div>
    <?php if ($answer_date): ?>
    <div style="color: #666; font-size: 13px;">
        Ответ дан: <?php echo date('d.m.Y H:i', strtotime($answer_date)); ?> 
        пользователем: <?php echo $admin_id ? get_the_author_meta('display_name', $admin_id) : 'Неизвестно'; ?>
	</div>
    <?php endif; ?>
    <?php
	}
	
	// Сохранение ответа на вопрос
	function save_question_answer($post_id) {
		if (!isset($_POST['answer_nonce']) || !wp_verify_nonce($_POST['answer_nonce'], 'save_question_answer')) {
			return;
		}
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		
		if (isset($_POST['question_answer'])) {
			$answer = wp_kses_post($_POST['question_answer']);
			$old_answer = get_post_meta($post_id, '_answer', true);
			
			update_post_meta($post_id, '_answer', $answer);
			
			// Если ответ изменился или добавлен новый
			if ($answer !== $old_answer) {
				update_post_meta($post_id, '_answer_date', current_time('mysql'));
				update_post_meta($post_id, '_answer_admin', get_current_user_id());
				
				// Отправляем уведомление пользователю
				$question = get_post($post_id);
				$user = get_userdata($question->post_author);
				$subject = 'Ответ на ваш вопрос';
				$message = "Здравствуйте, {$user->display_name}!\n\n";
				$message .= "На ваш вопрос получен ответ:\n\n";
				$message .= "Вопрос: {$question->post_content}\n\n";
				$message .= "Ответ: {$answer}\n\n";
				$message .= "С уважением, администрация сайта";
				
				wp_mail($user->user_email, $subject, $message);
			}
		}
	}
	add_action('save_post_question', 'save_question_answer');
	
	// Функция для получения активной подписки пользователя
	function get_user_active_subscription() {
		if (!is_user_logged_in()) return false;
		
		$user_id = get_current_user_id();
		
		// Если используете WooCommerce Subscriptions
		/* AxeCode.tech: перед вызовом API подписок проверяем и класс, и функцию-хелпер. */
		if (class_exists('WC_Subscriptions') && function_exists('wcs_get_users_subscriptions')) {
			$subscriptions = wcs_get_users_subscriptions($user_id);
			
			foreach ($subscriptions as $subscription) {
				if ($subscription->has_status('active')) {
					return array(
                    'id' => $subscription->get_id(),
                    'name' => $subscription->get_name(),
                    'start_date' => $subscription->get_date('start'),
                    'end_date' => $subscription->get_date('end'),
                    'status' => $subscription->get_status()
					);
				}
			}
		}
		
		// Альтернатива: проверка через метаполя
		$active_subscription = get_user_meta($user_id, 'active_subscription', true);
		if ($active_subscription && $active_subscription['end_date'] > current_time('mysql')) {
			return $active_subscription;
		}
		
		return false;
	}
	
	// Функция для получения истории заказов
	function get_user_orders_history() {
		if (!is_user_logged_in()) return array();
		
		$user_id = get_current_user_id();
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('completed', 'processing'),
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
		));
		
		$order_history = array();
		
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				$order_history[] = array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'product_name' => $item->get_name(),
                'total' => $order->get_total(),
                'status' => $order->get_status()
				);
			}
		}
		
		return $order_history;
	}
	
	// Функция для получения сохраненных карт
	function get_user_saved_cards() {
		if (!is_user_logged_in()) return array();
		
		$user_id = get_current_user_id();
		$saved_cards = get_user_meta($user_id, 'saved_payment_cards', true);
		
		return $saved_cards ?: array();
	}
	
	// Шорткод для отображения управления подпиской
	function subscription_management_shortcode() {
		ob_start();
	?>
    <div class="subscription-management">
        <h3>Управление подпиской</h3>
        <?php
			$subscription = get_user_active_subscription();
			if ($subscription) {
			?>
            <div class="subscription-info">
                <p><strong>Текущий тариф:</strong> <?php echo $subscription['name']; ?></p>
                <p><strong>Действует до:</strong> <?php echo date('d.m.Y', strtotime($subscription['end_date'])); ?></p>
                <p><strong>Статус:</strong> <?php echo $subscription['status']; ?></p>
			</div>
            
            <div class="subscription-actions">
                <button class="btn btn-renew">Продлить подписку</button>
                <button class="btn btn-cancel">Отменить подписку</button>
			</div>
            <?php
				} else {
			?>
            <div class="no-subscription">
                <p>У вас нет активной подписки.</p>
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="btn">
                    Выбрать тариф
				</a>
			</div>
            <?php
			}
		?>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('subscription_management', 'subscription_management_shortcode');
	
	// Обработчик для добавления карты
	function handle_add_payment_method() {
		if (!isset($_POST['payment_nonce']) || !wp_verify_nonce($_POST['payment_nonce'], 'add_payment_method')) {
			wp_send_json_error('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('Не авторизован');
		}
		
		// Здесь должна быть интеграция с платежной системой (Stripe, etc.)
		// Это упрощенный пример
		
		$user_id = get_current_user_id();
		$card_data = array(
        'id' => 'card_' . uniqid(),
        'brand' => sanitize_text_field($_POST['card_brand']),
        'last4' => sanitize_text_field($_POST['card_last4']),
        'exp_month' => sanitize_text_field($_POST['card_exp_month']),
        'exp_year' => sanitize_text_field($_POST['card_exp_year']),
        'type' => sanitize_text_field($_POST['card_type'])
		);
		
		$saved_cards = get_user_meta($user_id, 'saved_payment_cards', true) ?: array();
		$saved_cards[] = $card_data;
		
		update_user_meta($user_id, 'saved_payment_cards', $saved_cards);
		
		wp_send_json_success('Карта успешно добавлена');
	}
	add_action('wp_ajax_add_payment_method', 'handle_add_payment_method');
	
	// Обработчик для удаления карты
	function handle_remove_payment_method() {
		if (!isset($_POST['card_id']) || !wp_verify_nonce($_POST['security'], 'remove_payment_method')) {
			wp_send_json_error('Ошибка безопасности');
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('Не авторизован');
		}
		
		$user_id = get_current_user_id();
		$card_id = sanitize_text_field($_POST['card_id']);
		$saved_cards = get_user_meta($user_id, 'saved_payment_cards', true) ?: array();
		
		$updated_cards = array_filter($saved_cards, function($card) use ($card_id) {
			return $card['id'] !== $card_id;
		});
		
		update_user_meta($user_id, 'saved_payment_cards', $updated_cards);
		
		wp_send_json_success('Карта успешно удалена');
	}
	add_action('wp_ajax_remove_payment_method', 'handle_remove_payment_method');
	
	// Функция для подключения разных header'ов
	function custom_get_header() {
		// Проверяем, используется ли шаблон "Личный кабинет"
		if (is_page_template('my-account')) {
			locate_template('header-lk.php', true);
			} else {
			locate_template('header.php', true);
		}
	}
	
	// Переопределяем стандартный get_header()
	remove_action('get_header', 'wp_get_header');
	add_action('get_header', 'custom_get_header');
	
	function reading_time() {
		$content = get_post_field('post_content', get_the_ID());
		$word_count = str_word_count(strip_tags($content));
		$reading_time = ceil($word_count / 200); // 200 слов в минуту
		
		return $reading_time;
	}
	
	
	// Добавление AJAX обработчиков
	add_action('wp_ajax_send_sms_code', 'handle_send_sms_code');
	add_action('wp_ajax_nopriv_send_sms_code', 'handle_send_sms_code');
	add_action('wp_ajax_verify_sms_code', 'handle_verify_sms_code');
	add_action('wp_ajax_nopriv_verify_sms_code', 'handle_verify_sms_code');
	add_action('wp_ajax_resend_sms_code', 'handle_resend_sms_code');
	add_action('wp_ajax_nopriv_resend_sms_code', 'handle_resend_sms_code');
	
	add_action('wp_ajax_yoga_email_login', 'handle_yoga_email_login');
	add_action('wp_ajax_nopriv_yoga_email_login', 'handle_yoga_email_login');
	add_action('wp_ajax_yoga_email_register', 'handle_yoga_email_register');
	add_action('wp_ajax_nopriv_yoga_email_register', 'handle_yoga_email_register');
	add_action('wp_ajax_yoga_lost_password', 'handle_yoga_lost_password');
	add_action('wp_ajax_nopriv_yoga_lost_password', 'handle_yoga_lost_password');
	
	// Вход по email и паролю
	function handle_yoga_email_login() {
		check_ajax_referer('yoga_login_nonce', 'yoga_login_nonce');
		$log = sanitize_text_field($_POST['log']);
		$pwd = $_POST['pwd'];
		if (empty($log) || empty($pwd)) {
			wp_send_json_error('Введите почту и пароль');
		}
		$user = wp_signon(array(
			'user_login'    => $log,
			'user_password' => $pwd,
			'remember'      => true,
		), false);
		if (is_wp_error($user)) {
			wp_send_json_error($user->get_error_message());
		}
		wp_send_json_success();
	}
	
	// Регистрация по email
	function handle_yoga_email_register() {
		check_ajax_referer('yoga_register_nonce', 'yoga_register_nonce');
		
		// Проверка reCAPTCHA
		$recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
		if (!verify_recaptcha($recaptcha_response)) {
			wp_send_json_error('Пожалуйста, подтвердите, что вы не робот');
		}
		
		$email = sanitize_email($_POST['user_email']);
		$name = sanitize_text_field($_POST['user_name']);
		$pass = $_POST['user_pass'];
		if (empty($email) || !is_email($email)) {
			wp_send_json_error('Введите корректный email');
		}
		if (empty($pass) || strlen($pass) < 6) {
			wp_send_json_error('Пароль должен быть не короче 6 символов');
		}
		if (username_exists($email) || email_exists($email)) {
			wp_send_json_error('Пользователь с таким email уже зарегистрирован');
		}
		$user_id = wp_create_user($email, $pass, $email);
		if (is_wp_error($user_id)) {
			wp_send_json_error($user_id->get_error_message());
		}
		wp_update_user(array('ID' => $user_id, 'display_name' => $name));
		
		// Отправка письма подтверждения регистрации
		$site_name = get_bloginfo('name');
		$login_url = wp_login_url(home_url('/'));
		$subject = sprintf('Регистрация на %s', $site_name);
		$message = sprintf(
			"Здравствуйте, %s!\n\nВы успешно зарегистрировались на сайте %s.\n\nДля входа используйте ваш email и пароль, который вы указали при регистрации.\n\nСтраница входа: %s\n\n— %s",
			$name,
			$site_name,
			$login_url,
			$site_name
		);
		$headers = array('Content-Type: text/plain; charset=UTF-8');
		$sent = wp_mail($email, $subject, $message, $headers);
		
		wp_set_auth_cookie($user_id);
		wp_send_json_success();
	}
	
	// Восстановление пароля
	function handle_yoga_lost_password() {
		check_ajax_referer('yoga_recovery_nonce', 'yoga_recovery_nonce');
		
		// Проверка reCAPTCHA
		$recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
		if (!verify_recaptcha($recaptcha_response)) {
			wp_send_json_error('Пожалуйста, подтвердите, что вы не робот');
		}
		
		$login = sanitize_text_field($_POST['user_login']);
		if (empty($login)) {
			wp_send_json_error('Введите email');
		}
		$user = get_user_by('email', $login);
		if (!$user) {
			$user = get_user_by('login', $login);
		}
		if (!$user) {
			wp_send_json_error('Пользователь с таким email не найден');
		}
		$result = retrieve_password($user->user_login);
		if (is_wp_error($result)) {
			wp_send_json_error($result->get_error_message());
		}
		wp_send_json_success();
	}
	
	// Отправка SMS кода
	function handle_send_sms_code() {
		check_ajax_referer('login_modal_nonce', 'security');
		
		$phone = sanitize_text_field($_POST['phone']);
		
		// Валидация номера телефона
		if (!validate_phone($phone)) {
			wp_send_json_error('Введите корректный номер телефона');
		}
		
		// Генерация кода
		$sms_code = rand(1000, 9999);
		// Сохранение кода в transient
		set_transient('sms_code_' . $phone, $sms_code, 5 * MINUTE_IN_SECONDS);
		/* AxeCode.tech (безопасность, этап 1): OTP хранится только на сервере и не возвращается клиенту. */

		
		// Интеграция с Яндекс.Облаком SMS
		$sms_sent = send_sms_via_yandex_cloud($phone, $sms_code);
		
		if ($sms_sent) {
			/* AxeCode.tech: в ответе только статус, без OTP и внутренних данных. */
			wp_send_json_success(array('message' => 'Code sent'));
			} else {
			wp_send_json_error('Ошибка отправки SMS');
		}
	}
	
	// Функция отправки SMS через Яндекс.Облако
	function send_sms_via_yandex_cloud($phone, $code) {
		// Настройки (должны храниться в настройках плагина/темы)
		$api_key = get_option('yandex_cloud_api_key', ''); // API ключ
		$folder_id = get_option('yandex_cloud_folder_id', ''); // ID каталога
		$from = get_option('yandex_cloud_sms_from', ''); // Имя отправителя
		
		if (empty($api_key) || empty($folder_id) || empty($from)) {
			error_log('Yandex Cloud SMS: Missing configuration');
			return false;
		}
		
		// Форматирование номера телефона
		$formatted_phone = format_phone_for_yandex($phone);
		
		// Текст сообщения
		$message = "Ваш код подтверждения: $code";
		
		// URL API Яндекс.Облака
		$url = "https://sms.api.cloud.yandex.net/sms/v2/senders/{$from}/send";
		
		// Данные для отправки
		$data = [
        'phoneNumbers' => [$formatted_phone],
        'text' => $message,
        'channel' => 'FREE_SIGN' // или 'DIRECT' для платных сообщений
		];
		
		$args = [
        'headers' => [
		'Authorization' => 'Api-Key ' . $api_key,
		'Content-Type' => 'application/json',
        ],
        'body' => json_encode($data),
        'timeout' => 30
		];
		
		$response = wp_remote_post($url, $args);
		
		if (is_wp_error($response)) {
			error_log('Yandex Cloud SMS Error: ' . $response->get_error_message());
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		
		if ($response_code === 200) {
			return true;
			} else {
			error_log("Yandex Cloud SMS Error: HTTP {$response_code} - {$response_body}");
			return false;
		}
	}
	
	// Функция форматирования номера телефона для Яндекс.Облака
	function format_phone_for_yandex($phone) {
		// Удаляем все нечисловые символы
		$clean_phone = preg_replace('/[^0-9]/', '', $phone);
		
		// Если номер начинается с 8, заменяем на +7
		if (substr($clean_phone, 0, 1) === '8') {
			$clean_phone = '7' . substr($clean_phone, 1);
		}
		
		// Добавляем + в начало
		return '+' . $clean_phone;
	}
	
	// Функция для добавления настроек в админку (опционально)
	// Добавляем меню в админку
	function add_yandex_sms_admin_menu() {
		add_options_page(
        'Настройки SMS',
        'SMS Настройки',
        'manage_options',
        'sms-settings',
        'yandex_sms_settings_page'
		);
	}
	add_action('admin_menu', 'add_yandex_sms_admin_menu');
	
	// Страница настроек
	function yandex_sms_settings_page() {
	?>
    <div class="wrap">
        <h1>Настройки SMS (Яндекс.Облако)</h1>
        <form method="post" action="options.php">
            <?php
				settings_fields('yandex_sms_settings');
				do_settings_sections('sms-settings');
				submit_button();
			?>
		</form>
	</div>
    <?php
	}
	
	// Регистрируем настройки
	function register_yandex_sms_settings() {
		register_setting('yandex_sms_settings', 'yandex_cloud_api_key');
		register_setting('yandex_sms_settings', 'yandex_cloud_folder_id');
		register_setting('yandex_sms_settings', 'yandex_cloud_sms_from');
		
		add_settings_section(
        'yandex_sms_section',
        'Основные настройки',
        null,
        'sms-settings'
		);
		
		add_settings_field(
        'yandex_cloud_api_key',
        'API Key Яндекс.Облака',
        'yandex_api_key_callback',
        'sms-settings',
        'yandex_sms_section'
		);
		
		add_settings_field(
        'yandex_cloud_folder_id',
        'Folder ID',
        'yandex_folder_id_callback',
        'sms-settings',
        'yandex_sms_section'
		);
		
		add_settings_field(
        'yandex_cloud_sms_from',
        'Имя отправителя',
        'yandex_sms_from_callback',
        'sms-settings',
        'yandex_sms_section'
		);
	}
	add_action('admin_init', 'register_yandex_sms_settings');
	
	// Функции отображения полей
	function yandex_api_key_callback() {
		$value = get_option('yandex_cloud_api_key', '');
		echo '<input type="password" name="yandex_cloud_api_key" value="' . esc_attr($value) . '" class="regular-text">';
		echo '<p class="description">API ключ из Яндекс.Облака (IAM)</p>';
	}
	
	function yandex_folder_id_callback() {
		$value = get_option('yandex_cloud_folder_id', '');
		echo '<input type="text" name="yandex_cloud_folder_id" value="' . esc_attr($value) . '" class="regular-text">';
		echo '<p class="description">ID каталога в Яндекс.Облаке</p>';
	}
	
	function yandex_sms_from_callback() {
		$value = get_option('yandex_cloud_sms_from', '');
		echo '<input type="text" name="yandex_cloud_sms_from" value="' . esc_attr($value) . '" class="regular-text">';
		echo '<p class="description">Одобренное имя отправителя (например: MyCompany)</p>';
	}
	
	// ========== Google reCAPTCHA ==========
	
	// Добавление пункта меню для настроек reCAPTCHA
	function add_recaptcha_admin_menu() {
		add_options_page(
			'Настройки reCAPTCHA',
			'reCAPTCHA',
			'manage_options',
			'recaptcha-settings',
			'recaptcha_settings_page'
		);
	}
	add_action('admin_menu', 'add_recaptcha_admin_menu');
	
	// Адрес отправителя (From) — должен совпадать с SMTP-логином, иначе «Sender address rejected»
	add_filter('wp_mail_from', function($from) {
		$override = get_option('yoga_mail_from_email', '');
		return is_email($override) ? $override : $from;
	}, 999);
	add_filter('wp_mail_from_name', function($name) {
		$override = get_option('yoga_mail_from_name', '');
		return $override !== '' ? $override : $name;
	}, 999);
	// Принудительно задаём From в PHPMailer после плагинов (WP Mail SMTP переопределяет иначе)
	add_action('phpmailer_init', function($phpmailer) {
		$from_email = get_option('yoga_mail_from_email', '');
		if (is_email($from_email)) {
			$phpmailer->From = $from_email;
			$phpmailer->Sender = $from_email;
		}
		$from_name = get_option('yoga_mail_from_name', '');
		if ($from_name !== '') {
			$phpmailer->FromName = $from_name;
		}
	}, 999);
	
	// Тестовая отправка wp_mail
	function add_test_mail_admin_menu() {
		add_options_page(
			'Тест почты',
			'Тест почты',
			'manage_options',
			'test-mail',
			'test_mail_page'
		);
	}
	add_action('admin_menu', 'add_test_mail_admin_menu');
	
	function test_mail_page() {
		$result = null;
		if (isset($_POST['yoga_test_mail']) && check_admin_referer('yoga_test_mail', 'yoga_test_mail_nonce')) {
			$to = sanitize_email($_POST['test_mail_to']);
			if (empty($to) || !is_email($to)) {
				$to = get_option('admin_email');
			}
			// Сохраняем From из формы и принудительно задаём его при тестовой отправке (после любых плагинов SMTP)
			$force_from_email = '';
			$force_from_name  = '';
			if (!empty($_POST['yoga_mail_from_email']) && is_email($_POST['yoga_mail_from_email'])) {
				$force_from_email = sanitize_email($_POST['yoga_mail_from_email']);
				update_option('yoga_mail_from_email', $force_from_email);
			} else {
				$force_from_email = get_option('yoga_mail_from_email', '');
			}
			if (isset($_POST['yoga_mail_from_name'])) {
				$force_from_name = sanitize_text_field($_POST['yoga_mail_from_name']);
				update_option('yoga_mail_from_name', $force_from_name);
			} else {
				$force_from_name = get_option('yoga_mail_from_name', '');
			}
			if ($force_from_email !== '') {
				add_action('phpmailer_init', function($phpmailer) use ($force_from_email, $force_from_name) {
					$phpmailer->From     = $force_from_email;
					$phpmailer->Sender   = $force_from_email;
					if ($force_from_name !== '') {
						$phpmailer->FromName = $force_from_name;
					}
				}, 99999);
			} else {
				$result = array(
					'success' => false,
					'to' => $to,
					'phpmailer_error' => 'Укажите Email отправителя (From) — он должен совпадать с логином SMTP (например sshell72@yandex.ru). Иначе сервер вернёт «Sender address rejected».'
				);
			}
			if ($result === null) {
				$subject = 'Тестовое письмо с сайта ' . get_bloginfo('name');
				$message = "Это тестовое письмо.\n\n";
				$message .= "Время отправки: " . current_time('mysql') . "\n";
				$message .= "Сайт: " . get_bloginfo('url') . "\n";
				$headers = array('Content-Type: text/plain; charset=UTF-8');
				$sent = wp_mail($to, $subject, $message, $headers);
				$result = array(
					'success' => $sent,
					'to' => $to,
					'phpmailer_error' => ''
				);
			}
			global $phpmailer;
			if (isset($phpmailer) && is_object($phpmailer) && isset($phpmailer->ErrorInfo) && !empty($phpmailer->ErrorInfo)) {
				$result['phpmailer_error'] = $phpmailer->ErrorInfo;
			}
		}
		?>
		<div class="wrap">
			<h1>Тестовая отправка wp_mail</h1>
			<p>Проверьте, работает ли отправка писем на сайте. Письмо будет отправлено на указанный email.</p>
			<?php if ($result !== null) : ?>
				<div class="notice notice-<?php echo $result['success'] ? 'success' : 'error'; ?> is-dismissible">
					<p>
						<?php if ($result['success']) : ?>
							<strong>Письмо отправлено.</strong> Проверьте почту на адресе <?php echo esc_html($result['to']); ?> (включая папку «Спам»).
						<?php else : ?>
							<strong>Ошибка отправки.</strong> wp_mail() вернул false.
							<?php if (!empty($result['phpmailer_error'])) : ?>
								<br>PHPMailer: <?php echo esc_html($result['phpmailer_error']); ?>
							<?php endif; ?>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>
			<form method="post" action="">
				<?php wp_nonce_field('yoga_test_mail', 'yoga_test_mail_nonce'); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="yoga_mail_from_email">Email отправителя (From)</label></th>
						<td>
							<input type="email" name="yoga_mail_from_email" id="yoga_mail_from_email" value="<?php echo esc_attr(get_option('yoga_mail_from_email', '')); ?>" class="regular-text" placeholder="sshell72@yandex.ru">
							<p class="description">Должен совпадать с логином SMTP (иначе «Sender address rejected»). Например: sshell72@yandex.ru</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="yoga_mail_from_name">Имя отправителя</label></th>
						<td>
							<input type="text" name="yoga_mail_from_name" id="yoga_mail_from_name" value="<?php echo esc_attr(get_option('yoga_mail_from_name', '')); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="test_mail_to">Email получателя</label></th>
						<td>
							<input type="email" name="test_mail_to" id="test_mail_to" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text">
							<p class="description">По умолчанию — email администратора</p>
						</td>
					</tr>
				</table>
				<?php submit_button('Отправить тестовое письмо', 'primary', 'yoga_test_mail'); ?>
			</form>
			<p class="description">Если письмо не приходит, установите SMTP-плагин (WP Mail SMTP, Easy WP SMTP) и настройте отправку через SMTP.</p>
		</div>
		<?php
	}
	
	// Страница настроек reCAPTCHA
	function recaptcha_settings_page() {
		?>
		<div class="wrap">
			<h1>Настройки Google reCAPTCHA</h1>
			<p>Для получения ключей зарегистрируйте сайт на <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a></p>
			<form method="post" action="options.php">
				<?php
				settings_fields('recaptcha_settings');
				do_settings_sections('recaptcha-settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
	
	// Регистрация настроек reCAPTCHA
	function register_recaptcha_settings() {
		register_setting('recaptcha_settings', 'recaptcha_site_key');
		register_setting('recaptcha_settings', 'recaptcha_secret_key');
		
		add_settings_section(
			'recaptcha_section',
			'Ключи reCAPTCHA',
			null,
			'recaptcha-settings'
		);
		
		add_settings_field(
			'recaptcha_site_key',
			'Site Key (публичный ключ)',
			'recaptcha_site_key_callback',
			'recaptcha-settings',
			'recaptcha_section'
		);
		
		add_settings_field(
			'recaptcha_secret_key',
			'Secret Key (секретный ключ)',
			'recaptcha_secret_key_callback',
			'recaptcha-settings',
			'recaptcha_section'
		);
	}
	add_action('admin_init', 'register_recaptcha_settings');
	
	// Функции отображения полей настроек
	function recaptcha_site_key_callback() {
		$value = get_option('recaptcha_site_key', '');
		echo '<input type="text" name="recaptcha_site_key" value="' . esc_attr($value) . '" class="regular-text">';
		echo '<p class="description">Публичный ключ для отображения виджета reCAPTCHA</p>';
	}
	
	function recaptcha_secret_key_callback() {
		$value = get_option('recaptcha_secret_key', '');
		echo '<input type="password" name="recaptcha_secret_key" value="' . esc_attr($value) . '" class="regular-text">';
		echo '<p class="description">Секретный ключ для проверки ответа на сервере</p>';
	}
	
	// Подключение скрипта reCAPTCHA v2
	function enqueue_recaptcha_script() {
		$site_key = get_option('recaptcha_site_key', '');
		if (!empty($site_key)) {
			wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=initRecaptcha&render=explicit&hl=ru', array(), null, true);
			// Скрипт инициализации виджетов
			wp_add_inline_script('google-recaptcha', '
				window.recaptchaWidgets = {};
				window.initRecaptcha = function() {
					if (typeof grecaptcha === "undefined" || typeof grecaptcha.render !== "function") return;
					var registerWidget = document.getElementById("recaptcha-register");
					var recoveryWidget = document.getElementById("recaptcha-recovery");
					if (registerWidget && !window.recaptchaWidgets.register && !registerWidget.querySelector("iframe")) {
						try {
							window.recaptchaWidgets.register = grecaptcha.render("recaptcha-register", {
								"sitekey": "' . esc_js($site_key) . '"
							});
						} catch (e) {}
					}
					if (recoveryWidget && !window.recaptchaWidgets.recovery && !recoveryWidget.querySelector("iframe")) {
						try {
							window.recaptchaWidgets.recovery = grecaptcha.render("recaptcha-recovery", {
								"sitekey": "' . esc_js($site_key) . '"
							});
						} catch (e) {}
					}
				};
				function doInitRecaptcha() {
					if (typeof grecaptcha !== "undefined" && typeof grecaptcha.render === "function") {
						window.initRecaptcha();
					} else if (typeof grecaptcha !== "undefined" && grecaptcha.ready) {
						grecaptcha.ready(function() { window.initRecaptcha(); });
					} else {
						var t = setInterval(function() {
							if (typeof grecaptcha !== "undefined" && typeof grecaptcha.render === "function") {
								clearInterval(t);
								window.initRecaptcha();
							}
						}, 100);
						setTimeout(function() { clearInterval(t); }, 5000);
					}
				}
				jQuery(document).ready(function($) { doInitRecaptcha(); });
			');
		}
	}
	add_action('wp_enqueue_scripts', 'enqueue_recaptcha_script');
	
	// Проверка ответа reCAPTCHA
	function verify_recaptcha($response_token) {
		$secret_key = get_option('recaptcha_secret_key', '');
		if (empty($secret_key)) {
			// Если ключ не настроен, пропускаем проверку (для разработки)
			return true;
		}
		if (empty($response_token)) {
			return false;
		}
		
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$data = array(
			'secret' => $secret_key,
			'response' => $response_token,
			'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
		);
		
		$response = wp_remote_post($url, array(
			'body' => $data,
			'timeout' => 10
		));
		
		if (is_wp_error($response)) {
			return false;
		}
		
		$body = wp_remote_retrieve_body($response);
		$json = json_decode($body, true);
		
		return isset($json['success']) && $json['success'] === true;
	}
	
	// Проверка SMS кода
	function handle_verify_sms_code() {
		check_ajax_referer('login_modal_nonce', 'security');
		
		$phone = sanitize_text_field($_POST['phone']);
		$sms_code = sanitize_text_field($_POST['sms_code']);
		$terms_accepted = isset($_POST['checkbox_conf']);
		
		if (!$terms_accepted) {
			wp_send_json_error('Необходимо принять условия использования');
		}
		
		$stored_code = get_transient('sms_code_' . $phone);
		
		if ($stored_code && $stored_code == $sms_code) {
			// Авторизация или регистрация пользователя
			$user = login_or_register_user($phone);
			
			if ($user && !is_wp_error($user)) {
				wp_set_auth_cookie($user->ID);
				delete_transient('sms_code_' . $phone);
				wp_send_json_success('Успешный вход');
				} else {
				wp_send_json_error('Ошибка входа');
			}
			} else {
			wp_send_json_error('Неверный код');
		}
	}
	
	// Регистрация или вход пользователя
	function login_or_register_user($phone) {
		$username = 'user_' . preg_replace('/[^0-9]/', '', $phone);
		$user = get_user_by('login', $username);
		
		if (!$user) {
			// Регистрация нового пользователя
			$user_id = wp_create_user($username, wp_generate_password(), '');
			
			if (!is_wp_error($user_id)) {
				update_user_meta($user_id, 'phone', $phone);
				$user = get_user_by('id', $user_id);
			}
		}
		
		return $user;
	}
	
	// Валидация телефона
	function validate_phone($phone) {
		return preg_match('/^\+7\s?\(?\d{3}\)?\s?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/', $phone);
	}		
	
	// Обработчик для добавления/удаления из избранного
	function toggle_favorite_practice() {
		/* AxeCode.tech (безопасность, этап 1): восстановлена CSRF-проверка для AJAX-действия избранного. */
		check_ajax_referer('favorite_practice_nonce', 'security');
		
		if (!is_user_logged_in()) {
			wp_die('Не авторизован');
		}
		
		$practice_id = intval($_POST['practice_id']);
		$user_id = get_current_user_id();
		$favorites = get_user_meta($user_id, 'favorite_practices', true);
		
		if (empty($favorites)) {
			$favorites = array();
		}
		
		if (in_array($practice_id, $favorites)) {
			$favorites = array_diff($favorites, array($practice_id));
			$message = 'Удалено из избранного';
			} else {
			$favorites[] = $practice_id;
			$message = 'Добавлено в избранное';
		}
		
		update_user_meta($user_id, 'favorite_practices', $favorites);
		
		wp_send_json_success($message);
	}
	add_action('wp_ajax_toggle_favorite_practice', 'toggle_favorite_practice');
	
	// Получение информации о текущем активном тарифе
	function get_current_user_tariff($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id) return false;
		
		$orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => 'completed',
        'limit' => -1,
        'orderby' => 'date_completed',
        'order' => 'DESC'
		));
		
		$current_time = current_time('timestamp');
		$latest_tariff = null;
		$latest_end = 0;
		
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				$product = $item->get_product();
				$product_id = $product->get_id();
				$period = get_field('price_period', $product_id);
				if ($period) {
					$order_date = $order->get_date_completed()->getTimestamp();
					$access_duration = calculate_access_duration($period);
					$access_end = $order_date + $access_duration;
					
					if ($access_end > $current_time && $access_end > $latest_end) {
						$latest_end = $access_end;
						$latest_tariff = array(
                        'product_id' => $product_id,
                        'product_name' => $product->get_name(),
                        'period' => $period,
                        'order_id' => $order->get_id(),
                        'order_date' => $order->get_date_completed()->format('d.m.Y'),
                        'access_end' => $access_end,
                        'access_end_date' => date('d.m.Y H:i', $access_end),
                        'remaining_time' => $access_end - $current_time
						);
					}
				}
			}
		}
		
		return $latest_tariff;
	}

	if (!function_exists('calculate_access_duration')) {
		/* AxeCode.tech: добавлен fallback-хелпер, т.к. функция используется в расчете тарифа. */
		function calculate_access_duration($period) {
			$period = trim((string) $period);

			if ($period === '') {
				return 30 * DAY_IN_SECONDS;
			}

			// Formats like "30", "30d", "2w", "3m", "1y".
			if (preg_match('/^(\d+)\s*([dwmy])?$/i', $period, $matches)) {
				$value = (int) $matches[1];
				$unit = isset($matches[2]) ? strtolower($matches[2]) : 'd';

				switch ($unit) {
					case 'w':
						return $value * WEEK_IN_SECONDS;
					case 'm':
						return $value * 30 * DAY_IN_SECONDS;
					case 'y':
						return $value * 365 * DAY_IN_SECONDS;
					case 'd':
					default:
						return $value * DAY_IN_SECONDS;
				}
			}

			return 30 * DAY_IN_SECONDS;
		}
	}




