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

                // Инициализируем сессию/корзину максимально безопасно (защита от 5xx на checkout)
                if (function_exists('WC')) {
                    $wc = WC();

                    if ($wc && isset($wc->session) && $wc->session && method_exists($wc->session, 'has_session') && !$wc->session->has_session()) {
                        $wc->session->set_customer_session_cookie(true);
                    }

                    if ((!isset($wc->cart) || !$wc->cart) && function_exists('wc_load_cart')) {
                        wc_load_cart();
                    }
                }

                // Выводим уведомления, только если функция доступна
                if (function_exists('wc_print_notices')) {
                    wc_print_notices();
                }

                $cart = function_exists('WC') ? WC()->cart : null;
                $has_cart_items = $cart && method_exists($cart, 'is_empty') ? !$cart->is_empty() : false;

                // Проверяем корзину
                if (!$has_cart_items) {
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