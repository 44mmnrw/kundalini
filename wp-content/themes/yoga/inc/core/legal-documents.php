<?php

if (!defined('ABSPATH')) {
	exit;
}

function yoga_legal_document_types() {
	return array(
		'user_agreement' => 'Пользовательское соглашение',
		'public_offer' => 'Публичная оферта',
		'privacy_policy' => 'Политика конфиденциальности',
		'personal_data' => 'Согласие на обработку персональных данных',
		'contraindications' => 'Противопоказания и отказ от ответственности',
		'cookie_policy' => 'Политика использования cookie',
	);
}

function yoga_get_legal_document_page_id($type) {
	$type = sanitize_key((string) $type);
	if (!isset(yoga_legal_document_types()[$type])) {
		return 0;
	}

	$cache_key = 'yoga_legal_document_' . $type;
	$cached = wp_cache_get($cache_key, 'yoga');
	if ($cached !== false) {
		return (int) $cached;
	}

	$ids = get_posts(array(
		'post_type' => 'page',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'meta_key' => 'legal_document_type',
		'meta_value' => $type,
		'orderby' => 'modified',
		'order' => 'DESC',
	));
	$page_id = !empty($ids) ? (int) $ids[0] : 0;
	wp_cache_set($cache_key, $page_id, 'yoga');
	return $page_id;
}

function yoga_get_legal_document_url($type, $fallback = '') {
	$page_id = yoga_get_legal_document_page_id($type);
	if ($page_id > 0) {
		$url = get_permalink($page_id);
		if (is_string($url) && $url !== '') {
			return $url;
		}
	}
	return (string) $fallback;
}

function yoga_get_privacy_policy_url() {
	$fallback = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
	return yoga_get_legal_document_url('privacy_policy', $fallback ?: home_url('/privacy/'));
}

function yoga_validate_unique_legal_document_type($valid, $value, $field, $input) {
	if ($valid !== true || $value === null || $value === '') {
		return $valid;
	}
	$type = sanitize_key((string) $value);
	if (!isset(yoga_legal_document_types()[$type])) {
		return 'Выбрано неизвестное назначение страницы.';
	}
	$current_id = 0;
	$id_candidates = array(
		isset($_POST['post_ID']) ? wp_unslash($_POST['post_ID']) : '',
		isset($_POST['post_id']) ? wp_unslash($_POST['post_id']) : '',
		isset($_POST['_acf_post_id']) ? wp_unslash($_POST['_acf_post_id']) : '',
		isset($_GET['post']) ? wp_unslash($_GET['post']) : '',
	);
	if (function_exists('acf_get_form_data')) {
		$id_candidates[] = acf_get_form_data('post_id');
	}
	foreach ($id_candidates as $candidate) {
		if (is_string($candidate) && preg_match('/^(?:post_)?(\d+)$/', $candidate, $matches)) {
			$current_id = absint($matches[1]);
		} elseif (is_numeric($candidate)) {
			$current_id = absint($candidate);
		}
		if ($current_id > 0) {
			break;
		}
	}
	$existing = get_posts(array(
		'post_type' => 'page',
		'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
		'posts_per_page' => 1,
		'fields' => 'ids',
		'post__not_in' => $current_id > 0 ? array($current_id) : array(),
		'meta_key' => 'legal_document_type',
		'meta_value' => $type,
	));
	if (!empty($existing)) {
		return sprintf('Это назначение уже используется страницей «%s».', get_the_title((int) $existing[0]));
	}
	return $valid;
}
add_filter('acf/validate_value/name=legal_document_type', 'yoga_validate_unique_legal_document_type', 10, 4);

function yoga_flush_legal_document_cache($post_id) {
	if (get_post_type($post_id) !== 'page') {
		return;
	}
	foreach (array_keys(yoga_legal_document_types()) as $type) {
		wp_cache_delete('yoga_legal_document_' . $type, 'yoga');
	}
}
add_action('save_post_page', 'yoga_flush_legal_document_cache', 20);
add_action('trashed_post', 'yoga_flush_legal_document_cache');
