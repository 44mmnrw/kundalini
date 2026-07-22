<?php
/**
 * Template Name: Contacts
 *
 * Шаблон страницы: contacts.
 *
 * @package Yoga
 */
get_header(); ?>

<?php

get_template_part('template-parts/section', 'ways');
get_template_part('template-parts/section', 'form-questions');
$GLOBALS['has_subscription_section'] = true;
get_template_part('template-parts/section', 'subscription');
?>

<?php get_footer(); ?>
