<?php
/** Предупреждения используют согласованные карточки уведомлений 2918:27962 / 2918:27972. */
$warnings = array_filter(yoga_get_user_notifications((int) ($args['user_id'] ?? get_current_user_id()), 100), static function ($notification): bool {
	return in_array($notification['type'] ?? '', array('comment_restricted', 'comment_banned'), true);
});
?>
<div class="lk-community lk-warnings lk-slide--notifications">
	<header class="lk-community__head"><h1>Мои предупреждения</h1></header>
	<?php if (!$warnings) : ?>
	<div class="lk-notifications-empty" role="status"><span class="lk-notifications-empty__icon" aria-hidden="true"><?php yoga_lk_section_icon('menu-warnings'); ?></span><div class="lk-notifications-empty__text"><h3>Здесь пока ничего нет</h3></div></div>
	<?php else : ?>
	<div class="lk-notifications-list">
		<?php foreach ($warnings as $notification) { yoga_render_comment_restriction_notification($notification, false); } ?>
	</div>
	<?php endif; ?>
</div>
