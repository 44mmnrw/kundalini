# Комплект установки

Тема `yoga` использует WordPress, WooCommerce, ACF и обязательный MU-плагин
`wp-content/mu-plugins/yoga-frontend-session.php` из этого репозитория.
При передаче архивов темы и `yoga-mail` этот MU-плагин нужно приложить отдельно,
сохранив путь `wp-content/mu-plugins/yoga-frontend-session.php` относительно корня WordPress.
WordPress загружает его автоматически: активация в списке обычных плагинов не требуется.
Архивы только темы и почтового плагина не являются полным комплектом сайта.

До открытия регистрации проверить наличие файла и функций
`yoga_frontend_signon`, `yoga_set_frontend_auth_cookie`,
`yoga_frontend_session_can_change_credentials`.
Без MU-плагина вход и регистрация возвращают ошибку доступности до создания пользователя.

Проверки без подключения к БД и без отправки запросов к платёжному сервису:

```text
php wp-content/themes/yoga/tests/frontend-session.php
php wp-content/themes/yoga/tests/report-payments.php
php wp-content/themes/yoga/tests/report-downloads.php
php wp-content/themes/yoga/tests/report-tariff-timezone.php
php wp-content/themes/yoga/tests/report-mail.php
node wp-content/themes/yoga/tests/report-interface.js
```

Для защиты исходных upload URL также требуется правило веб-сервера:
пример находится в `deploy/nginx-protected-media.conf` локального проекта.
Учёт скачиваний использует именованную блокировку MySQL/MariaDB `GET_LOCK`;
при невозможности получить блокировку новая выдача файла отклоняется.

Изменения файлов, БД, конфигурации или кешей удалённых окружений выполняются
только после отдельного явного разрешения пользователя для конкретного окружения.
