<?php
/**
 * Template Name: Privacy-policy
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'rules');
?>

<?php get_footer(); ?>