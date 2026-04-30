<?php
/**
 * Шаблон для таксономии 'practice-type'
 */

$current_term = get_queried_object();

// Проверяем, является ли термин родительским
if (empty($current_term->parent)) {
    // Родительский термин - загружаем шаблон библиотеки практик
    get_template_part('template-parts/taxonomy', 'biblioteka-praktik');
} else {
    // Дочерний термин - загружаем шаблон крийи
    get_template_part('template-parts/taxonomy', 'kriyi');
}
?>