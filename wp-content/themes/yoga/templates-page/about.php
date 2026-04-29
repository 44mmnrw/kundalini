<?php
/**
 * Template Name: About
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'about');
?>

<?php get_footer(); ?>