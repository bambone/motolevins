# AF-017 — Инвентарь правок UI-copy (журнал)

Цель: не решать задачу «в одной форме», а иметь прослеживаемый список улучшений терминологии.

## Метод аудита (рекомендуемый)

1. Пройти разделы из списка AF-017 (домены, SEO, CRM, уведомления, медиа, scheduling…).
2. Зафиксировать: файл / экран, термин «до», решение (rename / helper / advanced / скрыть).
3. Внести правку в код и строку в таблицу ниже.

## Итерация 1 (базовый контракт + точечные правки)

| Область | Было (класс проблемы) | Стало / правило |
|--------|------------------------|-----------------|
| Документация | Нет единого контракта | [af-017-admin-terminology-contract.md](af-017-admin-terminology-contract.md), расширен [ux-glossary.md](ux-glossary.md) |
| `TenantResource` (platform) | Англ. `Write mode override`, `Delivery mode override`, bulk `Override: …` | Русские подписи режимов медиа + пояснения; массовые действия без голого «Override» |
| `CrmSharedInfolist` | Голые `utm_*`, `Referrer`, `JSON` | Подписи UTM с расшифровкой; «Сайт-источник»; тех. блок подписан явно |
| `CalendarOccupancyMappingResource` | «Маппинг», `Target`, сырые enum | Русские названия секции и цели; человекочитаемые подписи типов сопоставления и режимов |
| `DomainLocalizationPresetResource`, slug в таблицах | Голый `Slug` | «URL-идентификатор (пресет)» в духе глоссария |
| `BookableServiceResource`, `PlatformBookableServiceResource`, `TenantLocationResource`, `TenantServiceProgramResource` | Колонка `Slug` | «URL-идентификатор» |
| `CustomDomainResource`, `TenantDomainResource` (таблица) | Колонка `SSL` | «SSL-сертификат» |
| `TenantMailLogsRelationManager` | `Throttled` | «Ограничено по лимиту» |
| `DomainTermResource` | «Tenant» в подписи | «Клиент» в пользовательском смысле |
| `PlatformNotificationProvidersPage` | `Bot token`, «kill switch» без пояснения | Русские подписи; каналы с кратким пояснением |
| `CrmSharedWorkspaceSchema` | `Follow-up` | «Следующий контакт» |

Дальнейшие итерации: пройти `SeoFiles.php`, страницы аналитики, уведомлений tenant, builder — по тому же чеклисту AF-017.
