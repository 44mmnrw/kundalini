<?php
/**
 * Компонент темы: sprite icons page.
 *
 * @package Yoga
 */
if (!function_exists('yoga_render_sprite_icons_page')) {
	function yoga_render_sprite_icons_page(): void {
		if (is_admin() || wp_doing_ajax()) {
			return;
		}

		$request_path = untrailingslashit((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
		if ($request_path !== '/sprite-icons') {
			return;
		}

		$sprite_path = get_template_directory() . '/assets/svg/sprite.svg';
		$sprite = is_readable($sprite_path) ? (string) file_get_contents($sprite_path) : '';
		preg_match_all('/<symbol\\s+[^>]*id=["\\\']([^"\\\']+)["\\\'][^>]*>/i', $sprite, $matches);
		$icons = array_values(array_unique($matches[1] ?? []));
		sort($icons, SORT_NATURAL | SORT_FLAG_CASE);

		status_header(200);
		nocache_headers();
		get_header();
		?>
		<main class="sprite-icons-page">
			<div class="sprite-icons-page__inner">
				<h1>Иконки SVG-спрайта</h1>
				<p class="sprite-icons-page__count">Всего: <?php echo esc_html((string) count($icons)); ?></p>
				<div class="sprite-icons-page__grid">
					<?php foreach ($icons as $icon_id) : ?>
						<article class="sprite-icons-page__item">
							<svg class="sprite-icons-page__icon" aria-hidden="true" focusable="false"><use href="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/sprite.svg#' . $icon_id); ?>"></use></svg>
							<code><?php echo esc_html($icon_id); ?></code>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</main>
		<style>
			.sprite-icons-page { padding: 48px 20px 80px; background: #fff; color: #1f1f1f; }
			.sprite-icons-page__inner { width: min(1200px, 100%); margin: 0 auto; }
			.sprite-icons-page h1 { margin: 0; font-size: 36px; }
			.sprite-icons-page__count { margin: 10px 0 28px; color: #666; }
			.sprite-icons-page__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
			.sprite-icons-page__item { min-height: 132px; padding: 16px; border: 1px solid #d9d9d9; border-radius: 12px; display: grid; align-content: space-between; gap: 16px; background: #fafafa; }
			.sprite-icons-page__icon { width: 48px; height: 48px; color: #1f1f1f; fill: currentColor; }
			.sprite-icons-page__item code { display: block; overflow-wrap: anywhere; font-size: 12px; line-height: 1.35; }
			@media (max-width: 575px) { .sprite-icons-page { padding: 28px 15px 48px; } .sprite-icons-page h1 { font-size: 26px; } .sprite-icons-page__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; } .sprite-icons-page__item { min-height: 120px; padding: 12px; } }
		</style>
		<?php
		get_footer();
		exit;
	}

	add_action('template_redirect', 'yoga_render_sprite_icons_page', 0);
}
