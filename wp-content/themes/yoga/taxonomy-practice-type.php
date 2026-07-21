<?php
/**
 * Компонент темы: taxonomy practice type.
 *
 * @package Yoga
 */
$current_term = get_queried_object();


if (empty($current_term->parent)) {

    get_template_part('template-parts/taxonomy', 'biblioteka-praktik');
} else {

    get_template_part('template-parts/taxonomy', 'practice-category');
}
?>