# AF-018 — Журнал пустых состояний

## Итерация 1

- Введён хелпер `App\Filament\Support\AdminEmptyState` и контракт в документации.
- Обновлены таблицы (первичная пустота + подсказка про фильтры там, где они есть):

| Ресурс | Заголовок / смысл |
|--------|-------------------|
| `ReviewResource` | Отзывы: CTA «Добавить отзыв» |
| `FaqResource` | FAQ: CTA «Добавить вопрос» |
| `CrmRequestResource` (tenant + platform) | Заявки: автопоток, фильтры |
| `NotificationSubscriptionResource` | Правила уведомлений + ссылка на получателей |
| `BookableServiceResource` | Услуги записи |
| `PlatformBookableServiceResource` | Услуги платформы |
| `DomainLocalizationPresetResource` | Пресеты терминологии |
| `DomainTermResource` | Системные термины |
| `CalendarOccupancyMappingResource` | Сопоставления календаря |
| `CustomDomainResource` | Свой домен: заголовок, DNS/проверка, подсказка про поиск, CTA «Добавить домен» |

## Итерация 2

- `CalendarOccupancyMappingResource`: первичная пустота, пояснение про календарь ↔ расписание, подсказка про фильтры, CTA «Добавить сопоставление».
- `CustomDomainResource`: единый `AdminEmptyState`, иконка, CTA «Добавить домен», текст про DNS без потери смысла прежней версии.

## Дальше (по чеклисту AF-018)

Relation managers, виджеты дашборда, отдельные страницы настроек без таблицы, сценарии «только по фильтру» — при необходимости кастомный empty state в Livewire.
