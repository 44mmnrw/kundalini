<?php
/**
 * Оформление подписки (/checkout/).
 */

if (function_exists('yoga_handle_cart_mutation_request')) {
	yoga_handle_cart_mutation_request();
}

get_header();

if (function_exists('yoga_ensure_wc_cart_session')) {
	yoga_ensure_wc_cart_session();
}

$wc_cart = function_exists('WC') && WC()->cart ? WC()->cart : null;
$is_empty = !$wc_cart || $wc_cart->is_empty();
?>

<?php if ($is_empty) : ?>
	<section class="section-checkout section-checkout_empty" id="section-checkout">
		<div class="container">
			<div class="row">
				<div class="yoga-checkout yoga-checkout_empty">
					<h1 class="yoga-checkout__title"><?php esc_html_e('КОРЗИНА', 'yoga'); ?></h1>
					<p class="yoga-checkout-empty__text"><?php esc_html_e('Выберите тариф, чтобы оформить подписку.', 'yoga'); ?></p>
					<?php
					$tariffs_url = home_url('/product-category/tariffs/');
					$tariffs_term = get_term_by('slug', 'tariffs', 'product_cat');
					if ($tariffs_term && !is_wp_error($tariffs_term)) {
						$term_link = get_term_link($tariffs_term);
						if (!is_wp_error($term_link)) {
							$tariffs_url = $term_link;
						}
					}
					?>
					<a href="<?php echo esc_url($tariffs_url); ?>" class="btn btn_alt">
						<span><?php echo esc_html(function_exists('yoga_get_purchase_cta_text') ? yoga_get_purchase_cta_text() : __('Выбрать тариф', 'yoga')); ?></span>
					</a>
				</div>
			</div>
		</div>
	</section>
<?php else : ?>
	<?php get_template_part('template-parts/section', 'checkout'); ?>
<?php endif; ?>

<?php get_footer(); ?>
