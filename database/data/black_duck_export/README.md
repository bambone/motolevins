# Экспорт медиа Black Duck для curated-импорта

## Папка `--source`

Положите оригиналы фото/видео и в корне каталога файл **`curated-manifest.json`** (схема v3, см. [`../black_duck_media_catalog.example.json`](../black_duck_media_catalog.example.json)).

Импорт:

```bash
php artisan tenant:black-duck:import-curated-proof blackduck --source=C:\path\to\export --dry-run
php artisan tenant:black-duck:import-curated-proof blackduck --source=C:\path\to\export --force
php artisan tenant:black-duck:refresh-content blackduck --force
```

Внешние URL (Яндекс.Карты и т.п.) — только **`source_ref`** в JSON; в `logical_path` должны быть ключи под `site/brand/proof/...` после копирования файлов импортом.

## Инвентаризация

Шаблон таблицы: [`black_duck_operator_inventory.template.csv`](black_duck_operator_inventory.template.csv) — колонки: файл, услуга/slug, тип, роли-кандидаты, качество, дубликат.

## Квоты для приёмки `/raboty` (ориентир по `service_slug` и `tags`)

| Категория | Целевое число элементов в портфолио |
|-----------|-------------------------------------|
| PPF / бронеплёнка | 4–6 |
| Химчистка салона / интерьер | 4–6 |
| Полировка / керамика | 4–5 |
| PDR / сколы / локальное восстановление | 3–4 |
| Предпродажная подготовка | 2–3 |
| Подкапотка / диски / антидождь / прочее | 2–4 |

**Минимум для приёмки:** 12 качественных элементов в сетке (см. `BlackDuckServiceRegistry::MIN_WORKS_PORTFOLIO_ITEMS_ACCEPTANCE`).

## Услуги вне текущего реестра

Озонирование, локальная полировка, полировка фар, твёрдый воск: канонические будущие slug и ценовые якоря заданы в `BlackDuckServiceRegistry::deferredTzServiceSlugs()` и `publicPriceAnchorForSlug()`; **посадочных страниц пока нет** — при вводе в матрицу добавить строку в `rowsInner()`, bootstrap, меню и при необходимости booking.
