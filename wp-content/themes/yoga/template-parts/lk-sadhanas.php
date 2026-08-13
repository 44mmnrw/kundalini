<?php
/**
 * User Sadhana account layout.
 *
 * @package Yoga
 */

$sadhana_sprite_file = get_template_directory() . '/assets/svg/sprite.svg';
$sadhana_sprite_url = add_query_arg(
	'ver',
	file_exists($sadhana_sprite_file) ? (string) filemtime($sadhana_sprite_file) : wp_get_theme()->get('Version'),
	get_template_directory_uri() . '/assets/svg/sprite.svg'
);
$sadhana_nav_urls = function_exists('yoga_lk_sidebar_secondary_nav_urls') ? yoga_lk_sidebar_secondary_nav_urls() : array();
$sadhana_library_url = (string) ($sadhana_nav_urls['library'] ?? home_url('/'));
$sadhana_user_id = (int) get_current_user_id();
$sadhana_active = function_exists('yoga_sadhana_get_user_rows') ? yoga_sadhana_get_user_rows($sadhana_user_id, 'active') : array();
$sadhana_completed = function_exists('yoga_sadhana_get_user_rows') ? yoga_sadhana_get_user_rows($sadhana_user_id, 'completed') : array();
?>

<div class="lk-sadhanas" data-sadhanas-layout>
	<div class="lk-sadhanas__tabs" role="tablist" aria-label="Состояние садхан">
		<button class="lk-sadhanas__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="lk-sadhanas-active" id="lk-sadhanas-active-tab" data-sadhanas-tab="active">Активные</button>
		<button class="lk-sadhanas__tab" type="button" role="tab" aria-selected="false" aria-controls="lk-sadhanas-completed" id="lk-sadhanas-completed-tab" data-sadhanas-tab="completed">Завершённые</button>
	</div>

	<section class="lk-sadhanas__panel is-active" id="lk-sadhanas-active" role="tabpanel" aria-labelledby="lk-sadhanas-active-tab" data-sadhanas-panel="active">
		<?php if ($sadhana_active) : ?>
			<div class="lk-sadhanas__grid">
				<?php foreach ($sadhana_active as $sadhana) { yoga_render_sadhana_card($sadhana, 'active', $sadhana_sprite_url); } ?>
			</div>
		<?php else : ?>
			<?php yoga_get_sadhana_empty_layout('active', $sadhana_library_url, $sadhana_sprite_url); ?>
		<?php endif; ?>
	</section>

	<section class="lk-sadhanas__panel" id="lk-sadhanas-completed" role="tabpanel" aria-labelledby="lk-sadhanas-completed-tab" data-sadhanas-panel="completed" hidden>
		<?php if ($sadhana_completed) : ?>
			<div class="lk-sadhanas__grid">
				<?php foreach ($sadhana_completed as $sadhana) { yoga_render_sadhana_card($sadhana, 'completed', $sadhana_sprite_url); } ?>
			</div>
		<?php else : ?>
			<?php yoga_get_sadhana_empty_layout('completed', $sadhana_library_url, $sadhana_sprite_url); ?>
		<?php endif; ?>
	</section>

	<template data-sadhanas-empty-template="active"><?php yoga_get_sadhana_empty_layout('active', $sadhana_library_url, $sadhana_sprite_url); ?></template>
	<template data-sadhanas-empty-template="completed"><?php yoga_get_sadhana_empty_layout('completed', $sadhana_library_url, $sadhana_sprite_url); ?></template>
</div>
