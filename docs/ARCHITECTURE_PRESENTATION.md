# Presentation layer: platform marketing, tenant engine, themes

Один Laravel-приложение, один `public/`; разделение по **хостам** (уже сделано) и по **каталогам views/controllers**; **Phase 3 (theme layer)** для публичного tenant-сайта — выполнена в минимальном безопасном объёме.

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
│   └── themes/              # Phase 3 — пресеты тем (не по slug)
│       ├── default/pages/   # тонкие обёртки → engine (`tenant.pages.*`)
│       ├── moto/pages/      # точечные override (пример: home)
│       └── auto/pages/      # зарезервировано под пресет
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

### Tenant public (Phase 2 — выполнено; Phase 3 / 3.5 — resolver + helper)

- **Controllers (все под `EnsureTenantContext`, namespaces без изменений):**  
  Публичные HTML-страницы используют хелпер **`tenant_view($logical, $data)`** ([`app/helpers.php`](app/helpers.php)), который внутри вызывает **`TenantViewResolver`**. Логические имена: `pages.home`, `pages.page`, `pages.motorcycle`, `pages.contacts`, `pages.faq`, `pages.about`, `booking.index` (остальные шаги booking flow по-прежнему на прямых путях `tenant.booking.*`).  
  `TenantPublicPageController` — allowlist для `/contacts`, `/faq`, `/about`.  
  `SitemapController`, `RobotsController` и прочие `Route::view` (terms, prices, offline, …) **пока без** theme resolver — намеренно, чтобы не раздувать риск в одном PR.  
  `BookingController`, `LeadController` — JSON API для форм с tenant-сайта.
- **Views:** `resources/views/tenant/layouts/app.blade.php` (единый layout), `tenant/pages/**`, `tenant/booking/**`, `tenant/components/**`, `tenant/pages/offline.blade.php`, `tenant/sitemap.blade.php`, плюс **`tenant/themes/{theme_key}/**`** для пресетов. Совместимостных shim-файлов в старых путях (`pages.*`, `booking.*`, корневой `offline` / `sitemap`) **нет**.
- **`theme_key`:** колонка на `tenants`, default `default`. Тема задаётся **только** этим полем; **не** выводится из `slug` и **не** требует каталогов `resources/views/tenants/{slug}/`.
- **Регистрация компонентов:** `AppServiceProvider` — `Blade::anonymousComponentPath(resource_path('views/tenant/components'))`.
- **Filament:** в Platform Console у клиента (`TenantResource`) редактируется **`theme_key`** (whitelist: `default`, `moto`, `auto`). В Tenant Admin → **Настройки** — загрузка logo / favicon / hero в `storage/app/public/tenants/{tenant_id}/…` плюс legacy URL в `TenantSetting`.

### Оставшийся техдолг (вне Phase 2)

- **`resources/views/layouts/partials/*`** — не подключены ни одним актуальным шаблоном; при желании удалить отдельным коммитом.
- **Public assets:** `public/manifest.json`, `public/images/icons/*` (PWA) — платформенно-дефолтные; при отсутствии tenant favicon layout не ломает manifest.

---

## Theme resolution (Phase 3 — реализовано)

**Контракт логического имени:** строка без префикса `tenant.`, только сегменты `[a-z0-9_-]+`, разделённые `.`, без `..` и без заглавных букв; длина ограничена. Примеры: `pages.home`, `booking.index`.

**Цепочка fallback (`TenantViewResolver::resolve`):**

1. `tenant.themes.{theme_key}.{logical}` — пресет из `Tenant::themeKey()` (нормализация небезопасных/пустых значений → `default`);
2. `tenant.themes.default.{logical}` — общий слой темы по умолчанию;
3. `tenant.{logical}` — «движок» (текущие `tenant.pages.*` и т.д.).

Если шаг 1 совпадает с шагом 2 (когда активная тема уже `default`), дубликаты убираются; первый существующий view по цепочке побеждает.

**Регистрация:** `TenantViewResolver` — singleton в `AppServiceProvider`.

**Отладка:** если `TENANCY_LOG_VIEW_RESOLUTION=true` в `.env` или включён `APP_DEBUG`, в лог пишется одна строка `tenant_view_resolved` (tenant id, сырой и нормализованный `theme_key`, logical name, итоговый blade, список пропущенных кандидатов).

**Filament / админки:** resolver только на публичном сайте тенанта.

**Тесты:** `tests/Unit/TenantViewResolverTest.php`, `tests/Feature/TenantThemeViewResolverTest.php`, `tests/Feature/FilamentPanelRoutesTest.php`.

### Tenant branding / media (Phase 4 — первый инкремент)

- **Ключи `TenantSetting`:** `branding.logo_path`, `branding.favicon_path`, `branding.hero_path` — относительный путь внутри диска `public` (как возвращает Filament `FileUpload`). Legacy URL: `branding.logo`, `branding.favicon`, `branding.hero` — если путь пустой, используется URL.
- **URL:** хелперы `tenant_branding_logo_url()`, `tenant_branding_favicon_url()`, `tenant_branding_hero_url()` и низкоуровневый `tenant_branding_asset_url($path, $legacyUrl)`; в `View::composer` в массив `branding` попадают уже **разрешённые** строки URL (`logo`, `favicon`, `hero_image`).
- **Миграция со старых данных:** массовый перенос файлов из `public/` не выполняется; новые загрузки идут в storage. Старые внешние URL в настройках продолжают работать.

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

### Phase 3 — theme layer (выполнено, первый инкремент)

- Миграция `2026_03_28_150000_add_theme_key_to_tenants.php`: `tenants.theme_key` string(64), default `default`, не nullable.
- Сервис `App\Services\Tenancy\TenantViewResolver`; на модели `Tenant` — `themeKey()` и `theme_key` в `$fillable`.
- Каталоги: `tenant/themes/default/pages/home.blade.php` (тонкая обёртка `@include('tenant.pages.home')`), `tenant/themes/moto/pages/home.blade.php` (POC override + маркер для тестов), `tenant/themes/auto/pages/` (заготовка).
- Подключён resolver только в `HomeController`, `PageController`, `MotorcycleController`; остальные маршруты и URL без изменений.

### Phase 4 — media в storage (в работе)

- Реализовано: каталоги `storage/app/public/tenants/{tenant_id}/{logo,favicon,hero}/`, загрузка из Tenant Admin → Настройки, хелперы URL, favicon в `tenant.layouts.app` при наличии значения.
- **Дальше (backlog):** галерея как отдельный пресет; команда `tenants:migrate-media` для переноса legacy-файлов из `public/`; при необходимости единый диск `tenant_public` в `config/filesystems.php`.

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

## Проверки после Phase 3 (themes)

- `php artisan migrate` (колонка `theme_key`).
- `php artisan test tests/Unit/TenantViewResolverTest.php tests/Feature/TenantThemeViewResolverTest.php tests/Feature/FilamentPanelRoutesTest.php`.
- Для ручной проверки: в Platform Console задать клиенту тему **Мото** — главная должна содержать скрытый маркер `data-tenant-theme="moto"`; `/contacts`, `/faq`, `/about` без маркера, если нет override в `tenant/themes/moto/pages/`.

## Проверки после branding storage

- `php artisan storage:link` на сервере (симлинк `public/storage` → `storage/app/public`).
- Загрузить logo в Tenant Admin → Настройки; открыть публичный сайт — шапка должна показать файл с URL вида `/storage/tenants/{id}/logo/...`.
