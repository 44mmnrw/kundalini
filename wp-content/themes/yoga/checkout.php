<?php
/**
 * Template Name: Checkout Page
 */

get_header(); ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <?php
            // Убедимся, что WooCommerce загружен
            if (class_exists('WooCommerce')) {
                
                // Выводим уведомления
                wc_print_notices();
                
                // Проверяем корзину
                if (WC()->cart->is_empty()) {
                    echo '<div class="alert alert-info">';
                    echo '<p>Ваша корзина пуста.</p>';
                    echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="btn btn-primary">Вернуться в магазин</a>';
                    echo '</div>';
                } else {
                    // Используем стандартную функцию WooCommerce
                    echo do_shortcode('[woocommerce_checkout]');
                }
            } else {
                echo '<p>WooCommerce не активирован.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>