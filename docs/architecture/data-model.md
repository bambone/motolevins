# Модель данных (сводка)

Не полный DDL: уточняйте миграции в `database/migrations/`.

## Platform-level

| Таблица | Назначение |
|---------|------------|
| `tenants` | Клиент платформы: name, slug, status, timezone, locale, currency, plan_id, owner_user_id, template_preset_id, **theme_key**, brand_name, … |
| `tenant_domains` | Привязка хоста к клиенту: host, type (subdomain/custom), is_primary, **status**, **ssl_status**, dns_target, verification_method, verification_token, last_checked_at, verified_at, activated_at |
| `plans` | Тарифы, лимиты |
| `tenant_user` | pivot: user ↔ tenant, role, status |
| `platform_settings` | Ключ-значение |
| `platform_product_changelog_entries` | Чейнджлог **обновлений tenant-продукта** (тексты про кабинет клиента и публичный сайт тенанта): `entry_date`, `title`, `summary`, `body` (Markdown), `sort_weight` (порядок внутри дня), `is_published`. Только **central** БД, без `tenant_id`. Стартовые опубликованные строки вставляются **в той же миграции**, что создаёт таблицу (`2026_04_05_150000_*`). Чтение в UI: **кабинет клиента** → страница «Что нового» (`whats-new`); выдача только `is_published = true`, группы по дням от новых к старым, внутри дня — `sort_weight` DESC, затем `id` DESC. |
| `template_presets` | Шаблоны для клонирования при создании клиента |

## Tenant-scoped

Все с **`tenant_id` NOT NULL** (после миграций) и trait `BelongsToTenant`, кроме явных исключений в коде.

| Таблица | Заметки |
|---------|---------|
| `pages`, `page_sections` | CMS |
| `categories`, `motorcycles` | Каталог |
| `rental_units` | Единицы парка; **tenant_id** добавлена миграцией `2026_03_29_090000_*`, если таблица была создана раньше без колонки |
| `leads`, `customers`, `bookings` | CRM / брони |
| `availability_calendar`, `pricing_rules`, `addons`, `booking_addons` | Доступность и цены |
| `reviews`, `faqs`, `seo_meta`, `redirects` | Контент и SEO |
| `tenant_settings` | Настройки клиента (группа/ключ/значение) |
| `integrations`, `integration_logs`, `form_configs` | Интеграции |

## Уникальность (типовые)

- `pages.slug`, `motorcycles.slug`, `redirects.from_url` — в рамках `tenant_id`
- `tenant_domains.host` — глобально уникален
- `tenants.slug`, `plans.slug`, `template_presets.slug` — глобально

## Связи (кратко)

- Tenant hasMany TenantDomain, hasMany через tenant_user
- Motorcycle hasMany RentalUnit; Booking belongsTo Motorcycle, RentalUnit, Customer, …
- См. модели в `app/Models/`
