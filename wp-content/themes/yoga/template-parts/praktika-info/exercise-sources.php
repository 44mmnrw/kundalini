<?php
/** Sources shared by all execution variants of the current exercise. */
if (!defined('ABSPATH')) {
	exit;
}
$sources = $exercise['sources'] ?? '';
$sources_html = is_string($sources) ? wp_kses_post(wpautop($sources)) : '';
if (trim(html_entity_decode(wp_strip_all_tags($sources_html), ENT_QUOTES, 'UTF-8'), " \t\n\r\0\x0B\xc2\xa0") === '') {
	return;
}
?>
<aside class="exercise-sources" aria-label="<?php echo esc_attr__('Источники описания', 'yoga'); ?>">
	<div class="exercise-sources__icon" aria-hidden="true">
		<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/svg/exercise-sources-info.svg'); ?>" alt="" width="22" height="22">
	</div>
	<div class="exercise-sources__text">
		<p>Описание этой крийи составлено авторами сайта KUNDALINI.LOVE на основании следующих источников:</p>
		<?php echo $sources_html; ?>
	</div>
</aside>
