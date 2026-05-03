<?php
/**
 * Template Name: Шаблон главной страницы
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
$show_videos_section = true;

if (function_exists('get_field')) {
	$raw_show_videos = get_field('show_videos_section', get_the_ID());

	if ($raw_show_videos !== null && $raw_show_videos !== '') {
		$show_videos_section = (bool) $raw_show_videos;
	}
}

get_template_part('template-parts/section', 'hero');
get_template_part('template-parts/section', 'advantages');
get_template_part('template-parts/section', 'whyme');
get_template_part('template-parts/section', 'begin');
get_template_part('template-parts/section', 'tariffs');
get_template_part('template-parts/section', 'reviews');
if ($show_videos_section) {
	get_template_part('template-parts/section', 'videos');
}
get_template_part('template-parts/section', 'popular');
get_template_part('template-parts/section', 'questions');
$GLOBALS['has_subscription_section'] = true;
get_template_part('template-parts/section', 'subscription');
?>

<?php get_footer(); ?>