<?php
/**
 * Template Name: Contacts
 */
get_header(); ?>

<?php
// Подключаем все секции главной страницы
get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'form-questions');
$GLOBALS['has_subscription_section'] = true;
get_template_part('template-parts/section', 'subscription');
?>

<?php get_footer(); ?>