<?php
/**
 * Template Name: Tariffs
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'tariffs_single');
get_template_part('template-parts/section', 'subscription');
?>

<?php get_footer(); ?>