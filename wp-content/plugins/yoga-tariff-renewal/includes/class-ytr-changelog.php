<?php

if (!defined('ABSPATH')) {
	exit;
}

final class YTR_Changelog {
	/**
	 * @return array<string, array{date:string,items:string[]}>
	 */
	public static function get_entries(): array {
		return array(
			'1.1.1' => array(
				'date'  => '2026-06-10',
				'items' => array(
					__('Исправлено сохранение карты в ЮKassa для автопродления: при создании платежа через EPL/виджет save_payment_method теперь берётся из meta заказа (_ytr_auto_renew_opt_in), а не только из $_POST на чекауте.', 'yoga-tariff-renewal'),
					__('Фильтр woocommerce_yookassa_create_payment_request для сохранения карты выполняется с приоритетом 99 (после настроек темы).', 'yoga-tariff-renewal'),
				),
			),
			'1.1.0' => array(
				'date'  => '2026-06-10',
				'items' => array(
					__('Статус WP Cron в админке: последний запуск, просрочка, источник, примеры crontab.', 'yoga-tariff-renewal'),
					__('Настройка частоты проверки продлений (5 мин — сутки).', 'yoga-tariff-renewal'),
					__('Исправлено сравнение времени cron (UTC) для статуса «просрочено».', 'yoga-tariff-renewal'),
				),
			),
			'1.0.0' => array(
				'date'  => '2026-06-08',
				'items' => array(
					__('Первый релиз: автопродление тарифов через ЮKassa, карты в ЛК, cron, ручной запуск.', 'yoga-tariff-renewal'),
				),
			),
		);
	}
}
