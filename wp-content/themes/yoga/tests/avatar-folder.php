<?php

define('ABSPATH', __DIR__ . '/');

class WP_Error {
	private $code;
	private $data;

	public function __construct(string $code, $data = null) {
		$this->code = $code;
		$this->data = $data;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_data(string $code) {
		return $code === $this->code ? $this->data : null;
	}
}

function is_wp_error($value): bool {
	return $value instanceof WP_Error;
}

function taxonomy_exists(string $taxonomy): bool {
	return $taxonomy === 'media_folder' && $GLOBALS['avatar_folder_taxonomy_available'];
}

function term_exists($name, $taxonomy, $parent) {
	if ($name !== 'аватарки' || $taxonomy !== 'media_folder' || $parent !== 0) {
		throw new RuntimeException('Unexpected folder lookup.');
	}
	return $GLOBALS['avatar_folder_term'];
}

function wp_insert_term($name, $taxonomy, $args) {
	if ($name !== 'аватарки' || $taxonomy !== 'media_folder' || $args !== array('parent' => 0)) {
		throw new RuntimeException('Unexpected folder creation.');
	}
	$GLOBALS['avatar_folder_create_count']++;
	$result = $GLOBALS['avatar_folder_insert_result'];
	if (!is_wp_error($result)) {
		$GLOBALS['avatar_folder_term'] = $result;
	}
	return $result;
}

function update_term_meta($term_id, $key, $value): void {
	$GLOBALS['avatar_folder_meta'][] = array($term_id, $key, $value);
}

function wp_set_object_terms($attachment_id, $terms, $taxonomy, $append): void {
	$GLOBALS['avatar_folder_assignments'][] = array($attachment_id, $terms, $taxonomy, $append);
}

function avatar_folder_assert($condition, string $message): void {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}
}

require dirname(__DIR__) . '/inc/avatar-folders.php';

$GLOBALS['avatar_folder_taxonomy_available'] = false;
$GLOBALS['avatar_folder_term'] = false;
$GLOBALS['avatar_folder_insert_result'] = array('term_id' => 17);
$GLOBALS['avatar_folder_create_count'] = 0;
$GLOBALS['avatar_folder_meta'] = array();
$GLOBALS['avatar_folder_assignments'] = array();

yoga_assign_avatar_to_folder(10);
avatar_folder_assert($GLOBALS['avatar_folder_create_count'] === 0, 'uploads work without Folders media taxonomy');

$GLOBALS['avatar_folder_taxonomy_available'] = true;
yoga_assign_avatar_to_folder(10);
avatar_folder_assert($GLOBALS['avatar_folder_create_count'] === 1, 'the avatars folder is created once');
avatar_folder_assert($GLOBALS['avatar_folder_meta'] === array(array(17, 'wcp_custom_order', 0)), 'new folder is visible to Folders');
avatar_folder_assert($GLOBALS['avatar_folder_assignments'] === array(array(10, array(17), 'media_folder', false)), 'avatar is assigned to the folder');

yoga_assign_avatar_to_folder(11);
avatar_folder_assert($GLOBALS['avatar_folder_create_count'] === 1, 'existing folder is reused');
avatar_folder_assert($GLOBALS['avatar_folder_assignments'][1] === array(11, array(17), 'media_folder', false), 'another avatar uses the same folder');

$GLOBALS['avatar_folder_term'] = false;
$GLOBALS['avatar_folder_insert_result'] = new WP_Error('invalid_taxonomy');
yoga_assign_avatar_to_folder(12);
avatar_folder_assert(count($GLOBALS['avatar_folder_assignments']) === 2, 'folder errors do not reassign the attachment');

fwrite(STDOUT, "Avatar folder tests passed.\n");
