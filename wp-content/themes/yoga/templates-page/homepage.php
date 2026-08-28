<?php
/**
 * Template Name: Шаблон главной страницы
 *
 * Шаблон страницы: homepage.
 *
 * @package Yoga
 */
get_header(); ?>

<?php

$homepage_id = (int) get_queried_object_id();
if ($homepage_id <= 0) {
	$homepage_id = (int) get_the_ID();
}

$get_homepage_toggle = static function (string $field_name, bool $default) use ($homepage_id): bool {
	if (!function_exists('get_field') || !metadata_exists('post', $homepage_id, $field_name)) {
		return $default;
	}

	return (bool) get_field($field_name, $homepage_id);
};

$normalize_image_url = static function ($image): string {
	if (is_array($image)) {
		$image = $image['url'] ?? '';
	} elseif (is_numeric($image)) {
		$image = wp_get_attachment_image_url((int) $image, 'large');
	}

	return is_string($image) ? esc_url_raw($image) : '';
};

$normalize_video_url = static function (string $type, $url): string {
	if (is_array($url)) {
		$url = $url['url'] ?? '';
	} elseif (is_numeric($url)) {
		$url = wp_get_attachment_url((int) $url);
	}
	$url = is_string($url) ? trim($url) : '';
	if ($url === '') {
		return '';
	}

	if ($type === 'youtube') {
		$video_id = function_exists('yoga_get_youtube_video_id') ? yoga_get_youtube_video_id($url) : '';
		return $video_id !== '' ? 'https://www.youtube.com/watch?v=' . $video_id : '';
	}

	if ($type === 'vimeo') {
		$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
		$path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
		if (preg_match('/(?:^|\.)vimeo\.com$/', $host) && preg_match('/^(?:video\/)?(\d+)(?:\/|$)/', $path, $matches)) {
			return 'https://vimeo.com/' . $matches[1];
		}

		return '';
	}

	return wp_http_validate_url($url) ? esc_url_raw($url) : '';
};

$reviews_items = function_exists('get_field') ? get_field('reviews_items', $homepage_id) : array();
$review_people = function_exists('get_field') ? get_field('review_people', $homepage_id) : array();
$videos_items = function_exists('get_field') ? get_field('videos_items', $homepage_id) : array();
$reviews_items = is_array($reviews_items) ? $reviews_items : array();
$review_people = is_array($review_people) ? $review_people : array();
$videos_items = is_array($videos_items) ? $videos_items : array();
$reviews_items = array_values(array_filter($reviews_items, static function ($review): bool {
	if (!is_array($review)) {
		return false;
	}

	foreach (array('review_name', 'review_excerpt', 'review_full_text', 'review_image') as $field_name) {
		if (!empty($review[$field_name])) {
			return true;
		}
	}

	return false;
}));

$prepared_videos = array();
foreach ($videos_items as $video) {
	if (!is_array($video)) {
		continue;
	}

	$type = strtolower(trim((string) ($video['video_type'] ?? 'mp4')));
	$url = $normalize_video_url($type, $video['video_url'] ?? '');
	if ($url === '') {
		continue;
	}

	$video['_fancybox_url'] = $url;
	$video['_bg_url'] = $normalize_image_url($video['video_bg_image'] ?? '');
	$video['_person_url'] = $normalize_image_url($video['video_person_image'] ?? '');
	$prepared_videos[] = $video;
}

$show_reviews_section = $get_homepage_toggle('show_reviews_section', false) && !empty($reviews_items);
$show_videos_section = $get_homepage_toggle('show_videos_section', true) && !empty($prepared_videos);
$show_review_people_photos = $get_homepage_toggle('show_review_people_photos', true);

get_template_part('template-parts/section', 'hero');
get_template_part('template-parts/section', 'advantages');
get_template_part('template-parts/section', 'whyme');
get_template_part('template-parts/section', 'begin');
get_template_part('template-parts/section', 'tariffs');
if ($show_reviews_section) {
	get_template_part('template-parts/section', 'reviews', array(
		'items' => $reviews_items,
		'people' => $review_people,
		'show_photos' => $show_review_people_photos,
		'videos_hidden' => !$show_videos_section,
	));
}
if ($show_videos_section) {
	get_template_part('template-parts/section', 'videos', array(
		'items' => $prepared_videos,
		'reviews_hidden' => !$show_reviews_section,
	));
}
get_template_part('template-parts/section', 'popular', array(
	'testimonials_hidden' => !$show_reviews_section && !$show_videos_section,
	'follows_reviews' => $show_reviews_section && !$show_videos_section,
));
get_template_part('template-parts/section', 'questions');
$GLOBALS['has_subscription_section'] = true;
get_template_part('template-parts/section', 'subscription');
?>

<?php get_footer(); ?>
