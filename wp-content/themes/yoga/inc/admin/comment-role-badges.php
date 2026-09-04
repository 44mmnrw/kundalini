<?php
/**
 * Members integration for comment role badge colors.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_add_comment_role_badge_color_meta_box(string $screen_id): void {
	if (!current_user_can('edit_roles') && !current_user_can('create_roles')) {
		return;
	}

	add_meta_box(
		'yoga-comment-role-badge-color',
		__('Цвет бейджа в комментариях', 'yoga'),
		'yoga_render_comment_role_badge_color_meta_box',
		$screen_id,
		'side',
		'default'
	);
}
add_action('members_add_role_meta_boxes', 'yoga_add_comment_role_badge_color_meta_box');

function yoga_render_comment_role_badge_color_meta_box($role): void {
	$role_slug = is_object($role) && isset($role->name) ? sanitize_key((string) $role->name) : '';
	$color = $role_slug !== '' ? yoga_get_comment_role_badge_color($role_slug) : '#9153e1';
	?>
	<p>
		<label for="yoga-comment-role-badge-color-field"><?php esc_html_e('Цвет фона', 'yoga'); ?></label>
	</p>
	<input
		type="text"
		id="yoga-comment-role-badge-color-field"
		name="yoga_comment_role_badge_color"
		value="<?php echo esc_attr($color); ?>"
		class="yoga-comment-role-badge-color-field"
		data-default-color="#9153e1"
	>
	<p class="description">
		<?php esc_html_e('Цвет применяется к роли во всех комментариях. Цвет текста подбирается автоматически.', 'yoga'); ?>
	</p>
	<?php
}

function yoga_save_comment_role_badge_color(string $role_slug): void {
	if (!current_user_can('edit_roles') && !current_user_can('create_roles')) {
		return;
	}

	$edit_nonce = isset($_POST['members_edit_role_nonce'])
		? sanitize_text_field(wp_unslash($_POST['members_edit_role_nonce']))
		: '';
	$new_nonce = isset($_POST['members_new_role_nonce'])
		? sanitize_text_field(wp_unslash($_POST['members_new_role_nonce']))
		: '';
	$nonce_is_valid = ($edit_nonce !== '' && wp_verify_nonce($edit_nonce, 'edit_role'))
		|| ($new_nonce !== '' && wp_verify_nonce($new_nonce, 'new_role'));
	if (!$nonce_is_valid) {
		return;
	}

	$role_slug = sanitize_key($role_slug);
	if ($role_slug === '') {
		return;
	}

	$raw_color = isset($_POST['yoga_comment_role_badge_color'])
		? trim((string) wp_unslash($_POST['yoga_comment_role_badge_color']))
		: '';
	$submitted_color = sanitize_hex_color($raw_color);
	if ($raw_color !== '' && $submitted_color === null) {
		return;
	}
	$colors = yoga_get_comment_role_badge_colors();
	if ($submitted_color === null || $submitted_color === '') {
		unset($colors[$role_slug]);
	} else {
		$colors[$role_slug] = $submitted_color;
	}
	update_option('yoga_comment_role_badge_colors', $colors, false);
}
add_action('members_role_updated', 'yoga_save_comment_role_badge_color');
add_action('members_role_added', 'yoga_save_comment_role_badge_color');

function yoga_enqueue_comment_role_badge_color_assets(): void {
	$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
	if (!in_array($page, array('roles', 'members'), true)) {
		return;
	}

	$script_path = get_template_directory() . '/assets/js/admin-comment-role-badge-color.js';
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script(
		'yoga-comment-role-badge-color',
		get_template_directory_uri() . '/assets/js/admin-comment-role-badge-color.js',
		array('jquery', 'wp-color-picker'),
		file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
		true
	);
}
add_action('admin_enqueue_scripts', 'yoga_enqueue_comment_role_badge_color_assets');
