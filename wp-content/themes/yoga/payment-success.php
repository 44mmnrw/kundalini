<?php
/**
 * Успешная оплата — /payment-success/ и редирект с order-received.
 */
get_header();

get_template_part('template-parts/section', 'payment-success');

get_footer();
