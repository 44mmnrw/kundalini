<?php
/**
 * Sort profile avatars into the Folders plugin's media taxonomy.
 *
 * @package Yoga
 */
if (!defined('ABSPATH')) {
	exit;
}

function yoga_assign_avatar_to_folder(int $attachment_id): void {
	if ($attachment_id <= 0 || !taxonomy_exists('media_folder')) {
		return;
	}

	$folder = term_exists('аватарки', 'media_folder', 0);
	if (!$folder || is_wp_error($folder)) {
		$folder = wp_insert_term('аватарки', 'media_folder', array('parent' => 0));
		if (is_wp_error($folder)) {
			if ($folder->get_error_code() !== 'term_exists') {
				return;
			}
			$term_id = (int) $folder->get_error_data('term_exists');
		} else {
			$term_id = (int) $folder['term_id'];
			update_term_meta($term_id, 'wcp_custom_order', 0);
		}
	} else {
		$term_id = (int) $folder['term_id'];
	}

	if ($term_id > 0) {
		wp_set_object_terms($attachment_id, array($term_id), 'media_folder', false);
	}
}
