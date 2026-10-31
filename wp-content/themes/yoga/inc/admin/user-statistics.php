<?php
/** Статистика в карточке пользователя и простые общие таблицы WordPress. */
if (!defined('ABSPATH')) { exit; }

function yoga_statistics_table(array $headers, array $rows, string $empty = 'Данных пока нет.'): void {
	echo '<div style="overflow-x:auto;margin:12px 0 24px"><table class="widefat striped"><thead><tr>';
	foreach ($headers as $header) { echo '<th scope="col">' . esc_html($header) . '</th>'; }
	echo '</tr></thead><tbody>';
	if (!$rows) { echo '<tr><td colspan="' . count($headers) . '">' . esc_html($empty) . '</td></tr>'; }
	foreach ($rows as $row) {
		echo '<tr>';
		// Callers escape text, URLs and monetary markup before passing cells.
		foreach ($row as $cell) { echo '<td>' . $cell . '</td>'; }
		echo '</tr>';
	}
	echo '</tbody></table></div>';
}

function yoga_statistics_practice_link(int $practice_id): string {
	$title = get_the_title($practice_id);
	if (!$title) { return esc_html('Практика #' . $practice_id . ' (удалена)'); }
	$url = get_edit_post_link($practice_id, 'raw');
	return $url ? '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>' : esc_html($title);
}

function yoga_statistics_user_link(int $user_id): string {
	$user = get_userdata($user_id);
	if (!$user) { return esc_html('Пользователь #' . $user_id . ' (удалён)'); }
	return '<a href="' . esc_url(get_edit_user_link($user_id)) . '">' . esc_html($user->display_name . ' (#' . $user_id . ')') . '</a>';
}

function yoga_statistics_price(float $amount, string $currency): string {
	return wp_kses_post(wc_price($amount, array('currency' => $currency)));
}

function yoga_statistics_page_number(string $key): int {
	return isset($_GET[$key]) && is_scalar($_GET[$key]) ? max(1, absint($_GET[$key])) : 1;
}

function yoga_statistics_pagination(string $key, int $page, int $pages, string $url, string $anchor = ''): void {
	if ($pages <= 1) { return; }
	echo '<p>';
	if ($page > 1) { echo '<a class="button" href="' . esc_url(add_query_arg($key, $page - 1, $url) . $anchor) . '">Предыдущая</a> '; }
	echo esc_html(sprintf('Страница %d из %d', $page, $pages));
	if ($page < $pages) { echo ' <a class="button" href="' . esc_url(add_query_arg($key, $page + 1, $url) . $anchor) . '">Следующая</a>'; }
	echo '</p>';
}

function yoga_statistics_render_finance_totals(array $report): void {
	if (!$report['available']) { echo '<p>WooCommerce недоступен. Финансовая статистика не загружена.</p>'; return; }
	if ($report['error'] !== '') { echo '<p>' . esc_html($report['error']) . '</p>'; return; }
	$rows = array();
	foreach ($report['currencies'] as $currency => $totals) {
		$rows[] = array(esc_html($currency), (int) $totals['orders'], yoga_statistics_price($totals['paid'], $currency), yoga_statistics_price($totals['refunded'], $currency), '<strong>' . yoga_statistics_price($totals['net'], $currency) . '</strong>');
	}
	yoga_statistics_table(array('Валюта', 'Оплат, включая возвращённые', 'Всего оплачено', 'Возвращено', 'Оплачено за вычетом возвратов'), $rows, 'Оплаченных заказов пока нет.');
	echo '<p class="description">Учитываются оплаченные и возвращённые заказы WooCommerce. Неоплаченные заказы и технические платежи привязки карты исключены. Суммы включают налоги и выводятся отдельно по валютам.</p>';
}

function yoga_statistics_render_user_orders(int $user_id): void {
	if (!function_exists('wc_get_orders')) { return; }
	$page = yoga_statistics_page_number('yoga_orders_page');
	echo '<h3 id="yoga-statistics-orders">История заказов и подписок</h3>';
	try {
		$result = wc_get_orders(array('type' => 'shop_order', 'customer_id' => $user_id, 'limit' => 20, 'page' => $page, 'paginate' => true, 'orderby' => 'date', 'order' => 'DESC'));
		if (!is_object($result) || !isset($result->orders, $result->max_num_pages)) { throw new UnexpectedValueException('Invalid order query result'); }
	} catch (Throwable $error) { echo '<p>Не удалось прочитать историю заказов.</p>'; return; }
	$rows = array();
	foreach ($result->orders as $order) {
		$items = array();
		foreach ($order->get_items('line_item') as $item) { $items[] = esc_html($item->get_name() . ' × ' . $item->get_quantity()); }
		$created = $order->get_date_created();
		$type = $order->get_meta('_ytr_card_binding') === 'yes' ? 'Привязка карты' : ($order->get_meta('_ytr_renewal') === 'yes' ? 'Автопродление' : 'Покупка');
		$rows[] = array('<a href="' . esc_url($order->get_edit_order_url()) . '">#' . esc_html($order->get_order_number()) . '</a>', esc_html($created ? wp_date('d.m.Y H:i', $created->getTimestamp()) : '—'), implode('<br>', $items), esc_html($type), esc_html(wc_get_order_status_name($order->get_status())), yoga_statistics_price((float) $order->get_total(), $order->get_currency()), yoga_statistics_price((float) $order->get_total_refunded(), $order->get_currency()));
	}
	yoga_statistics_table(array('Заказ', 'Дата', 'Тарифы и товары', 'Тип', 'Статус', 'Сумма заказа', 'Возврат'), $rows, 'Заказов пока нет.');
	yoga_statistics_pagination('yoga_orders_page', $page, (int) $result->max_num_pages, get_edit_user_link($user_id), '#yoga-statistics-orders');
	echo '<p class="description">История привязана к ID аккаунта покупателя. Неоплаченные заказы видны в истории со своим статусом, но не входят в сумму оплат.</p>';
}

function yoga_statistics_render_practice_counts(array $activity): void {
	if (!$activity['available']) { echo '<p>Счётчик практик недоступен: таблица ещё не создана.</p>'; return; }
	yoga_statistics_table(array('Показатель', 'Количество'), array(
		array('Запусков через таймер или плеер', $activity['starts']),
		array('Завершений выполнения', $activity['completions']),
		array('Разных начатых практик', $activity['practices_started']),
		array('Разных завершённых практик', $activity['practices_completed']),
	));
	$since = get_option('yoga_practice_activity_since', '');
	echo '<p class="description">Открытие страницы, пауза и продолжение не добавляют запуск. Завершение — выполнение всех упражнений через таймер, окончание полного видео или отметка «Практика выполнена» после запуска. Счётчик собирает новые данные' . ($since ? ' с ' . esc_html($since) . ' UTC' : '') . '; прошлые запуски не восстанавливаются.</p>';
}

function yoga_statistics_render_sadhana_counts(array $sadhanas, bool $user = false): void {
	if (!$sadhanas['available']) { echo '<p>Данные счётчика садхан недоступны.</p>'; return; }
	$rows = array(array('Активных садхан', $sadhanas['active']), array('Завершённых садхан', $sadhanas['completed']), array('Прерванных и отменённых садхан', $sadhanas['cancelled']), array('Всего отмеченных дней садхан', $sadhanas['days']));
	if ($user) { $rows[] = array('Текущая серия: максимальная среди садхан', $sadhanas['streak']); }
	yoga_statistics_table(array('Показатель', 'Количество'), $rows);
	echo '<p class="description">Дни суммируются по циклам: один день в двух садханах даёт две отметки. Текущая серия учитывается, если последняя отметка была сегодня или вчера в часовом поясе пользователя. Просмотр статистики не изменяет садханы и не отправляет уведомления.</p>';
}

function yoga_statistics_render_user_profile($user): void {
	$user_id = (int) $user->ID;
	if (!current_user_can('manage_options') || !current_user_can('edit_user', $user_id)) { return; }
	echo '<h2>Статистика пользователя</h2><h3>Оплаты</h3>';
	yoga_statistics_render_finance_totals(yoga_statistics_finances($user_id));
	yoga_statistics_render_user_orders($user_id);
	echo '<h3>Практики</h3>';
	$activity = yoga_statistics_activity_summary($user_id);
	yoga_statistics_render_practice_counts($activity);
	$page = yoga_statistics_page_number('yoga_activity_page');
	$rows = array();
	foreach (yoga_statistics_popular_practices($user_id, $page) as $row) {
		$rows[] = array(yoga_statistics_practice_link((int) $row['practice_id']), (int) $row['starts'], (int) $row['completions']);
	}
	echo '<h3 id="yoga-statistics-practices">Начатые и завершённые практики</h3>';
	yoga_statistics_table(array('Практика', 'Запусков', 'Завершений'), $rows);
	yoga_statistics_pagination('yoga_activity_page', $page, (int) ceil($activity['practices_started'] / 20), get_edit_user_link($user_id), '#yoga-statistics-practices');
	$legacy = get_user_meta($user_id, 'completed_practices', true);
	if (is_array($legacy) && $legacy) {
		$legacy_ids = array_unique(array_filter(array_map('absint', array_filter($legacy, 'is_scalar'))));
		echo '<p class="description">В прежней истории отмечено выполненными практик: ' . count($legacy_ids) . '. Эти записи без дат и запусков не смешиваются с новым счётчиком.</p>';
	}
	echo '<h3>Садханы</h3>';
	$sadhanas = yoga_statistics_sadhanas($user_id);
	yoga_statistics_render_sadhana_counts($sadhanas, true);
	$page = yoga_statistics_page_number('yoga_sadhanas_page');
	$rows = array();
	$statuses = array('active' => 'Активна', 'completed' => 'Завершена', 'cancelled' => 'Прервана / отменена');
	$cycles = array_reverse($sadhanas['rows']);
	foreach (array_slice($cycles, ($page - 1) * 20, 20) as $row) {
		$rows[] = array(yoga_statistics_practice_link((int) $row['practice_id']), esc_html($statuses[$row['status']] ?? $row['status']), esc_html($row['started_on']), (int) $row['completed_days'] . ' / ' . (int) $row['target_days'], (int) $row['streak'], esc_html($row['last_marked_on'] ?: '—'));
	}
	echo '<h3 id="yoga-statistics-sadhanas">История садхан</h3>';
	yoga_statistics_table(array('Практика', 'Статус', 'Начало', 'Выполнено дней', 'Текущая серия', 'Последняя отметка'), $rows);
	yoga_statistics_pagination('yoga_sadhanas_page', $page, (int) ceil(count($cycles) / 20), get_edit_user_link($user_id), '#yoga-statistics-sadhanas');
}
add_action('show_user_profile', 'yoga_statistics_render_user_profile');
add_action('edit_user_profile', 'yoga_statistics_render_user_profile');

function yoga_statistics_register_admin_page(): void {
	add_users_page('Статистика пользователей', 'Статистика', 'manage_options', 'yoga-user-statistics', 'yoga_statistics_render_admin_page');
}
add_action('admin_menu', 'yoga_statistics_register_admin_page');

function yoga_statistics_render_admin_page(): void {
	if (!current_user_can('manage_options')) { wp_die('Недостаточно прав.'); }
	echo '<div class="wrap"><h1>Статистика пользователей</h1>';
	$users = count_users();
	$activity = yoga_statistics_activity_summary();
	$sadhanas = yoga_statistics_sadhanas();
	yoga_statistics_table(array('Показатель', 'Количество'), array(array('Всего пользователей', (int) $users['total_users']), array('Пользователей с запуском практики', $activity['users'])));
	echo '<h2>Практики</h2>';
	yoga_statistics_render_practice_counts($activity);
	$rows = array();
	foreach (yoga_statistics_popular_practices() as $row) { $rows[] = array(yoga_statistics_practice_link((int) $row['practice_id']), (int) $row['starts'], (int) $row['completions']); }
	echo '<h2>Популярные практики — первые 20 по запускам</h2>';
	yoga_statistics_table(array('Практика', 'Запусков', 'Завершений'), $rows);
	echo '<h2>Садханы</h2>';
	yoga_statistics_render_sadhana_counts($sadhanas);
	$rows = array();
	foreach (array_slice($sadhanas['popular'], 0, 20) as $row) { $rows[] = array(yoga_statistics_practice_link((int) $row['practice_id']), (int) $row['starts'], (int) $row['active'], (int) $row['completed'], (int) $row['days']); }
	echo '<h2>Популярные садханы — первые 20 по начатым циклам</h2>';
	yoga_statistics_table(array('Практика', 'Начатых циклов', 'Активных', 'Завершённых', 'Отмеченных дней'), $rows);
	echo '<h2>Выручка</h2>';
	$finances = yoga_statistics_finances();
	yoga_statistics_render_finance_totals($finances);
	if ($finances['available'] && $finances['error'] === '') {
		$rows = array();
		$page = yoga_statistics_page_number('yoga_revenue_page');
		foreach (array_slice(array_values($finances['users']), ($page - 1) * 25, 25) as $row) { $rows[] = array(yoga_statistics_user_link($row['user_id']), esc_html($row['currency']), $row['orders'], yoga_statistics_price($row['net'], $row['currency'])); }
		echo '<h2 id="yoga-statistics-revenue">Выручка по пользователям</h2>';
		yoga_statistics_table(array('Пользователь', 'Валюта', 'Оплат', 'За вычетом возвратов'), $rows);
		yoga_statistics_pagination('yoga_revenue_page', $page, (int) ceil(count($finances['users']) / 25), admin_url('users.php?page=yoga-user-statistics'), '#yoga-statistics-revenue');
		$rows = array();
		foreach ($finances['currencies'] as $currency => $totals) { if ($totals['guest_net'] > 0) { $rows[] = array('Без аккаунта покупателя', esc_html($currency), yoga_statistics_price($totals['guest_net'], $currency)); } }
		if ($rows) { yoga_statistics_table(array('Гостевые заказы', 'Валюта', 'За вычетом возвратов'), $rows); }
		$rows = array();
		$page = yoga_statistics_page_number('yoga_tariffs_page');
		foreach (array_slice(array_values($finances['tariffs']), ($page - 1) * 25, 25) as $row) {
			$url = get_edit_post_link($row['product_id'], 'raw');
			$name = $url ? '<a href="' . esc_url($url) . '">' . esc_html($row['name']) . '</a>' : esc_html($row['name']);
			$rows[] = array($name, esc_html($row['currency']), $row['quantity'], yoga_statistics_price($row['net'], $row['currency']));
		}
		echo '<h2 id="yoga-statistics-tariffs">Популярные тарифы</h2>';
		yoga_statistics_table(array('Тариф', 'Валюта', 'Куплено за вычетом возвращённых позиций', 'Выручка по позициям'), $rows);
		yoga_statistics_pagination('yoga_tariffs_page', $page, (int) ceil(count($finances['tariffs']) / 25), admin_url('users.php?page=yoga-user-statistics'), '#yoga-statistics-tariffs');
		echo '<p class="description">Тарифы сгруппированы по товару, варианты периода объединены. Сортировка — по количеству покупок, отдельно для каждой валюты. Возвраты без привязки к позициям распределяются пропорционально оставшейся стоимости позиций. Старые удалённые товары без сохранённого типа тарифа не входят в этот список.</p>';
		echo '<p class="description">Финансовые итоги обновляются при изменении заказов или не позднее чем через 5 минут. Время расчёта: ' . esc_html(wp_date('d.m.Y H:i', $finances['updated_at'])) . '.</p>';
	}
	echo '</div>';
}
