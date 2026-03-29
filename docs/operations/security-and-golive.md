# Безопасность, роли и чеклист перед релизом

Детали установки и URL: [setup-access-deploy.md](setup-access-deploy.md). Индекс документации: [../README.md](../README.md).

---

## Роли

| Роль (Spatie / pivot) | Назначение |
|----------------------|------------|
| `platform_owner`, `platform_admin`, `support_manager` | Platform Console. Права tenant-пермишенов у этих ролей **пустые** — данные клиентов только через `tenant_user`. |
| `tenant_owner`, `tenant_admin`, `booking_manager`, `fleet_manager`, `content_manager`, `operator` | Роль в `tenant_user.pivot.role` + маппинг в `TenantPivotPermissions` для `manage_*` в кабинете клиента. |

**Legacy:** `super_admin` в БД может оставаться, но **не открывает Filament**. Вход — только `platform_*` и/или активный `tenant_user`. Роли `admin` / `manager` / `content_manager` в Spatie — совместимость; **вход в кабинет клиента** требует pivot в `tenant_user`.

Подписи в UI: `App\Filament\Support\RoleLabels`.

---

## Зоны

| Зона | Host | Доступ |
|------|------|--------|
| Platform Website | `config('app.platform_host')` | Публично, без `EnsureTenantContext`, без Filament tenant. |
| Platform Console | тот же host, Filament с корня (`/login`, `/dashboard`) | `EnsurePlatformAccess`: platform host + platform-роль. |
| Сайт клиента | домен из `tenant_domains` | `EnsureTenantContext` + публичные маршруты. |
| Кабинет клиента | тот же домен, `/admin` | `canAccessPanel(admin)` + `EnsureTenantMembership` + `Gate::before` по pivot. |

## Матрица role × зона

| role | Platform Website | Platform Console | Сайт клиента | Кабинет клиента |
|------|------------------|------------------|--------------|-----------------|
| platform_* | да (публично) | да | нет | только при **active** `tenant_user` |
| tenant pivot | да (как гость) | нет | нет | да (host + pivot) |
| без ролей / blocked | да (публично) | нет | нет | нет |

## Три слоя проверки

1. `User::canAccessPanel(Panel)`
2. Middleware: `EnsurePlatformAccess`, `EnsureTenantContext`, `EnsureTenantMembership`
3. Policies + `Gate::before` (tenant) для `manage_*` / экспорта

## Global Search

В обеих панелях **выключен** (`globalSearch(false)`), пока нет tenant-safe провайдеров.

---

## Чеклист перед релизом (GO LIVE)

**Версия / дата прогона:** _заполнить_

### Доступ

- [ ] Пользователь **только с tenant membership** не открывает Platform Console (прямой URL консоли на `PLATFORM_HOST` после логина — отказ / 403).
- [ ] Пользователь с **platform_* без `tenant_user`** не открывает кабинет клиента на домене тенанта (`/admin`).
- [ ] Пользователь tenant **A** не видит данные tenant **B** (прямые URL к записям, экспорт, виджеты).
- [ ] `User.status = blocked` — не входит **ни** в Platform, **ни** в кабинет клиента.
- [ ] На **platform host** без platform-роли после логина → 403 на защищённых маршрутах панели.
- [ ] Неизвестный домен (не platform, не в `tenant_domains`) → страница «Домен не подключён» / ожидаемый 404.

### Host spoofing

- [ ] Запрос с подменённым `Host` не должен резолвить чужой tenant при корректных `TrustProxies` и реальном клиентском host за прокси. Зафиксировать поведение для вашей среды (Nginx, Cloudflare).

### Поиск и экспорт

- [ ] Global Search **выключен** в обеих панелях.
- [ ] Экспорт не отдаёт данные другого tenant.

### Редирект после логина

- [ ] Platform → `/dashboard`.
- [ ] Только tenant → `/admin`.
- [ ] Platform + tenant, вход с `/admin/login` → редирект на Platform Console (см. [setup-access-deploy.md](setup-access-deploy.md)).

### Автотесты

- [ ] `php artisan test --filter=AccessControl` (или полный suite) в CI.

### UI контекста

- [ ] В кабинете клиента в шапке видно **текущего клиента** (бренд / имя).
- [ ] В Platform Console понятно, что это консоль платформы.

### Ручной прогон (кратко)

| Сценарий | Проверка |
|----------|----------|
| Platform host | `/login` → `/dashboard`, вход platform-пользователем. |
| Tenant host | `/admin`, вход с `tenant_user`. |
| Blocked | не пускает никуда. |
| Оба контекста | с `/admin/login` → Platform при наличии platform-роли. |
| Unknown host | «Домен не подключён». |

После прогона — дата в шапке чеклиста.
