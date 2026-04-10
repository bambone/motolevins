# Scheduling / Calendars / Occupancy

Отдельный домен от арендной `availability_calendar` по `rental_unit`. Таблицы `scheduling_*`, `bookable_services`, `calendar_*`, `appointment_holds`, `manual_busy_blocks`, `external_busy_blocks`.

## Публичное API (tenant host)

- `GET /api/tenant/scheduling/bookable-services` — список услуг с включённым `scheduling_enabled` на связанной цели.
- `GET /api/tenant/scheduling/bookable-services/{id}/slots?from=&to=` — слоты (UTC ISO в ответе) и массив `warnings` (коды: `scheduling_calendar_integration_error`, `scheduling_external_busy_stale`).
- `POST /api/tenant/scheduling/holds` — мягкий hold (тело: `bookable_service_id`, `scheduling_resource_id`, `starts_at_utc`, `ends_at_utc`).
- `POST /api/tenant/scheduling/submit` — заявка в CRM (`tenant_appointment`) и обновление hold.

Требуется `tenants.scheduling_module_enabled` и ability `manage_scheduling` для доступа в Filament, не для публичного API.

## Filament tenant

Раздел «Запись и расписание»: услуги, ресурсы, цели, правила/исключения, ручные блокировки, подключения календарей, маппинги, **отладка слотов**, **превью занятости**, **состояние синхронизации**. При создании услуги автоматически создаётся строка `scheduling_targets` типа `bookable_service`.

Публичные слоты и holds учитывают `scheduling_integration_error_policy` (блокировка при `block_scheduling` и непустом `last_error` у активного подключения) и `stale_busy_policy` (при `block_new_slots` и протухшем sync — пустые слоты / отказ hold).

## Интеграции календарей

Адаптеры: `App\Scheduling\Calendar\CalendarProviderAdapter`; реестр `CalendarAdapterRegistry` (Google / CalDAV классы — каркас, HTTP в следующих итерациях). Конфиг: `config/scheduling.php`, переменные `SCHEDULING_*`. OAuth-заглушка маршрутов: `scheduling.oauth.google.redirect` / `callback` → `App\Http\Controllers\Scheduling\GoogleCalendarOAuthController`.

Очередь: `App\Jobs\Scheduling\SyncCalendarBusyJob`; в списке подключений Filament — действие «Синхр. busy».

## Запись во внешний календарь

`App\Scheduling\WriteCalendarResolver` — порядок: услуга → цель → ресурс → tenant default → **platform** (`PlatformSetting` ключ `scheduling.default_write_calendar_subscription_id`, тип integer в БД настроек).

## Lifecycle внешних событий (`calendar_event_links`)

Код-заготовка: `App\Scheduling\Calendar\ExternalCalendarEventLifecycle`. Продуктовая матрица (реализация по этапам):

| Триггер | Типичные действия с событием во внешнем календаре |
|--------|-----------------------------------------------------|
| Отмена заявки / hold | `delete` или `update` (отменён), либо `detach` со статусом link |
| Перенос слота | `update` времени / `delete` + создать новое |
| Переназначение ресурса | `update` календаря назначения или `detach` + новая запись |
| Истечение неподтверждённой CRM-заявки | `delete` или `leave` + пометка link |
| Удаление оператором в CRM | по политике: `delete` / `leave` / `orphaned` |

Точное сопоставление «триггер → действие» фиксируется в политике продукта и может быть вынесено в enum при реализации.

## Bridge аренды

`App\Scheduling\Occupancy\RentalAvailabilityBridge` — `external_busy_effect`, политика устаревшего busy (`stale_busy_policy`).

`App\Services\AvailabilityService` (`isAvailable`, `getConflicts`) подмешивает синтетические пересечения из `external_busy_blocks`, если для `rental_unit` есть активный `scheduling_target` с `external_busy_enabled` и жёстким эффектом (`hard_block`), либо если политика устаревшего busy требует блокировать при «протухшем» sync подписок (`stale_after_seconds` / `last_successful_sync_at`). Для `soft_warning` пересечения доступны отдельно через `getExternalSchedulingSoftWarnings()` (без блокировки `isAvailable`).

Коммерческий UI: при выключенных `calendar_integrations_enabled` в кабинете показывается баннер (gating не в доменных сервисах; запись наружу режется в `WriteCalendarResolver` / entitlements).
