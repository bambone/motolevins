# Architecture Decision Record (ADR)

## ADR-001: Shared DB + tenant_id

**Статус:** Принято

**Решение:** Одна БД, колонка `tenant_id` на tenant-scoped таблицах. Трейт `BelongsToTenant` — глобальный scope по `currentTenant()`.

**Последствия:** простой деплой и бэкап; риск утечки при ошибке в scope — митигируется policies и тестами доступа.

---

## ADR-002: users + tenant_user

**Статус:** Принято

**Решение:** Глобальные `users` без `tenant_id`. Связь с клиентом через pivot `tenant_user` (tenant_id, user_id, role, status).

**Последствия:** один пользователь — несколько клиентов; platform staff может иметь membership в нескольких tenant.

---

## ADR-003: Platform Console vs кабинет клиента

**Статус:** Принято

**Решение:** Два Filament panel:

- **Platform Console** — хост из `PLATFORM_HOST` (или конфиг), панель Filament с **корнем пути** на этом хосте (`/login`, `/dashboard`, ресурсы без префикса `/platform`). Ресурсы: клиенты, планы, домены, настройки платформы и т.д.
- **Кабинет клиента** — путь **`/admin`** на домене из `tenant_domains`. Работает только при разрешённом tenant context.

**Последствия:** middleware `ResolveTenantFromDomain`, `EnsureTenantContext`, `EnsureTenantMembership`, `EnsurePlatformAccess`.

---

## ADR-004: Клонирование шаблона сайта

**Статус:** Принято

**Решение:** При создании клиента выбранный `TemplatePreset` клонируется в страницы/секции с `tenant_id`. Дальше контент живёт отдельно от пресета.

---

## ADR-005: Определение tenant по домену

**Статус:** Принято

**Решение:** `ResolveTenantFromDomain` по `Host` ищет активную запись в `tenant_domains`. `CurrentTenantManager` в контексте запроса.

**Последствия:** jobs/уведомления должны явно передавать/восстанавливать tenant.

---

## ADR-006: Разделение маркетинг / бронирование / операции

**Статус:** Принято

**Решение:** Разные сущности и сервисы: CMS/каталог vs bookings/availability/pricing vs будущие операции (договоры, платежи).

---

## ADR-007: Inbound CRM — `crm_request` как aggregate root, LeadStatusHistory как проекция

**Статус:** Принято

**Решение:**

- **`crm_requests` / `CrmRequest`** — единственный долгосрочный вход и **aggregate root** операторского inbound: к нему относятся notes, activity/timeline, mail hooks, assignment, state machine статуса обработки. Не вести параллельный «истинный» timeline на `Lead`, `platform_marketing_leads` или `Booking` для той же заявки.
- **`LeadStatusHistory`** — **transitional projection** для совместимости и возможных отчётов; не primary timeline и не отдельный workflow engine. Смена статуса `Lead` с `crm_request_id` дублируется в `CrmRequestActivity` с пометкой `lead_status_projection` (см. `LeadObserver`).
- **`platform_marketing_leads` / `PlatformMarketingLead`** — **deprecated**, новые записи не создаются; источник — `CreateCrmRequestFromPublicForm` → `CrmRequest`.
- **Публичные формы** обязаны проходить через **`CreateCrmRequestFromPublicForm`** (контекст platform/tenant + submission). Downstream (`Lead` и т.д.) — после CRM-записи.

**Покрытие форм (на момент введения ADR):** под контрактом — маркетинговый `/contact` и tenant `POST /api/leads`. **Backlog миграции на тот же контракт:** публичный поток бронирования (`PublicBookingController`, `BookingController::store` и связанные endpoint’ы), иные формы записи/демо/колбэка — по мере переноса.

**Последствия:** UI операторской истории и уведомлений развивается вокруг CRM-карточки; экран `Lead` остаётся downstream и ссылается на CRM (колонка/подсказка), без дублирования продукта как второго SoT по inbound.
