<?php
/** Отметка завершения доступна после запуска таймера или плеера. */
if (!is_user_logged_in() || !yoga_statistics_can_track_practice((int) get_current_user_id(), (int) get_the_ID())) { return; }
?>
<div class="praktika-activity" data-practice-activity hidden>
	<button type="button" class="btn" disabled><span><?php esc_html_e('Практика выполнена', 'yoga'); ?></span></button>
	<p role="status" aria-live="polite"></p>
</div>
