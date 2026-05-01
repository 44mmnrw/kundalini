<?php
	@ini_set( 'upload_max_size' , '256M' );
	@ini_set( 'post_max_size', '256M');
	@ini_set( 'max_execution_time', '300' );
	/* Axecode.tech: Р­С‚Р°Рї 3 (СѓРЅРёРІРµСЂСЃР°Р»СЊРЅРѕСЃС‚СЊ) вЂ” РјРѕРґСѓР»СЊРЅС‹Рµ РїРѕРґРєР»СЋС‡РµРЅРёСЏ.
	 * РџРµСЂРµС…РѕРґ РІС‹РїРѕР»РЅСЏРµС‚СЃСЏ РїРѕСЌС‚Р°РїРЅРѕ, Р±РµР· СЂРёСЃРєР° РґРІРѕР№РЅРѕР№ СЂРµРіРёСЃС‚СЂР°С†РёРё С…СѓРєРѕРІ.
	 */
	require_once get_template_directory() . '/inc/core/ajax-responses.php';
	require_once get_template_directory() . '/inc/core/dependencies.php';
	require_once get_template_directory() . '/inc/integrations/acf.php';
	require_once get_template_directory() . '/inc/ajax/payments.php';
	require_once get_template_directory() . '/inc/ajax/favorites.php';
	/* Axecode.tech: Р­С‚Р°Рї 3 (СѓРЅРёРІРµСЂСЃР°Р»СЊРЅРѕСЃС‚СЊ) вЂ” РІС‹РЅРѕСЃРёРј auth/SMS AJAX РІ РѕС‚РґРµР»СЊРЅС‹Р№ РјРѕРґСѓР»СЊ. */
	require_once get_template_directory() . '/inc/ajax/auth-sms.php';

	// Fallback, РµСЃР»Рё ACF РІСЂРµРјРµРЅРЅРѕ РѕС‚РєР»СЋС‡РµРЅ: РЅРµ РґР°РµРј С‚РµРјРµ РїР°РґР°С‚СЊ РЅР° РІС‹Р·РѕРІР°С… ACF-С„СѓРЅРєС†РёР№.
		/* Axecode.tech: Р­С‚Р°Рї 3 (СѓРЅРёРІРµСЂСЃР°Р»СЊРЅРѕСЃС‚СЊ) вЂ” fallback ACF РѕСЃС‚Р°РІР»СЏРµРј С‚РѕР»СЊРєРѕ РґР»СЏ С„СЂРѕРЅС‚РµРЅРґР°.
	 * Р’ Р°РґРјРёРЅРєРµ/Р°РєС‚РёРІР°С†РёРё РїР»Р°РіРёРЅРѕРІ Р·Р°РіР»СѓС€РєРё Р·Р°РїСЂРµС‰РµРЅС‹, РёРЅР°С‡Рµ РІРѕР·РЅРёРєР°РµС‚ С„Р°С‚Р°Р»
	 * \"Cannot redeclare get_field()\" РїСЂРё Р°РєС‚РёРІР°С†РёРё Advanced Custom Fields PRO.
	 */
	/* Axecode.tech: CLI-СЂРµР¶РёРј РёСЃРєР»СЋС‡Р°РµРј, РёРЅР°С‡Рµ РїСЂРё Р°РєС‚РёРІР°С†РёРё ACF РёР· РєРѕРЅСЃРѕР»Рё Р»РѕРІРёРј redeclare get_field(). */
	$yoga_allow_acf_fallback = !is_admin() && !(defined('WP_CLI') && WP_CLI) && (php_sapi_name() !== 'cli');
	if ($yoga_allow_acf_fallback) {
		if (!function_exists('get_field')) {
			function get_field($selector, $post_id = false, $format_value = true) {
				return null;
			}
		}
		if (!function_exists('the_field')) {
			function the_field($selector, $post_id = false, $format_value = true) {
				echo '';
			}
		}
		if (!function_exists('have_rows')) {
			function have_rows($selector, $post_id = false) {
				return false;
			}
		}
		if (!function_exists('the_row')) {
			function the_row() {
				return null;
			}
		}
		if (!function_exists('the_sub_field')) {
			function the_sub_field($selector, $format_value = true) {
				echo '';
			}
		}
	}
	if (!function_exists('yoga_ajax_error')) {
		/* Axecode.tech: Р­С‚Р°Рї 2 СЃС‚Р°Р±РёР»РёР·Р°С†РёРё - РµРґРёРЅС‹Р№ С„РѕСЂРјР°С‚ AJAX-РѕС€РёР±РѕРє. */
		function yoga_ajax_error($message, $code = 'error', $status = 400, $extra = array()) {
			$payload = array_merge(array(
				'code' => $code,
				'message' => $message,
			), $extra);
			wp_send_json_error($payload, $status);
		}
	}
	if (!function_exists('yoga_ajax_success')) {
		/* Axecode.tech: Р­С‚Р°Рї 2 СЃС‚Р°Р±РёР»РёР·Р°С†РёРё - РµРґРёРЅС‹Р№ С„РѕСЂРјР°С‚ AJAX-СѓСЃРїРµС…Р°. */
		function yoga_ajax_success($message = '', $data = array(), $status = 200) {
			$payload = array_merge(array(
				'message' => $message,
			), $data);
			wp_send_json_success($payload, $status);
		}
	}
	// Р РµРіРёСЃС‚СЂР°С†РёСЏ РјРµРЅСЋ
	function my_theme_setup() {
		register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'yoga' ),
        'footer'  => __( 'Footer Menu', 'yoga' ),
		) );
		add_theme_support( 'post-thumbnails' );
	}
	add_action( 'after_setup_theme', 'my_theme_setup' );
	
	// РџРѕРґРєР»СЋС‡РµРЅРёРµ СЃС‚РёР»РµР№ Рё СЃРєСЂРёРїС‚РѕРІ
	function my_theme_scripts() {
		$theme_uri = get_template_directory_uri();
		
		// РЎС‚РёР»Рё
		wp_enqueue_style( 'main-style', $theme_uri . '/assets/css/style.css', array(), '1.0.0' );
		wp_enqueue_style( 'mulish-style', $theme_uri . '/assets/css/mulish.css', array(), '1.0.0' );
		wp_enqueue_style( 'animate-style', $theme_uri . '/assets/css/animate.css', array(), '1.0.0' );
		
		// РЎРєСЂРёРїС‚С‹ (jQuery СѓР¶Рµ РІС…РѕРґРёС‚ РІ СЃРѕСЃС‚Р°РІ WordPress)
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
		
		// Plyr JS - Р·Р°РіСЂСѓР¶Р°РµРј РїРµСЂРІС‹Рј
		wp_enqueue_script('plyr-js', get_template_directory_uri() . '/assets/js/plyr.min.js', array(), '3.7.8', true);
		
		// РљР°СЃС‚РѕРјРЅС‹Р№ СЃРєСЂРёРїС‚ - Р·Р°РІРёСЃРёС‚ РѕС‚ plyr-js Рё jQuery
		wp_enqueue_script('practice-player', get_template_directory_uri() . '/assets/js/practice-player.js', 
        array('plyr-js', 'jquery'), '1.0.0', true);
		
		
		
		
		// Р›РѕРєР°Р»РёР·Р°С†РёСЏ Р±Р°Р·РѕРІС‹С… СЃС‚СЂРѕРє (РїРµСЂРµРІРѕРґС‹/РїРѕРґРїРёСЃРё)
		wp_localize_script('practice-js', 'practiceI18n', [
		'pause' => 'РџР°СѓР·Р°',
		'play' => 'РџСѓСЃРє',
		'next' => 'Р”Р°Р»РµРµ',
		'prev' => 'РќР°Р·Р°Рґ',
		'stage' => 'Р­С‚Р°Рї',
		'locked' => 'Р”РѕСЃС‚СѓРї С‚РѕР»СЊРєРѕ РїРѕ РїРѕРґРїРёСЃРєРµ',
		'demo_over' => 'Р”РµРјРѕ-С„СЂР°РіРјРµРЅС‚ Р·Р°РІРµСЂС€С‘РЅ',
		]);
	}
	add_action( 'wp_enqueue_scripts', 'my_theme_scripts' );
	
	// РћРїС†РёРё ACF
	// РћР±СЂР°Р±РѕС‚С‡РёРє AJAX РґР»СЏ С„РѕСЂРјС‹ РїРѕРґРїРёСЃРєРё
	function yoga_subscribe_handler() {
		// РџСЂРѕРІРµСЂРєР° nonce РґР»СЏ Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё
		if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
			wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
		}
		
		$email = sanitize_email($_POST['email']);
		
		if (!is_email($email)) {
			wp_send_json_error('РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ email Р°РґСЂРµСЃ');
		}
		
		// Р—РґРµСЃСЊ РјРѕР¶РЅРѕ РґРѕР±Р°РІРёС‚СЊ Р»РѕРіРёРєСѓ РїРѕРґРїРёСЃРєРё:
		// - Р”РѕР±Р°РІР»РµРЅРёРµ РІ Р±Р°Р·Сѓ РґР°РЅРЅС‹С…
		// - РРЅС‚РµРіСЂР°С†РёСЏ СЃ СЃРµСЂРІРёСЃРѕРј СЂР°СЃСЃС‹Р»РѕРє (Mailchimp, SendPulse Рё С‚.Рґ.)
		// - РћС‚РїСЂР°РІРєР° СѓРІРµРґРѕРјР»РµРЅРёСЏ Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ
		
		// РџСЂРёРјРµСЂ: СЃРѕС…СЂР°РЅРµРЅРёРµ РІ РѕРїС†РёРё WordPress
		$subscribers = get_option('yoga_subscribers', array());
		if (!in_array($email, $subscribers)) {
			$subscribers[] = $email;
			update_option('yoga_subscribers', $subscribers);
			
			// РћС‚РїСЂР°РІРєР° email Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ)
			$admin_email = get_option('admin_email');
			$subject = 'РќРѕРІС‹Р№ РїРѕРґРїРёСЃС‡РёРє РЅР° СЃР°Р№С‚Рµ ' . get_bloginfo('name');
			$message = "РќРѕРІС‹Р№ email РїРѕРґРїРёСЃС‡РёРєР°: $email\n";
			$message .= "Р’СЂРµРјСЏ: " . current_time('mysql') . "\n";
			wp_mail($admin_email, $subject, $message);
		}
		
		wp_send_json_success('РџРѕРґРїРёСЃРєР° СѓСЃРїРµС€РЅРѕ РѕС„РѕСЂРјР»РµРЅР°');
	}
	add_action('wp_ajax_yoga_subscribe', 'yoga_subscribe_handler');
	add_action('wp_ajax_nopriv_yoga_subscribe', 'yoga_subscribe_handler');
	
	// Р›РѕРєР°Р»РёР·Р°С†РёСЏ AJAX РїР°СЂР°РјРµС‚СЂРѕРІ
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
	
// Р¤СѓРЅРєС†РёСЏ С€Р°Р±Р»РѕРЅР° РєРѕРјРјРµРЅС‚Р°СЂРёСЏ
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
                    <?php printf(_x('%s РЅР°Р·Р°Рґ', '%s = human-readable time difference', 'textdomain'), human_time_diff(get_comment_time('U'), current_time('timestamp'))); ?>
                </span>
                <div class="praktika-comment-item__main-action">
                    <?php if ($is_own_comment): ?>
                       <!-- <div class="your-comm">
                            <div class="your-comm__btn your-comm__btn_edit" onclick="toggleEditForm(<?php echo $comment->comment_ID; ?>)">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/edit-icon.png" alt="Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ">
                            </div>
                            <div class="your-comm__btn your-comm__btn_del" onclick="deleteComment(<?php echo $comment->comment_ID; ?>)">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/del-icon.png" alt="РЈРґР°Р»РёС‚СЊ">
                            </div>
                        </div>-->
                    <?php else: ?>
                        <div class="answer-btn" onclick="toggleReplyForm(<?php echo $comment->comment_ID; ?>)">
                            <span>РћС‚РІРµС‚РёС‚СЊ</span>
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
            
            <!-- Р¤РѕСЂРјР° СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ (С‚РѕР»СЊРєРѕ РґР»СЏ СЃРІРѕРёС… РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ) -->
            <?php if ($is_own_comment): ?>
            <form class="praktika-comment-item__edit hidden" id="edit-form-<?php echo $comment->comment_ID; ?>">
                <div class="answer-main">
                    <textarea name="comment_content" class="input textarea-resize" rows="1"><?php echo esc_textarea($comment->comment_content); ?></textarea>
                    <button type="button" class="btn" onclick="updateComment(<?php echo $comment->comment_ID; ?>)">
                        РћР±РЅРѕРІРёС‚СЊ
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <!-- Р¤РѕСЂРјР° РѕС‚РІРµС‚Р° -->
        <div class="praktika-comment__answer hidden" id="reply-form-<?php echo $comment->comment_ID; ?>">
            <div class="answer-main">
                <div class="answer-main__image">
                    <?php echo get_avatar(get_current_user_id(), 40); ?>
                </div>
                <textarea name="reply_content" class="input textarea-resize" placeholder="Р’Р°С€ РѕС‚РІРµС‚" rows="1"></textarea>
                <button type="button" class="btn" >
                    РћС‚РїСЂР°РІРёС‚СЊ
                </button>
            </div>
        </div>
    </div>
    <?php
}

// РћР±СЂР°Р±РѕС‚РєР° AJAX РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ
add_action('wp_ajax_submit_custom_comment', 'handle_custom_comment');
add_action('wp_ajax_nopriv_submit_custom_comment', 'handle_custom_comment');

function handle_custom_comment() {
    // РџСЂРѕРІРµСЂРєР° nonce - РёСЃРїРѕР»СЊР·СѓРµРј РІР°С€ 'yoga_ajax_nonce'
    if (!wp_verify_nonce($_POST['comment_security'], 'yoga_ajax_nonce')) {
        wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
    }
    
    // РћРїСЂРµРґРµР»СЏРµРј Р°РІС‚РѕСЂР° РёСЃРїРѕР»СЊР·СѓСЏ РІР°С€Рё РґР°РЅРЅС‹Рµ
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = $current_user->display_name ?: $current_user->user_login;
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Р“РѕСЃС‚СЊ';
        $comment_author_email = '';
        $user_id = 0;
    }
    
    // Р”Р°РЅРЅС‹Рµ РєРѕРјРјРµРЅС‚Р°СЂРёСЏ
    $comment_data = array(
        'comment_post_ID' => intval($_POST['post_id']),
        'comment_content' => sanitize_textarea_field($_POST['comment']),
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_author_email,
        'comment_author_url' => '',
        'user_id' => $user_id,
        'comment_approved' => 1,
    );
    
    // Р’СЃС‚Р°РІР»СЏРµРј РєРѕРјРјРµРЅС‚Р°СЂРёР№
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        wp_send_json_success('РљРѕРјРјРµРЅС‚Р°СЂРёР№ РґРѕР±Р°РІР»РµРЅ');
    } else {
        wp_send_json_error('РћС€РёР±РєР° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё РєРѕРјРјРµРЅС‚Р°СЂРёСЏ');
    }
}

// РћР±СЂР°Р±РѕС‚РєР° РѕС‚РІРµС‚РѕРІ РЅР° РєРѕРјРјРµРЅС‚Р°СЂРёРё
add_action('wp_ajax_submit_comment_reply', 'handle_comment_reply');
add_action('wp_ajax_nopriv_submit_comment_reply', 'handle_comment_reply');

function handle_comment_reply() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
    }
    
    // РћРїСЂРµРґРµР»СЏРµРј Р°РІС‚РѕСЂР°
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $comment_author = $current_user->display_name ?: $current_user->user_login;
        $comment_author_email = $current_user->user_email;
        $user_id = $current_user->ID;
    } else {
        $comment_author = 'Р“РѕСЃС‚СЊ';
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
        wp_send_json_success('РћС‚РІРµС‚ РґРѕР±Р°РІР»РµРЅ');
    } else {
        wp_send_json_error('РћС€РёР±РєР° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё РѕС‚РІРµС‚Р°');
    }
}

// РћР±РЅРѕРІР»РµРЅРёРµ РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ (С‚РѕР»СЊРєРѕ РґР»СЏ Р·Р°СЂРµРіРёСЃС‚СЂРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№)
add_action('wp_ajax_update_comment', 'handle_comment_update');

function handle_comment_update() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
    }
    
    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);
    
    // РџСЂРѕРІРµСЂСЏРµРј, С‡С‚Рѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ РјРѕР¶РµС‚ СЂРµРґР°РєС‚РёСЂРѕРІР°С‚СЊ РєРѕРјРјРµРЅС‚Р°СЂРёР№
    if (!current_user_can('edit_comment', $comment_id) || $comment->user_id != get_current_user_id()) {
        wp_send_json_error('РќРµРґРѕСЃС‚Р°С‚РѕС‡РЅРѕ РїСЂР°РІ РґР»СЏ СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ РєРѕРјРјРµРЅС‚Р°СЂРёСЏ');
    }
    
    $comment_data = array(
        'comment_ID' => $comment_id,
        'comment_content' => sanitize_textarea_field($_POST['content']),
    );
    
    $result = wp_update_comment($comment_data);
    
    if ($result) {
        wp_send_json_success('РљРѕРјРјРµРЅС‚Р°СЂРёР№ РѕР±РЅРѕРІР»РµРЅ');
    } else {
        wp_send_json_error('РћС€РёР±РєР° РїСЂРё РѕР±РЅРѕРІР»РµРЅРёРё РєРѕРјРјРµРЅС‚Р°СЂРёСЏ');
    }
}

// РЈРґР°Р»РµРЅРёРµ РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ (С‚РѕР»СЊРєРѕ РґР»СЏ Р·Р°СЂРµРіРёСЃС‚СЂРёСЂРѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№)
add_action('wp_ajax_delete_comment', 'handle_comment_delete');

function handle_comment_delete() {
    if (!wp_verify_nonce($_POST['security'], 'yoga_ajax_nonce')) {
        wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
    }
    
    $comment_id = intval($_POST['comment_id']);
    
    // РџСЂРѕРІРµСЂСЏРµРј, С‡С‚Рѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ РјРѕР¶РµС‚ СѓРґР°Р»СЏС‚СЊ РєРѕРјРјРµРЅС‚Р°СЂРёР№
    if (!current_user_can('edit_comment', $comment_id) || get_comment($comment_id)->user_id != get_current_user_id()) {
        wp_send_json_error('РќРµРґРѕСЃС‚Р°С‚РѕС‡РЅРѕ РїСЂР°РІ РґР»СЏ СѓРґР°Р»РµРЅРёСЏ РєРѕРјРјРµРЅС‚Р°СЂРёСЏ');
    }
    
    $result = wp_delete_comment($comment_id, true);
    
    if ($result) {
        wp_send_json_success('РљРѕРјРјРµРЅС‚Р°СЂРёР№ СѓРґР°Р»РµРЅ');
    } else {
        wp_send_json_error('РћС€РёР±РєР° РїСЂРё СѓРґР°Р»РµРЅРёРё РєРѕРјРјРµРЅС‚Р°СЂРёСЏ');
    }
}


	
	// Р Р°Р·СЂРµС€РёС‚СЊ РєРѕРјРјРµРЅС‚Р°СЂРёРё РґР»СЏ custom post type 'practice'
	function enable_comments_for_practice($open, $post_id) {
		$post = get_post($post_id);
		if ($post->post_type == 'practice') {
			return true;
		}
		return $open;
	}
	add_filter('comments_open', 'enable_comments_for_practice', 10, 2);
	
	// Р’РєР»СЋС‡РёС‚СЊ РїРѕРґРґРµСЂР¶РєСѓ РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ РґР»СЏ custom post type
	function add_comments_support_for_practice() {
		add_post_type_support('practice', 'comments');
	}
	add_action('init', 'add_comments_support_for_practice');
	
	// РљР°СЃС‚РѕРјРёР·Р°С†РёСЏ Р°РІР°С‚Р°СЂРѕРІ
	add_filter('avatar_defaults', 'custom_avatar_defaults');
	function custom_avatar_defaults($avatar_defaults) {
		$avatar_defaults[get_template_directory_uri() . '/assets/img/default-avatar.png'] = 'Default Avatar';
		return $avatar_defaults;
	}
	
	// Р’СЂРµРјСЏ РєРѕРјРјРµРЅС‚Р°СЂРёРµРІ РЅР° СЂСѓСЃСЃРєРѕРј
	function russian_comment_time($date, $d, $comment) {
		if (!is_admin()) {
			return human_time_diff(get_comment_time('U'), current_time('timestamp')) . ' РЅР°Р·Р°Рґ';
		}
		return $date;
	}
	add_filter('get_comment_date', 'russian_comment_time', 10, 3);
	
	// РћР±СЂР°Р±РѕС‚РєР° AJAX С„РѕСЂРјС‹ РєРѕРЅС‚Р°РєС‚РѕРІ
	add_action('wp_ajax_process_contact_form', 'process_contact_form');
	add_action('wp_ajax_nopriv_process_contact_form', 'process_contact_form');
	
	function process_contact_form() {
		// РџСЂРѕРІРµСЂРєР° nonce
		if (!wp_verify_nonce($_POST['contacts_nonce_field'], 'contacts_nonce')) {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё'));
		}
		
		// Р’Р°Р»РёРґР°С†РёСЏ Рё СЃР°РЅРёС‚РёР·Р°С†РёСЏ РґР°РЅРЅС‹С…
		$name = sanitize_text_field($_POST['contacts_name']);
		$email = sanitize_email($_POST['contacts_email']);
		$phone = sanitize_text_field($_POST['contacts_phone']);
		$message = sanitize_textarea_field($_POST['contacts_message']);
		
		if (empty($name) || empty($email) || empty($phone) || empty($message)) {
			wp_send_json_error(array('message' => 'РџРѕР¶Р°Р»СѓР№СЃС‚Р°, Р·Р°РїРѕР»РЅРёС‚Рµ РІСЃРµ РїРѕР»СЏ'));
		}
		
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'РџРѕР¶Р°Р»СѓР№СЃС‚Р°, РІРІРµРґРёС‚Рµ РєРѕСЂСЂРµРєС‚РЅС‹Р№ email'));
		}
		
		// РћС‚РїСЂР°РІРєР° email Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ
		//$to = get_option('admin_email');
		$to = 'sshell72@yandex.ru';
		$subject = 'РќРѕРІРѕРµ СЃРѕРѕР±С‰РµРЅРёРµ СЃ С„РѕСЂРјС‹ РєРѕРЅС‚Р°РєС‚РѕРІ';
		$body = "
        РРјСЏ: $name
        Email: $email
        РўРµР»РµС„РѕРЅ: $phone
        РЎРѕРѕР±С‰РµРЅРёРµ: $message
		";
		
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$sent = wp_mail($to, $subject, nl2br($body), $headers);
		
		if ($sent) {
			// РЎРѕС…СЂР°РЅРµРЅРёРµ РІ Р±Р°Р·Сѓ РґР°РЅРЅС‹С… (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ)
			save_contact_message($name, $email, $phone, $message);
			
			wp_send_json_success(array('message' => 'РЎРѕРѕР±С‰РµРЅРёРµ РѕС‚РїСЂР°РІР»РµРЅРѕ СѓСЃРїРµС€РЅРѕ!'));
			} else {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° РїСЂРё РѕС‚РїСЂР°РІРєРµ СЃРѕРѕР±С‰РµРЅРёСЏ'));
		}
	}
	
	// РЎРѕС…СЂР°РЅРµРЅРёРµ СЃРѕРѕР±С‰РµРЅРёСЏ РІ Р±Р°Р·Сѓ РґР°РЅРЅС‹С…
	function save_contact_message($name, $email, $phone, $message) {
		$post_data = array(
        'post_title' => 'РЎРѕРѕР±С‰РµРЅРёРµ РѕС‚ ' . $name,
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
	
	// РћР±СЂР°Р±РѕС‚РєР° AJAX РїРѕРґРїРёСЃРєРё
	add_action('wp_ajax_process_subscription', 'process_subscription');
	add_action('wp_ajax_nopriv_process_subscription', 'process_subscription');
	
	function process_subscription() {
		if (!wp_verify_nonce($_POST['nonce'], 'subscription_nonce')) {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё'));
		}
		
		$email = sanitize_email($_POST['email']);
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'РџРѕР¶Р°Р»СѓР№СЃС‚Р°, РІРІРµРґРёС‚Рµ РєРѕСЂСЂРµРєС‚РЅС‹Р№ email'));
		}
		
		$saved = save_subscription_email($email);
		
		if ($saved) {
			wp_mail(
            get_option('admin_email'),
            'РќРѕРІР°СЏ РїРѕРґРїРёСЃРєР° РЅР° СЃР°Р№С‚Рµ',
            'РќРѕРІС‹Р№ email РґР»СЏ РїРѕРґРїРёСЃРєРё: ' . $email
			);
			
			wp_send_json_success(array('message' => 'РџРѕРґРїРёСЃРєР° РѕС„РѕСЂРјР»РµРЅР° СѓСЃРїРµС€РЅРѕ!'));
			} else {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° РїСЂРё СЃРѕС…СЂР°РЅРµРЅРёРё РїРѕРґРїРёСЃРєРё'));
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
		private $item_counter = 0; // РЎС‡РµС‚С‡РёРє РїСѓРЅРєС‚РѕРІ РјРµРЅСЋ
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			// РџСЂРѕРІРµСЂСЏРµРј, СЏРІР»СЏРµС‚СЃСЏ Р»Рё СЌС‚Рѕ РїРµСЂРІС‹Р№ РїСѓРЅРєС‚ РјРµРЅСЋ
			$is_first_item = ($this->item_counter === 0);
			
			// Р”РѕР±Р°РІР»СЏРµРј РєР»Р°СЃСЃ main-menu-active-item С‚РѕР»СЊРєРѕ РїРµСЂРІРѕРјСѓ РїСѓРЅРєС‚Сѓ
			$active_class = $is_first_item ? 'main-menu-active-item' : '';
			
			$output .= '<li class="' . $active_class . '">';
			
			// РЎРѕР·РґР°РµРј СЃСЃС‹Р»РєСѓ
			$attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
			$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
			$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
			$attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
			
			/* AxeCode.tech (СЌС‚Р°Рї 1): РЅРѕСЂРјР°Р»РёР·Р°С†РёСЏ $args РІ walker РґР»СЏ СЃРѕРІРјРµСЃС‚РёРјРѕСЃС‚Рё СЃ PHP 8.x. */
			$args_before = is_object($args) ? ($args->before ?? '') : (is_array($args) ? ($args['before'] ?? '') : '');
			$args_link_before = is_object($args) ? ($args->link_before ?? '') : (is_array($args) ? ($args['link_before'] ?? '') : '');
			$args_link_after = is_object($args) ? ($args->link_after ?? '') : (is_array($args) ? ($args['link_after'] ?? '') : '');
			$args_after = is_object($args) ? ($args->after ?? '') : (is_array($args) ? ($args['after'] ?? '') : '');
			$item_output = $args_before;
			$item_output .= '<a class="ref"' . $attributes . '>';
			$item_output .= $args_link_before . apply_filters('the_title', $item->title, $item->ID) . $args_link_after;
			
			// Р”РѕР±Р°РІР»СЏРµРј РёРєРѕРЅРєРё С‚РѕР»СЊРєРѕ РґР»СЏ РїРµСЂРІРѕРіРѕ РїСѓРЅРєС‚Р°
			if ($is_first_item) {
				$item_output .= '<div class="ref-icon">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon.png" alt="" class="active">';
				$item_output .= '<img src="' . get_template_directory_uri() . '/assets/img/menu-ref-icon_violet.png" alt="">';
				$item_output .= '</div>';
			}
			
			$item_output .= '</a>';
			$item_output .= $args_after;
			
			$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
			
			// РЈРІРµР»РёС‡РёРІР°РµРј СЃС‡РµС‚С‡РёРє РїРѕСЃР»Рµ РѕР±СЂР°Р±РѕС‚РєРё СЌР»РµРјРµРЅС‚Р°
			$this->item_counter++;
		}
		
		// РЎР±СЂР°СЃС‹РІР°РµРј СЃС‡РµС‚С‡РёРє РїСЂРё РЅР°С‡Р°Р»Рµ РЅРѕРІРѕРіРѕ СѓСЂРѕРІРЅСЏ РјРµРЅСЋ
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::start_lvl($output, $depth, $args);
		}
		
		// РЎР±СЂР°СЃС‹РІР°РµРј СЃС‡РµС‚С‡РёРє РїСЂРё Р·Р°РІРµСЂС€РµРЅРёРё СѓСЂРѕРІРЅСЏ РјРµРЅСЋ
		function end_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_counter = 0;
			parent::end_lvl($output, $depth, $args);
		}
	}
	
	class Mobile_Menu_Walker extends Walker_Nav_Menu {
		private $item_count = 0;
		
		function start_lvl(&$output, $depth = 0, $args = array()) {
			$this->item_count = 0; // РЎР±СЂР°СЃС‹РІР°РµРј СЃС‡РµС‚С‡РёРє РґР»СЏ РЅРѕРІС‹С… СѓСЂРѕРІРЅРµР№
		}
		
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			$this->item_count++;
			
			// Р”РѕР±Р°РІР»СЏРµРј РєР»Р°СЃСЃС‹
			$class_names = 'mobile-menu-main-item';
			
			// Р”РѕР±Р°РІР»СЏРµРј special РєР»Р°СЃСЃ РґР»СЏ РїРµСЂРІРѕРіРѕ РїСѓРЅРєС‚Р°
			if ($this->item_count === 1) {
				$class_names .= ' mobile-menu-main-item_sw';
			}
			
			$output .= '<li class="' . $class_names . '">';
			
			// Р”Р»СЏ РїРµСЂРІРѕРіРѕ РїСѓРЅРєС‚Р° РЅРµ РґРѕР±Р°РІР»СЏРµРј СЃСЃС‹Р»РєСѓ, РґР»СЏ РѕСЃС‚Р°Р»СЊРЅС‹С… - РґРѕР±Р°РІР»СЏРµРј
			if ($this->item_count !== 1) {
				$attributes = !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
				$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
				$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
				
				$output .= '<a' . $attributes . '></a>';
			}
			
			// Р”РѕР±Р°РІР»СЏРµРј span СЃ С‚РµРєСЃС‚РѕРј
			$output .= '<span>' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
		}
		
		function end_el(&$output, $item, $depth = 0, $args = array()) {
			$output .= '</li>';
		}
	}
	
	
	// РљР°СЃС‚РѕРјРЅС‹Р№ walker
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
	
	// РћР±СЂР°Р±РѕС‚РєР° AJAX С„РѕСЂРјС‹ FAQ
	add_action('wp_ajax_faq_contact_form', 'handle_faq_contact_form');
	add_action('wp_ajax_nopriv_faq_contact_form', 'handle_faq_contact_form');
	
	function handle_faq_contact_form() {
		// РџСЂРѕРІРµСЂРєР° nonce
		if (!wp_verify_nonce($_POST['faq_nonce'], 'faq_contact_nonce')) {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё'));
			exit;
		}
		
		// Р’Р°Р»РёРґР°С†РёСЏ РґР°РЅРЅС‹С…
		$name = sanitize_text_field($_POST['name']);
		$email = sanitize_email($_POST['email']);
		$message = sanitize_textarea_field($_POST['message']);
		
		// РџСЂРѕРІРµСЂРєР° РѕР±СЏР·Р°С‚РµР»СЊРЅС‹С… РїРѕР»РµР№
		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'РџРѕР¶Р°Р»СѓР№СЃС‚Р°, Р·Р°РїРѕР»РЅРёС‚Рµ РІСЃРµ РїРѕР»СЏ'));
			exit;
		}
		
		// РџСЂРѕРІРµСЂРєР° email
		if (!is_email($email)) {
			wp_send_json_error(array('message' => 'РџРѕР¶Р°Р»СѓР№СЃС‚Р°, РІРІРµРґРёС‚Рµ РєРѕСЂСЂРµРєС‚РЅС‹Р№ email'));
			exit;
		}
		
		// РћС‚РїСЂР°РІРєР° email Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ
		$to = get_option('admin_email');
		$subject = 'РќРѕРІС‹Р№ РІРѕРїСЂРѕСЃ РёР· СЂР°Р·РґРµР»Р° FAQ: ' . $name;
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$body = "
        <h3>РќРѕРІС‹Р№ РІРѕРїСЂРѕСЃ РёР· СЂР°Р·РґРµР»Р° FAQ</h3>
        <p><strong>РРјСЏ:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Р’РѕРїСЂРѕСЃ:</strong></p>
        <p>" . nl2br($message) . "</p>
        <hr>
        <p><small>РЎРѕРѕР±С‰РµРЅРёРµ РѕС‚РїСЂР°РІР»РµРЅРѕ СЃ СЃР°Р№С‚Р° " . get_bloginfo('name') . "</small></p>
		";
		
		// РћС‚РїСЂР°РІРєР° email
		$email_sent = wp_mail($to, $subject, $body, $headers);
		
		if ($email_sent) {
			// РЎРѕС…СЂР°РЅРµРЅРёРµ РІ Р±Р°Р·Сѓ РґР°РЅРЅС‹С…
			$post_id = wp_insert_post(array(
            'post_title' => 'Р’РѕРїСЂРѕСЃ РѕС‚ ' . $name,
            'post_content' => $message,
            'post_type' => 'faq_question',
            'post_status' => 'private',
            'meta_input' => array(
			'contact_email' => $email,
			'contact_date' => current_time('mysql')
            )
			));
			
			wp_send_json_success(array(
            'message' => get_field('faq_form_success_message', 'option') ?: 'Р’Р°С€ РІРѕРїСЂРѕСЃ РѕС‚РїСЂР°РІР»РµРЅ! РњС‹ РѕС‚РІРµС‚РёРј РІР°Рј РІ Р±Р»РёР¶Р°Р№С€РµРµ РІСЂРµРјСЏ.'
			));
			} else {
			wp_send_json_error(array('message' => 'РћС€РёР±РєР° РїСЂРё РѕС‚РїСЂР°РІРєРµ СЃРѕРѕР±С‰РµРЅРёСЏ'));
		}
		
		exit;
	}
	
	// РЎРѕР·РґР°РЅРёРµ Custom Post Type РґР»СЏ РІРѕРїСЂРѕСЃРѕРІ
	function register_faq_question_cpt() {
		register_post_type('faq_question', array(
        'labels' => array(
		'name' => 'Вопросы FAQ',
		'singular_name' => 'Вопрос',
		'menu_name' => 'Вопросы FAQ',
		'add_new' => 'Добавить вопрос',
		'add_new_item' => 'Добавить новый вопрос',
		'edit_item' => 'Редактировать вопрос',
		'new_item' => 'Новый вопрос',
		'view_item' => 'Просмотреть вопрос',
		'search_items' => 'Поиск вопросов',
		'not_found' => 'Вопросы не найдены',
		'not_found_in_trash' => 'Вопросы в корзине не найдены'
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
	
	// Р”РѕР±Р°РІР»РµРЅРёРµ РјРµС‚Р°РїРѕР»РµР№ РґР»СЏ РІРѕРїСЂРѕСЃРѕРІ
	function add_faq_question_meta() {
		add_meta_box(
        'faq_question_meta',
        'РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РІРѕРїСЂРѕСЃРµ',
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
    <p><strong>Р”Р°С‚Р°:</strong> <?php echo esc_html($date); ?></p>
    <?php
	}
	
	// РЎРѕС…СЂР°РЅРµРЅРёРµ РјРµС‚Р°РїРѕР»РµР№
	function save_faq_question_meta($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!current_user_can('edit_post', $post_id)) return;
		
		if (isset($_POST['contact_email'])) {
			update_post_meta($post_id, 'contact_email', sanitize_email($_POST['contact_email']));
		}
	}
	add_action('save_post_faq_question', 'save_faq_question_meta');
	
	// === РЎР»РѕР¶РЅРѕСЃС‚СЊ ===
	/* register_taxonomy('practice-difficulty', ['practice'], [
		'label' => 'РЎР»РѕР¶РЅРѕСЃС‚СЊ',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Р’Р°Р¶РЅРѕ РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РІ РЅРѕРІРѕРј СЂРµРґР°РєС‚РѕСЂРµ
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // РџРѕРєР°Р·С‹РІР°С‚СЊ РєРѕР»РѕРЅРєСѓ РІ СЃРїРёСЃРєРµ Р·Р°РїРёСЃРµР№
		]);
		
		// === РџСЂРѕРґРѕР»Р¶РёС‚РµР»СЊРЅРѕСЃС‚СЊ ===
		register_taxonomy('practice-duration', ['practice'], [
		'label' => 'РџСЂРѕРґРѕР»Р¶РёС‚РµР»СЊРЅРѕСЃС‚СЊ',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Р’Р°Р¶РЅРѕ РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РІ РЅРѕРІРѕРј СЂРµРґР°РєС‚РѕСЂРµ
		'rewrite' => ['slug' => 'duration'],
		'show_admin_column' => true, // РџРѕРєР°Р·С‹РІР°С‚СЊ РєРѕР»РѕРЅРєСѓ РІ СЃРїРёСЃРєРµ Р·Р°РїРёСЃРµР№
		]);
		
		// === Р¦РµР»СЊ ===
		register_taxonomy('practice-goal', ['practice'], [
		'label' => 'Р¦РµР»Рё',
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true, // Р’Р°Р¶РЅРѕ РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РІ РЅРѕРІРѕРј СЂРµРґР°РєС‚РѕСЂРµ
		'rewrite' => ['slug' => 'goal'],
		'show_admin_column' => true, // РџРѕРєР°Р·С‹РІР°С‚СЊ РєРѕР»РѕРЅРєСѓ РІ СЃРїРёСЃРєРµ Р·Р°РїРёСЃРµР№
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
			echo '<p>РќРµС‚ РїСЂР°РєС‚РёРє РїРѕ РІС‹Р±СЂР°РЅРЅС‹Рј С„РёР»СЊС‚СЂР°Рј.</p>';
		}
		
		wp_die();
	}
	
	// AJAX РѕР±СЂР°Р±РѕС‚С‡РёРє РґР»СЏ С„РёР»СЊС‚СЂР°С†РёРё РїСЂР°РєС‚РёРє
	add_action('wp_ajax_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	add_action('wp_ajax_nopriv_filter_practices_kriyi', 'filter_practices_callback_kriyi');
	
	function filter_practices_callback_kriyi() {
		// РџСЂРѕРІРµСЂСЏРµРј nonce РґР»СЏ Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё
		check_ajax_referer('practice_filter_nonce', 'nonce');
		
		// РџР°СЂР°РјРµС‚СЂС‹ РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => -1,
        'post_status' => 'publish'
		);
		
		// Р¤РёР»СЊС‚СЂ РїРѕ РєР°С‚РµРіРѕСЂРёРё (term_id)
		if (!empty($_POST['term_id'])) {
			$args['tax_query'] = array(
            array(
			'taxonomy' => 'practice-type',
			'field' => 'term_id',
			'terms' => intval($_POST['term_id'])
            )
			);
		}
		
		// РџРѕРёСЃРє РїРѕ РЅР°Р·РІР°РЅРёСЋ Рё РѕРїРёСЃР°РЅРёСЋ
		if (!empty($_POST['search'])) {
			$args['s'] = sanitize_text_field($_POST['search']);
		}
		
		// Р¤РёР»СЊС‚СЂС‹ РїРѕ С‚Р°РєСЃРѕРЅРѕРјРёСЏРј
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
		
		// РЎРѕСЂС‚РёСЂРѕРІРєР°
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
		
		// Р¤РѕСЂРјРёСЂСѓРµРј HTML РѕС‚РІРµС‚
		ob_start();
		
		if ($query->have_posts()) :
        $item_count = 0;
        while ($query->have_posts()) : $query->the_post();
		$item_count++;
		$practice_level = get_field('level') ?: 'РќР°С‡РёРЅР°СЋС‰РёР№';
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
        
        // Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ СЌР»РµРјРµРЅС‚С‹ РµСЃР»Рё СЂРµР·СѓР»СЊС‚Р°С‚РѕРІ Р±РѕР»СЊС€Рµ 10
        if ($count > 10) :
		for ($i = 0; $i < 2; $i++) :
	?>
	<div class="kriyi-item kriyi-item_last hidden">
		<div class="kriyi-item__inner">
			<a href="#"></a>
			<span class="kriya-level">РќР°С‡РёРЅР°СЋС‰РёР№</span>
			<div class="kriya-info">
				<h3>РћСЃС‚Р°Р»СЊРЅС‹Рµ РєСЂРёР№Рё</h3>
				<p>РџРѕРєР°Р·Р°С‚СЊ РІСЃРµ РґРѕСЃС‚СѓРїРЅС‹Рµ РїСЂР°РєС‚РёРєРё</p>
			</div>
			<div class="kriya-media">
				<div class="kriya-img">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/img/kriya-img_01.png" alt="РћСЃС‚Р°Р»СЊРЅС‹Рµ РєСЂРёР№Рё">
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
        echo '<p class="no-practices">РџРѕ РІР°С€РµРјСѓ Р·Р°РїСЂРѕСЃСѓ РЅРёС‡РµРіРѕ РЅРµ РЅР°Р№РґРµРЅРѕ.</p>';
		endif;
		
		$html = ob_get_clean();
		
		// Р’РѕР·РІСЂР°С‰Р°РµРј HTML Рё РєРѕР»РёС‡РµСЃС‚РІРѕ СЂРµР·СѓР»СЊС‚Р°С‚РѕРІ
		wp_send_json_success(array(
        'html' => $html,
        'count' => $count
		));
		
		wp_die();
	}
	
	// РћС‡РёСЃС‚РєР° РєРѕСЂР·РёРЅС‹ Рё РґРѕР±Р°РІР»РµРЅРёРµ С‚РѕРІР°СЂР° СЃ СЂРµРґРёСЂРµРєС‚РѕРј
	add_action('template_redirect', 'handle_tariff_add_to_cart');
	function handle_tariff_add_to_cart() {
	if (!function_exists('WC') || !function_exists('wc_get_checkout_url')) {
		return;
	}

		// РџСЂРѕРІРµСЂСЏРµРј, РґРѕР±Р°РІР»СЏРµС‚СЃСЏ Р»Рё С‚РѕРІР°СЂ РєР°С‚РµРіРѕСЂРёРё tariffs
		if (isset($_POST['add-to-cart']) && is_numeric($_POST['add-to-cart']) && isset($_POST['woocommerce-add-to-cart-nonce'])) {
			
			// РџСЂРѕРІРµСЂСЏРµРј nonce
			if (!wp_verify_nonce($_POST['woocommerce-add-to-cart-nonce'], 'woocommerce-add-to-cart')) {
				return;
			}
			
			$product_id = intval($_POST['add-to-cart']);
			
			if (has_term('tariffs', 'product_cat', $product_id)) {
				// РћС‡РёС‰Р°РµРј РєРѕСЂР·РёРЅСѓ
				WC()->cart->empty_cart();
				
				// Р”РѕР±Р°РІР»СЏРµРј С‚РѕРІР°СЂ
				$added = WC()->cart->add_to_cart($product_id);
				
				if ($added) {
					// Р РµРґРёСЂРµРєС‚ РЅР° checkout
					wp_redirect(wc_get_checkout_url());
					exit;
				}
			}
		}
	}
	
	// РћС‚РєР»СЋС‡Р°РµРј СЃС‚Р°РЅРґР°СЂС‚РЅСѓСЋ РѕР±СЂР°Р±РѕС‚РєСѓ WooCommerce РґР»СЏ С‚Р°СЂРёС„РѕРІ
	add_filter('woocommerce_add_to_cart_redirect', 'disable_standard_redirect_for_tariffs', 10, 1);
	function disable_standard_redirect_for_tariffs($url) {
		if (isset($_POST['add-to-cart']) && is_numeric($_POST['add-to-cart'])) {
			$product_id = intval($_POST['add-to-cart']);
			if (has_term('tariffs', 'product_cat', $product_id)) {
				return ''; // РћС‚РєР»СЋС‡Р°РµРј СЃС‚Р°РЅРґР°СЂС‚РЅС‹Р№ СЂРµРґРёСЂРµРєС‚
			}
		}
		return $url;
	}
	
	
	// РџРѕРґРєР»СЋС‡Р°РµРј СЃРєСЂРёРїС‚С‹ Рё СЃС‚РёР»Рё WooCommerce
	function theme_woocommerce_support() {
		add_theme_support('woocommerce');
		
		// РџРѕРґРєР»СЋС‡Р°РµРј СЃРєСЂРёРїС‚С‹ РґР»СЏ checkout
	}
	add_action('after_setup_theme', 'theme_woocommerce_support');
	
	// РЈР±РµРґРёРјСЃСЏ, С‡С‚Рѕ РІСЃРµ РЅРµРѕР±С…РѕРґРёРјС‹Рµ СЃРєСЂРёРїС‚С‹ Р·Р°РіСЂСѓР¶РµРЅС‹
	function theme_enqueue_checkout_scripts() {
		if (function_exists('is_checkout') && is_checkout()) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('wc-checkout');
			wp_enqueue_script('wc-country-select');
			wp_enqueue_script('wc-address-i18n');
		}
	}
	add_action('wp_enqueue_scripts', 'theme_enqueue_checkout_scripts');
	
	// РџСЂРѕРІРµСЂСЏРµРј Рё РёСЃРїСЂР°РІР»СЏРµРј РІРѕР·РјРѕР¶РЅС‹Рµ РїСЂРѕР±Р»РµРјС‹ СЃ checkout
	add_action('template_redirect', 'fix_checkout_issues');
	function fix_checkout_issues() {
	if (!function_exists('is_checkout') || !function_exists('WC')) {
		return;
	}

		if (is_checkout() && WC()->cart && !WC()->cart->is_empty()) {
			// РЈР±РµРґРёРјСЃСЏ, С‡С‚Рѕ СЃРµСЃСЃРёСЏ WooCommerce Р°РєС‚РёРІРЅР°
			if (WC()->session && !WC()->session->has_session()) {
				WC()->session->set_customer_session_cookie(true);
			}
		}
	}
	
	/* Axecode.tech: Р­С‚Р°Рї 2 СЃС‚Р°Р±РёР»РёР·Р°С†РёРё - СЃРѕС…СЂР°РЅРµРЅ СЃС‚Р°РЅРґР°СЂС‚РЅС‹Р№ РїРѕС‚РѕРє WooCommerce checkout, РѕС‚Р»Р°РґРѕС‡РЅС‹Рµ/override-РѕР±СЂР°Р±РѕС‚С‡РёРєРё СѓРґР°Р»РµРЅС‹. */
	
	
	// Р”РѕР±Р°РІР»СЏРµРј РІРѕР·РјРѕР¶РЅРѕСЃС‚Рё РґР»СЏ РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№
	function add_custom_capabilities() {
		$subscriber = get_role('subscriber');
		$subscriber->add_cap('read_private_practices');
		$subscriber->add_cap('edit_user_profile');
	}
	add_action('init', 'add_custom_capabilities');
	
	// РћР±СЂР°Р±РѕС‚РєР° РѕР±РЅРѕРІР»РµРЅРёСЏ РїСЂРѕС„РёР»СЏ
	/* function update_user_profile() {
		if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
        wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
		}
		
		if (!is_user_logged_in()) {
        wp_die('Р’С‹ РЅРµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅС‹');
		}
		
		$user_id = get_current_user_id();
		$user_data = array('ID' => $user_id);
		
		// РћР±РЅРѕРІР»РµРЅРёРµ РѕСЃРЅРѕРІРЅС‹С… РґР°РЅРЅС‹С…
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
		
		// РћР±РЅРѕРІР»РµРЅРёРµ РјРµС‚Р°РїРѕР»РµР№
		if (!empty($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
		}
		
		if (!empty($_POST['birthdate'])) {
        update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
		}
		
		if (!empty($_POST['gender'])) {
        update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
		}
		
		// РћР±СЂР°Р±РѕС‚РєР° СЃРјРµРЅС‹ РїР°СЂРѕР»СЏ
		if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
        if ($_POST['new_password'] === $_POST['repeat_password']) {
		$user = get_user_by('id', $user_id);
		
		if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
		wp_set_password($_POST['new_password'], $user_id);
		}
        }
		}
		
		// РћР±СЂР°Р±РѕС‚РєР° Р·Р°РіСЂСѓР·РєРё Р°РІР°С‚Р°СЂР°
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
	// AJAX РѕР±СЂР°Р±РѕС‚С‡РёРє РґР»СЏ РѕР±РЅРѕРІР»РµРЅРёСЏ РїСЂРѕС„РёР»СЏ
	function yoga_update_profile_ajax() {
		// Р›РѕРіРёСЂСѓРµРј Р·Р°РїСЂРѕСЃ РґР»СЏ РѕС‚Р»Р°РґРєРё
		error_log('AJAX update_profile called');
		error_log('POST data: ' . print_r($_POST, true));
		error_log('FILES data: ' . print_r($_FILES, true));
		
		// РџСЂРѕРІРµСЂСЏРµРј nonce
		if (!isset($_POST['nonce'])) {
			error_log('Nonce not set');
			wp_send_json_error('Nonce РЅРµ СѓСЃС‚Р°РЅРѕРІР»РµРЅ', 400);
		}
		
		if (!wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			error_log('Nonce verification failed');
			wp_send_json_error('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё: РЅРµРІРµСЂРЅС‹Р№ nonce', 403);
		}
		
		if (!is_user_logged_in()) {
			error_log('User not logged in');
			wp_send_json_error('Р’С‹ РЅРµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅС‹', 401);
		}
		
		$user_id = get_current_user_id();
		$response = array();
		
		try {
			$user_data = array('ID' => $user_id);
			
			// РћР±РЅРѕРІР»РµРЅРёРµ РѕСЃРЅРѕРІРЅС‹С… РґР°РЅРЅС‹С…
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
			
			// РћР±РЅРѕРІР»РµРЅРёРµ РјРµС‚Р°РїРѕР»РµР№
			if (!empty($_POST['phone'])) {
				update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
			}
			
			if (!empty($_POST['birthdate'])) {
				update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
			}
			
			if (!empty($_POST['gender'])) {
				update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
			}
			
			// РћР±СЂР°Р±РѕС‚РєР° СЃРјРµРЅС‹ РїР°СЂРѕР»СЏ
			if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['repeat_password'])) {
				if ($_POST['new_password'] === $_POST['repeat_password']) {
					$user = get_user_by('id', $user_id);
					
					if (wp_check_password($_POST['current_password'], $user->user_pass, $user_id)) {
						wp_set_password($_POST['new_password'], $user_id);
					}
				}
			}
			
			// РћР±СЂР°Р±РѕС‚РєР° Р·Р°РіСЂСѓР·РєРё Р°РІР°С‚Р°СЂР°
			// РћР±СЂР°Р±РѕС‚РєР° Р·Р°РіСЂСѓР·РєРё Р°РІР°С‚Р°СЂР°
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
						// РћР±РЅРѕРІР»СЏРµРј РїРѕР»Рµ ACF РґР»СЏ С‚РµРєСѓС‰РµРіРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
						$result = update_field('user_avatar', $attachment_id, 'user_' . $user_id);
						
						if ($result) {
							wp_send_json_success('РђРІР°С‚Р°СЂ СѓСЃРїРµС€РЅРѕ РѕР±РЅРѕРІР»РµРЅ С‡РµСЂРµР· ACF');
							} else {
							wp_send_json_error('РћС€РёР±РєР° РїСЂРё РѕР±РЅРѕРІР»РµРЅРёРё РїРѕР»СЏ ACF');
						}
						} else {
						wp_send_json_error("Р¤Р°Р№Р» РЅРµ СЏРІР»СЏРµС‚СЃСЏ РёР·РѕР±СЂР°Р¶РµРЅРёРµРј: $mime_type");
					}
				}
			}
			
			wp_send_json_success($result);
			
			} catch (Exception $e) {
			error_log('Exception in update_profile: ' . $e->getMessage());
			wp_send_json_error('Р’РЅСѓС‚СЂРµРЅРЅСЏСЏ РѕС€РёР±РєР° СЃРµСЂРІРµСЂР°: ' . $e->getMessage(), 500);
		}
	}
	add_action('wp_ajax_update_user_profile', 'yoga_update_profile_ajax');
	// РћР±СЂР°Р±РѕС‚С‡РёРє СѓРґР°Р»РµРЅРёСЏ Р°РІР°С‚Р°СЂР°
	function delete_avatar_ajax() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yoga_ajax_nonce')) {
			wp_send_json_error('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
		}
		
		if (!is_user_logged_in()) {
			wp_send_json_error('РќРµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅ');
		}
		
		$user_id = get_current_user_id();
		delete_user_meta($user_id, 'simple_local_avatar');
		
		wp_send_json_success('РђРІР°С‚Р°СЂ СѓРґР°Р»РµРЅ');
	}
	add_action('wp_ajax_delete_avatar', 'delete_avatar_ajax');
	
	// РЁРѕСЂС‚РєРѕРґ РґР»СЏ РёСЃС‚РѕСЂРёРё РїСЂР°РєС‚РёРє
	function practice_history_shortcode() {
		if (!is_user_logged_in()) return '';
		
		$user_id = get_current_user_id();
		$completed_practices = get_user_meta($user_id, 'completed_practices', true);
		
		if (empty($completed_practices)) {
			return '<p>Р’С‹ РµС‰Рµ РЅРµ Р·Р°РІРµСЂС€РёР»Рё РЅРё РѕРґРЅРѕР№ РїСЂР°РєС‚РёРєРё.</p>';
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
							$level_name = !empty($level) ? $level[0]->name : 'РќРµ СѓРєР°Р·Р°РЅ';
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
	
	// РЁРѕСЂС‚РєРѕРґ РґР»СЏ РёСЃС‚РѕСЂРёРё Р·Р°РєР°Р·РѕРІ Рё РїРѕРґРїРёСЃРѕРє
	function subscription_settings_shortcode() {
		if (!is_user_logged_in()) return '';
		if (!function_exists('wc_get_orders')) return '';
		
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
            <h2>РќР°СЃС‚СЂРѕР№РєРё РїРѕРґРїРёСЃРєРё</h2>
            <div class="lk-settings-part">
                <div class="lk-settings-item lk-settings-item_main">
                    <div class="lk-settings-item__col">
                        <p class="lk-settings-item__col-text">Р’Р°С€ С‚Р°СЂРёС„:</p>
                        <div class="personal-status">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/personal-status-icon_settings.png" alt="" class="personal-status__img">
                            <span>
                                <?php 
									/* AxeCode.tech: Р±РµР·РѕРїР°СЃРЅС‹Р№ РІС‹Р·РѕРІ РґР»СЏ РѕРєСЂСѓР¶РµРЅРёР№ Р±РµР· WooCommerce Subscriptions. */
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
										echo 'РќРµ Р°РєС‚РёРІРµРЅ';
									}
								?>
							</span>
						</div>
					</div>
                    <div class="lk-settings-item__col">
                        <p class="lk-settings-item__col-text">Р”РµР№СЃС‚РІСѓРµС‚ РґРѕ:</p>
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
									echo 'вЂ”';
								}
							?>
						</time>
					</div>
				</div>
			</div>
            
            <div class="lk-settings-part">
                <h4>РСЃС‚РѕСЂРёСЏ РїРѕРєСѓРїРѕРє</h4>
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
						echo '<p>РЈ РІР°СЃ РїРѕРєР° РЅРµС‚ Р·Р°РІРµСЂС€РµРЅРЅС‹С… Р·Р°РєР°Р·РѕРІ.</p>';
					}
				?>
			</div>
		</div>
	</div>
    <?php
		return ob_get_clean();
	}
	add_shortcode('subscription_settings', 'subscription_settings_shortcode');
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ СЂРµРєРѕРјРµРЅРґРѕРІР°РЅРЅС‹С… РїСЂР°РєС‚РёРє
	function get_recommended_practices($user_id) {
		$completed_practices = get_user_meta($user_id, 'completed_practices', true) ?: array();
		$favorite_practices = get_user_meta($user_id, 'favorite_practices', true) ?: array();
		
		// Р•СЃР»Рё РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРѕРІС‹Р№, РїРѕРєР°Р·С‹РІР°РµРј РїРѕРїСѓР»СЏСЂРЅС‹Рµ РїСЂР°РєС‚РёРєРё
		if (empty($completed_practices) && empty($favorite_practices)) {
			return get_popular_practices();
		}
		
		// РџРѕР»СѓС‡Р°РµРј РїСЂР°РєС‚РёРєРё РЅР° РѕСЃРЅРѕРІРµ РїСЂРµРґРїРѕС‡С‚РµРЅРёР№ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
		$recommended = array();
		
		// 1. РџРѕ СѓСЂРѕРІРЅСЋ СЃР»РѕР¶РЅРѕСЃС‚Рё (РЅР° РѕСЃРЅРѕРІРµ Р·Р°РІРµСЂС€РµРЅРЅС‹С… РїСЂР°РєС‚РёРє)
		$user_levels = get_user_practice_levels($user_id);
		if (!empty($user_levels)) {
			$level_practices = get_practices_by_levels($user_levels, 6);
			$recommended = array_merge($recommended, $level_practices);
		}
		
		// 2. РџРѕС…РѕР¶РёРµ РЅР° РёР·Р±СЂР°РЅРЅС‹Рµ
		if (!empty($favorite_practices)) {
			$similar_practices = get_similar_practices($favorite_practices, 4);
			$recommended = array_merge($recommended, $similar_practices);
		}
		
		// 3. РќРѕРІС‹Рµ РїСЂР°РєС‚РёРєРё, РєРѕС‚РѕСЂС‹Рµ РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ РµС‰Рµ РЅРµ РїСЂРѕС…РѕРґРёР»
		$new_practices = get_new_practices($user_id, 3);
		$recommended = array_merge($recommended, $new_practices);
		
		// РЈР±РёСЂР°РµРј РґСѓР±Р»РёРєР°С‚С‹ Рё СѓР¶Рµ Р·Р°РІРµСЂС€РµРЅРЅС‹Рµ РїСЂР°РєС‚РёРєРё
		$recommended = array_unique($recommended);
		$recommended = array_diff($recommended, $completed_practices);
		
		// РћРіСЂР°РЅРёС‡РёРІР°РµРј РєРѕР»РёС‡РµСЃС‚РІРѕ СЂРµРєРѕРјРµРЅРґР°С†РёР№
		return array_slice($recommended, 0, 12);
	}
	
	// Р’СЃРїРѕРјРѕРіР°С‚РµР»СЊРЅС‹Рµ С„СѓРЅРєС†РёРё
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
		// РњРѕР¶РЅРѕ СЂРµР°Р»РёР·РѕРІР°С‚СЊ СЃРёСЃС‚РµРјСѓ РїРѕРґСЃС‡РµС‚Р° РїРѕРїСѓР»СЏСЂРЅРѕСЃС‚Рё РЅР° РѕСЃРЅРѕРІРµ РїСЂРѕСЃРјРѕС‚СЂРѕРІ
		// РџРѕРєР° РїСЂРѕСЃС‚Рѕ РІРѕР·РІСЂР°С‰Р°РµРј РїРѕСЃР»РµРґРЅРёРµ РїСЂР°РєС‚РёРєРё
		$args = array(
        'post_type' => 'practice',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids'
		);
		
		return get_posts($args);
	}
	
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ РІРѕРїСЂРѕСЃРѕРІ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
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
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РІРѕРїСЂРѕСЃР°
	function display_question_item($question, $hidden = false) {
		$question_id = $question->ID;
		$answer = get_post_meta($question_id, '_answer', true);
		$answer_date = get_post_meta($question_id, '_answer_date', true);
		$admin_id = get_post_meta($question_id, '_answer_admin', true);
		$admin_name = $admin_id ? get_the_author_meta('display_name', $admin_id) : 'РђРґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂ';
		
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
            <span class="lk-question__status">РћР¶РёРґР°РµС‚ РѕС‚РІРµС‚Р°</span>
            <?php endif; ?>
		</div>
        
        <?php if ($answer): ?>
        <div class="lk-question lk-question_answer">
            <div class="lk-question__time">
                <b>РћС‚РІРµС‚ <?php echo esc_html($admin_name); ?></b>
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
	
	// РћР±СЂР°Р±РѕС‚С‡РёРє РѕС‚РїСЂР°РІРєРё РІРѕРїСЂРѕСЃР°
	function handle_question_submission() {
		if (!isset($_POST['question_nonce']) || !wp_verify_nonce($_POST['question_nonce'], 'submit_question')) {
			wp_die('РћС€РёР±РєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё');
		}
		
		if (!is_user_logged_in()) {
			wp_die('Р’С‹ РЅРµ Р°РІС‚РѕСЂРёР·РѕРІР°РЅС‹');
		}
		
		$question_text = sanitize_textarea_field($_POST['question_text']);
		
		if (empty($question_text)) {
			wp_die('Р’РѕРїСЂРѕСЃ РЅРµ РјРѕР¶РµС‚ Р±С‹С‚СЊ РїСѓСЃС‚С‹Рј');
		}
		
		$user_id = get_current_user_id();
		
		// РЎРѕР·РґР°РµРј РїРѕСЃС‚ РІРѕРїСЂРѕСЃР°
		$question_data = array(
        'post_title' => 'Р’РѕРїСЂРѕСЃ РѕС‚ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ ' . $user_id,
        'post_content' => $question_text,
        'post_status' => 'publish',
        'post_type' => 'question',
        'post_author' => $user_id
		);
		
		$question_id = wp_insert_post($question_data);
		
		if (is_wp_error($question_id)) {
			wp_die('РћС€РёР±РєР° РїСЂРё СЃРѕС…СЂР°РЅРµРЅРёРё РІРѕРїСЂРѕСЃР°');
		}
		
		// РћС‚РїСЂР°РІР»СЏРµРј СѓРІРµРґРѕРјР»РµРЅРёРµ Р°РґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂСѓ
		$admin_email = get_option('admin_email');
		$user = get_userdata($user_id);
		$subject = 'РќРѕРІС‹Р№ РІРѕРїСЂРѕСЃ РІ Р»РёС‡РЅРѕРј РєР°Р±РёРЅРµС‚Рµ';
		$message = "РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ {$user->display_name} Р·Р°РґР°Р» РЅРѕРІС‹Р№ РІРѕРїСЂРѕСЃ:\n\n";
		$message .= $question_text . "\n\n";
		$message .= "РЎСЃС‹Р»РєР° РґР»СЏ РѕС‚РІРµС‚Р°: " . admin_url("post.php?post={$question_id}&action=edit");
		
		wp_mail($admin_email, $subject, $message);
		
		wp_redirect(add_query_arg('question_submitted', 'true', wp_get_referer()));
		exit;
	}
	add_action('admin_post_submit_question', 'handle_question_submission');
	add_action('admin_post_nopriv_submit_question', 'handle_question_submission');
	
	// Р РµРіРёСЃС‚СЂРёСЂСѓРµРј С‚РёРї Р·Р°РїРёСЃРё РґР»СЏ РІРѕРїСЂРѕСЃРѕРІ
	function register_question_post_type() {
		$args = array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => array('title', 'editor'),
        'labels' => array(
		'name' => 'Вопросы FAQ',
		'singular_name' => 'Вопрос',
		'menu_name' => 'Вопросы FAQ',
		'add_new' => 'Добавить вопрос',
		'add_new_item' => 'Добавить новый вопрос',
		'edit_item' => 'Редактировать вопрос',
		'new_item' => 'Новый вопрос',
		'view_item' => 'Просмотреть вопрос',
		'search_items' => 'Поиск вопросов',
		'not_found' => 'Вопросы не найдены',
		'not_found_in_trash' => 'Вопросы в корзине не найдены'
        )
		);
		
		register_post_type('question', $args);
	}
	add_action('init', 'register_question_post_type');
	
	// Р”РѕР±Р°РІР»СЏРµРј РјРµС‚Р°Р±РѕРєСЃ РґР»СЏ РѕС‚РІРµС‚Р° РЅР° РІРѕРїСЂРѕСЃ
	function add_question_answer_meta_box() {
		add_meta_box(
        'question_answer',
        'РћС‚РІРµС‚ РЅР° РІРѕРїСЂРѕСЃ',
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
        <label for="question_answer" style="display: block; margin-bottom: 5px; font-weight: bold;">РћС‚РІРµС‚:</label>
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
        РћС‚РІРµС‚ РґР°РЅ: <?php echo date('d.m.Y H:i', strtotime($answer_date)); ?> 
        РїРѕР»СЊР·РѕРІР°С‚РµР»РµРј: <?php echo $admin_id ? get_the_author_meta('display_name', $admin_id) : 'РќРµРёР·РІРµСЃС‚РЅРѕ'; ?>
	</div>
    <?php endif; ?>
    <?php
	}
	
	// РЎРѕС…СЂР°РЅРµРЅРёРµ РѕС‚РІРµС‚Р° РЅР° РІРѕРїСЂРѕСЃ
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
			
			// Р•СЃР»Рё РѕС‚РІРµС‚ РёР·РјРµРЅРёР»СЃСЏ РёР»Рё РґРѕР±Р°РІР»РµРЅ РЅРѕРІС‹Р№
			if ($answer !== $old_answer) {
				update_post_meta($post_id, '_answer_date', current_time('mysql'));
				update_post_meta($post_id, '_answer_admin', get_current_user_id());
				
				// РћС‚РїСЂР°РІР»СЏРµРј СѓРІРµРґРѕРјР»РµРЅРёРµ РїРѕР»СЊР·РѕРІР°С‚РµР»СЋ
				$question = get_post($post_id);
				$user = get_userdata($question->post_author);
				$subject = 'РћС‚РІРµС‚ РЅР° РІР°С€ РІРѕРїСЂРѕСЃ';
				$message = "Р—РґСЂР°РІСЃС‚РІСѓР№С‚Рµ, {$user->display_name}!\n\n";
				$message .= "РќР° РІР°С€ РІРѕРїСЂРѕСЃ РїРѕР»СѓС‡РµРЅ РѕС‚РІРµС‚:\n\n";
				$message .= "Р’РѕРїСЂРѕСЃ: {$question->post_content}\n\n";
				$message .= "РћС‚РІРµС‚: {$answer}\n\n";
				$message .= "РЎ СѓРІР°Р¶РµРЅРёРµРј, Р°РґРјРёРЅРёСЃС‚СЂР°С†РёСЏ СЃР°Р№С‚Р°";
				
				wp_mail($user->user_email, $subject, $message);
			}
		}
	}
	add_action('save_post_question', 'save_question_answer');
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ Р°РєС‚РёРІРЅРѕР№ РїРѕРґРїРёСЃРєРё РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
	function get_user_active_subscription() {
		if (!is_user_logged_in()) return false;
		
		$user_id = get_current_user_id();
		
		// Р•СЃР»Рё РёСЃРїРѕР»СЊР·СѓРµС‚Рµ WooCommerce Subscriptions
		/* AxeCode.tech: РїРµСЂРµРґ РІС‹Р·РѕРІРѕРј API РїРѕРґРїРёСЃРѕРє РїСЂРѕРІРµСЂСЏРµРј Рё РєР»Р°СЃСЃ, Рё С„СѓРЅРєС†РёСЋ-С…РµР»РїРµСЂ. */
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
		
		// РђР»СЊС‚РµСЂРЅР°С‚РёРІР°: РїСЂРѕРІРµСЂРєР° С‡РµСЂРµР· РјРµС‚Р°РїРѕР»СЏ
		$active_subscription = get_user_meta($user_id, 'active_subscription', true);
		if ($active_subscription && $active_subscription['end_date'] > current_time('mysql')) {
			return $active_subscription;
		}
		
		return false;
	}
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ РёСЃС‚РѕСЂРёРё Р·Р°РєР°Р·РѕРІ
	function get_user_orders_history() {
		if (!is_user_logged_in()) return array();
		if (!function_exists('wc_get_orders')) return array();
		
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
	
	// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ СЃРѕС…СЂР°РЅРµРЅРЅС‹С… РєР°СЂС‚
	function get_user_saved_cards() {
		if (!is_user_logged_in()) return array();
		
		$user_id = get_current_user_id();
		$saved_cards = get_user_meta($user_id, 'saved_payment_cards', true);
		
		return $saved_cards ?: array();
	}
	
	// РЁРѕСЂС‚РєРѕРґ РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ СѓРїСЂР°РІР»РµРЅРёСЏ РїРѕРґРїРёСЃРєРѕР№
	function subscription_management_shortcode() {
		ob_start();
	?>
    <div class="subscription-management">
        <h3>РЈРїСЂР°РІР»РµРЅРёРµ РїРѕРґРїРёСЃРєРѕР№</h3>
        <?php
			$subscription = get_user_active_subscription();
			if ($subscription) {
			?>
            <div class="subscription-info">
                <p><strong>РўРµРєСѓС‰РёР№ С‚Р°СЂРёС„:</strong> <?php echo $subscription['name']; ?></p>
                <p><strong>Р”РµР№СЃС‚РІСѓРµС‚ РґРѕ:</strong> <?php echo date('d.m.Y', strtotime($subscription['end_date'])); ?></p>
                <p><strong>РЎС‚Р°С‚СѓСЃ:</strong> <?php echo $subscription['status']; ?></p>
			</div>
            
            <div class="subscription-actions">
                <button class="btn btn-renew">РџСЂРѕРґР»РёС‚СЊ РїРѕРґРїРёСЃРєСѓ</button>
                <button class="btn btn-cancel">РћС‚РјРµРЅРёС‚СЊ РїРѕРґРїРёСЃРєСѓ</button>
			</div>
            <?php
				} else {
			?>
            <div class="no-subscription">
                <p>РЈ РІР°СЃ РЅРµС‚ Р°РєС‚РёРІРЅРѕР№ РїРѕРґРїРёСЃРєРё.</p>
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="btn">
                    Р’С‹Р±СЂР°С‚СЊ С‚Р°СЂРёС„
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
	
	// РћР±СЂР°Р±РѕС‚С‡РёРє РґР»СЏ РґРѕР±Р°РІР»РµРЅРёСЏ РєР°СЂС‚С‹
// Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕРґРєР»СЋС‡РµРЅРёСЏ СЂР°Р·РЅС‹С… header'РѕРІ
	function custom_get_header() {
		// РџСЂРѕРІРµСЂСЏРµРј, РёСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ Р»Рё С€Р°Р±Р»РѕРЅ "Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚"
		if (is_page_template('my-account')) {
			locate_template('header-lk.php', true);
			} else {
			locate_template('header.php', true);
		}
	}
	
	// РџРµСЂРµРѕРїСЂРµРґРµР»СЏРµРј СЃС‚Р°РЅРґР°СЂС‚РЅС‹Р№ get_header()
	remove_action('get_header', 'wp_get_header');
	add_action('get_header', 'custom_get_header');
	
	function reading_time() {
		$content = get_post_field('post_content', get_the_ID());
		$word_count = str_word_count(strip_tags($content));
		$reading_time = ceil($word_count / 200); // 200 СЃР»РѕРІ РІ РјРёРЅСѓС‚Сѓ
		
		return $reading_time;
	}
	
	
	// Р”РѕР±Р°РІР»РµРЅРёРµ AJAX РѕР±СЂР°Р±РѕС‚С‡РёРєРѕРІ
	// РћР±СЂР°Р±РѕС‚С‡РёРє РґР»СЏ РґРѕР±Р°РІР»РµРЅРёСЏ/СѓРґР°Р»РµРЅРёСЏ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ
// РџРѕР»СѓС‡РµРЅРёРµ РёРЅС„РѕСЂРјР°С†РёРё Рѕ С‚РµРєСѓС‰РµРј Р°РєС‚РёРІРЅРѕРј С‚Р°СЂРёС„Рµ
	function get_current_user_tariff($user_id = null) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		
		if (!$user_id) return false;
		if (!yoga_has_woocommerce() || !function_exists('wc_get_orders')) return false;
		
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
		/* AxeCode.tech: РґРѕР±Р°РІР»РµРЅ fallback-С…РµР»РїРµСЂ, С‚.Рє. С„СѓРЅРєС†РёСЏ РёСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ РІ СЂР°СЃС‡РµС‚Рµ С‚Р°СЂРёС„Р°. */
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











