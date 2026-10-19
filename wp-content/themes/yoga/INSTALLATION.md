# Комплект установки

Тема `yoga` использует WordPress, WooCommerce, ACF и обязательный MU-плагин
`wp-content/mu-plugins/yoga-frontend-session.php` из этого репозитория.
При передаче архивов темы и `yoga-mail` этот MU-плагин нужно приложить отдельно,
сохранив путь `wp-content/mu-plugins/yoga-frontend-session.php` относительно корня WordPress.
WordPress загружает его автоматически: активация в списке обычных плагинов не требуется.
Архивы только темы и почтового плагина не являются полным комплектом сайта.

Для передачи на проверку используйте `kundalini-audit4-complete.zip`: MU-плагин
включён по рабочему пути `wp-content/mu-plugins/yoga-frontend-session.php`.
В отдельном архиве темы его копия находится в `yoga/required-mu-plugins/`;
при установке только темы эту копию нужно перенести в `wp-content/mu-plugins/`.
Нахождение копии внутри темы само по себе не включает MU-плагин.

До открытия регистрации проверить наличие файла и функций
`yoga_frontend_signon`, `yoga_set_frontend_auth_cookie`,
`yoga_frontend_session_can_change_credentials`.
Без MU-плагина вход и регистрация возвращают ошибку доступности до создания пользователя.

Проверки без подключения к БД и без отправки запросов к платёжному сервису:

```text
php wp-content/themes/yoga/tests/frontend-session.php
php wp-content/themes/yoga/tests/report-payments.php
php wp-content/themes/yoga/tests/report-downloads.php
php wp-content/themes/yoga/tests/report-protected-media.php
php wp-content/themes/yoga/tests/report-mobile-menu.php
php wp-content/themes/yoga/tests/report-tariff-timezone.php
php wp-content/themes/yoga/tests/report-mail.php
node wp-content/themes/yoga/tests/report-interface.js
```

Проверка меню со всеми обработчиками в Chromium (реальные клики и клавиши,
начальная загрузка 1300/375 px и изменение ширины без перезагрузки):

```text
php wp-content/themes/yoga/tests/report-mobile-menu.php --fixture menu-fixture.html
node wp-content/themes/yoga/tests/report-menu-browser.cjs <путь-к-chromium> menu-fixture.html
```

Тест выдачи PDF запускает изолированные процессы с ограничением времени,
в том числе из каталога с кириллицей. Старые домены учитываются только при
определении защиты и авторизованной выдаче локального протокола по точному пути uploads;
это не загрузка файлов со старого сервера и не изменение данных в БД.

Для защиты исходных upload URL также требуется правило веб-сервера:
пример находится в `deploy/nginx-protected-media.conf` локального проекта.
Учёт скачиваний использует именованную блокировку MySQL/MariaDB `GET_LOCK`;
при невозможности получить блокировку новая выдача файла отклоняется.

Изменения файлов, БД, конфигурации или кешей удалённых окружений выполняются
только после отдельного явного разрешения пользователя для конкретного окружения.
