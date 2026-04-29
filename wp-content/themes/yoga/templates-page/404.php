<?php
/**
 * Template Name: 404
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'notfound');
?>

<?php get_footer(); ?>