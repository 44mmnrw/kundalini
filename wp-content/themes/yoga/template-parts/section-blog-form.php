<?php
/**
 * Переиспользуемый шаблонный блок: section blog form.
 *
 * @package Yoga
 */
	$blog_category = get_category_by_slug('blog');
	$radio_items = array();

	if ($blog_category) {

		$child_categories = get_categories(array(
        'parent' => $blog_category->term_id,
        'hide_empty' => false
		));


		foreach ($child_categories as $category) {
			$radio_items[] = array(
            'category_name' => $category->name,
            'category_slug' => $category->slug
			);
		}
	}

$form_action = home_url('/blog/');
$sprite_file = get_template_directory() . '/assets/svg/sprite.svg';
$sprite_version = file_exists($sprite_file) ? (string) filemtime($sprite_file) : wp_get_theme()->get('Version');
$sprite_href = add_query_arg('ver', rawurlencode($sprite_version), get_template_directory_uri() . '/assets/svg/sprite.svg');
$blog_search_value = '';
if (isset($_GET['s']) && is_string($_GET['s'])) {
	$blog_search_value = sanitize_text_field(wp_unslash($_GET['s']));
}
if ($blog_search_value === '') {
	$blog_search_value = get_search_query();
}
?>

<section class="section-blog-form animated fadeIn slow delay-200ms" id="section-blog-form">
    <div class="container">
        <div class="row">
            <div class="blog-form">
                <form action="<?php echo esc_url($form_action); ?>" method="get">
                    <div class="blog-radios">
						<?php if (!empty($radio_items)) : ?>

						<label class="active">
							<input type="radio" name="category" value="" checked>
							<span>Все статьи</span>
						</label>

						<?php foreach ($radio_items as $index => $item) : ?>
						<label>
							<input type="radio" name="category" value="<?php echo esc_attr($item['category_slug']); ?>">
							<span>
								<?php echo esc_html($item['category_name']); ?>
							</span>
						</label>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
                    <div class="blog-search">
                        <input type="text" name="s" class="input" placeholder="Что ищете?" value="<?php echo esc_attr($blog_search_value); ?>" required>
                        <label class="blog-search__btn">
                            <input type="submit">
							<svg class="blog-search__btn-icon" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
								<use href="<?php echo esc_url($sprite_href); ?>#search-icon"></use>
							</svg>
						</label>
                        <label class="blog-search__delete-btn">
                            <input type="reset">
							<svg class="blog-search__delete-icon" aria-hidden="true" focusable="false">
								<use href="<?php echo esc_url($sprite_href); ?>#lk-modal-close"></use>
							</svg>
						</label>
					</div>
				</form>
			</div>
		</div>
	</div>
</section>

