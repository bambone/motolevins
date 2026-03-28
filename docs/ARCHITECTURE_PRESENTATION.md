# Presentation layer: platform marketing, tenant engine, themes

Один Laravel-приложение, один `public/`; разделение по **хостам** (уже сделано) и по **каталогам views/controllers** (в процессе).

## Целевая структура каталогов

### Views

```
resources/views/
├── platform/
│   ├── marketing/           # лендинг rentbase.su / www (Route::view на central domains)
│   └── layouts/
│       └── marketing.blade.php
├── tenant/
│   ├── layouts/
│   │   └── app.blade.php    # единственный entrypoint публичного сайта тенанта (Phase 2)
│   ├── pages/               # CMS + статические страницы + offline
│   ├── booking/             # публичный flow бронирования
│   ├── components/          # anonymous <x-*> для tenant public (Blade::anonymousComponentPath)
│   ├── sitemap.blade.php    # XML-шаблон для SitemapController
│   └── themes/              # Phase 3+
│       ├── default/
│       ├── moto/
│       └── auto/
├── layouts/
│   └── partials/            # legacy: не используются текущими шаблонами (можно удалить отдельным PR)
├── errors/                  # domain-not-connected и др.
└── filament/                # Filament — не трогать в этом рефакторинге
```

Запрещено: `resources/views/tenants/{slug}/` (дублирование кода по клиентам).

### Controllers (целевые namespaces)

```
App\Http\Controllers\Platform\Marketing\   # опционально: если маркетинг станет не только Route::view
App\Http\Controllers\Tenant\Public\          # Home, Page, Motorcycle, PublicBooking, Sitemap, Robots
```

API без HTML (`BookingController::store`, `LeadController::store`) остаются общими; при желании позже: `App\Http\Controllers\Tenant\Api\`.

### Storage (tenant uploads)

```
storage/app/public/tenants/{tenant_id}/logo/
storage/app/public/tenants/{tenant_id}/hero/
storage/app/public/tenants/{tenant_id}/gallery/
storage/app/public/tenants/{tenant_id}/favicon/
```

Симлинк `php artisan storage:link` уже отдаёт `/storage/...`; URL в шаблонах через `Storage::url()` с префиксом, завязанным на `tenant_id`, не на slug в пути репозитория.

---

## Текущая инвентаризация (на момент документа)

### Platform marketing

- **Routes:** `routes/web.php` — `Route::view` на central domains → теперь `platform.marketing.*`.
- **Views:** `resources/views/platform/marketing/*`, layout `platform/layouts/marketing.blade.php`.
- **Controllers:** отдельных классов нет (только views).

### Tenant public (Phase 2 — выполнено)

- **Controllers (все под `EnsureTenantContext`, namespaces без изменений):**  
  `HomeController`, `PageController`, `MotorcycleController`, `PublicBookingController`, `SitemapController`, `RobotsController` — отдают tenant HTML/XML; в коде используются имена views `tenant.pages.*`, `tenant.booking.*`, `tenant.sitemap`.  
  `BookingController`, `LeadController` — JSON API для форм с tenant-сайта.
- **Views:** `resources/views/tenant/layouts/app.blade.php` (единый layout), `tenant/pages/**`, `tenant/booking/**`, `tenant/components/**`, `tenant/pages/offline.blade.php`, `tenant/sitemap.blade.php`. Совместимостных shim-файлов в старых путях (`pages.*`, `booking.*`, корневой `offline` / `sitemap`) **нет**.
- **Регистрация компонентов:** `AppServiceProvider` — `Blade::anonymousComponentPath(resource_path('views/tenant/components'))`.
- **Filament:** `resources/views/filament/*` — не трогалось.

### Оставшийся техдолг (вне Phase 2)

- **`resources/views/layouts/partials/*`** — не подключены ни одним актуальным шаблоном; при желании удалить отдельным коммитом.
- **Public assets:** `public/manifest.json`, `public/images/icons/*` (PWA) — платформенно-дефолтные; tenant-бренд позже через storage + настройки.

---

## Theme resolution (рекомендации)

1. **Источник `theme_key`:** колонка на `tenants` (например `theme_key` string, default `default`) или JSON в `tenant_settings` / `platform_settings` с наследованием от плана.
2. **Разрешение имени view:** сервис `TenantViewResolver` или хелпер:
   - попытка `tenant.themes.{theme_key}.{logicalName}` (например `tenant.themes.moto.pages.home`);
   - fallback `tenant.themes.default.{logicalName}`;
   - опционально второй fallback на текущий путь `pages.home` на время миграции.
3. **Не хардкодить motolevins:** только `theme_key` из БД; пресет `moto` — один из вариантов темы, не привязка к `slug`.
4. **Filament:** не использовать theme resolver для панелей; только публичный сайт.

---

## Пошаговый план (incremental)

### Phase 1 — инвентаризация (done + этот документ)

- Зафиксированы marketing vs tenant controllers/views.
- Marketing views перенесены в `platform/marketing` (первый безопасный шаг).

### Phase 2 — tenant public paths (выполнено)

- Перенесены views в `tenant/{layouts,pages,booking,components}`, единый layout `tenant.layouts.app`; `home` / динамические `page` / `motorcycle` переведены с `<x-app-layout>` на `@extends` + `@section('content')`.
- Обновлены только строки `view()` / `Route::view()` → `tenant.pages.*`, `tenant.booking.*`, `tenant.sitemap`, `tenant.pages.offline`; URL маршрутов не менялись.
- Удалены legacy entrypoints: `resources/views/layouts/app.blade.php`, `resources/views/components/app-layout.blade.php` (и пустой каталог `components/` у корня views).
- Перенос контроллеров в `App\Http\Controllers\Tenant\Public\` **не делался** (сознательно вне scope Phase 2).

### Phase 3 — theme layer

- Миграция: `tenants.theme_key` (nullable → `default`).
- Ввести `TenantViewResolver` / View composer на `tenant` layout.
- Вынести первую тему: скопировать текущие шаблоны в `tenant/themes/default`, подключить resolver с fallback на `pages.*` до полного переноса.

### Phase 4 — media в storage

- Настроить диск `tenant_public` → `storage/app/public/tenants/{tenant_id}`.
- Миграция файлов Spatie Media Library (или текущих путей) — отдельная команда `tenants:migrate-media` + бэкап.
- Обновить генерацию URL в моделях/ресурсах.

---

## Первый выполненный шаг (фрагменты в репозитории)

- `resources/views/platform/layouts/marketing.blade.php`
- `resources/views/platform/marketing/*.blade.php`
- `routes/web.php` — имена views `platform.marketing.*` (имена маршрутов `platform.*` без изменений).

---

## Риски

- Пакетные тесты/ссылки на старые пути `platform.home` как **view** — обновлять на `platform.marketing.home` при появлении.
- Дублирование layout при частичном переносе tenant — держать один источник правды после phase 2.
- Миграция медиа на проде — только с бэкапом и поэтапной проверкой URL.

## Проверки после шага marketing

- Открыть `https://rentbase.su/` (и www): тот же контент, навигация по `route('platform.*')`.
- `php artisan test tests/Feature/HostRoutingSplitTest.php`.
