# Деплой на production

## Ошибка `Table '…tenant_domains' doesn't exist`

Сообщение `SQLSTATE[42S02]: Base table or view not found: 1146 … tenant_domains` означает, что в **той БД**, к которой подключается Laravel на сервере (см. `DB_*` в `.env`), **ещё не создана схема** из миграций. Локально таблица есть, потому что у вас выполнен `php artisan migrate`.

### Что сделать на сервере

1. SSH в хостинг, перейти в каталог приложения (у вас в логе: `…/gmtst2.ru/motolevins`).
2. Убедиться, что `.env` указывает на нужную базу `xnjlcdaa_motolevins` (или актуальное имя).
3. Выполнить миграции в неинтерактивном режиме:

```bash
php artisan migrate --force
```

4. Проверить, что миграция `2026_03_19_090002_create_tenant_domains_table` в статусе **Ran**:

```bash
php artisan migrate:status
```

5. **Порядок важен:** сначала миграции, потом кеш конфига. Если выполнить `php artisan config:cache` до появления на сервере всех файлов в `config/` (в т.ч. `config/permission.php`), в `bootstrap/cache/config.php` не попадёт пакетный конфиг — следующий `migrate` упрётся в ошибку ниже.

6. После успешных миграций при необходимости: `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.

После этого запрос к `tenant_domains` в `TenantResolver` перестанет падать с 1146.

---

## Ошибка `config/permission.php not loaded` (миграция `create_permission_tables`)

Текст вроде `Error: config/permission.php not loaded. Run [php artisan config:clear] and try again` (в стеке может фигурировать `helpers.php` из `vendor/laravel/framework` — это внутренняя реализация `throw_if`) выбрасывается из миграции `2026_03_19_125704_create_permission_tables`, когда `config('permission.table_names')` пустой.

**Типичные причины на production**

1. **Устаревший кеш конфига** — ранее запускали `config:cache`, когда файла `config/permission.php` ещё не было или он не попал в деплой.
2. **Файл не задеплоен** — на сервере нет `config/permission.php` (проверьте `ls config/permission.php` или аналог).

**Что сделать**

1. Убедиться, что в репозитории и на сервере есть `config/permission.php`.
2. Сбросить кеш конфигурации и снова прогнать миграции (прерванная миграция прав не создала таблиц Spatie — повторный запуск безопасен):

```bash
php artisan config:clear
php artisan migrate --force
```

3. Уже **после** успешного `migrate` при желании: `php artisan config:cache`.

Так вы избежите ситуации, когда `migrate` читает «пустой» `permission.*` из старого `bootstrap/cache/config.php`.

### Рекомендация по URL

Желательно настроить **document root** виртуального хоста на каталог `public` приложения, чтобы сайт открывался как `https://gmtst2.ru/` без сегмента `/motolevins/public/` в пути.
