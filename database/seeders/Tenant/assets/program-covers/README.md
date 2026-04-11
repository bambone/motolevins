# Обложки карточек программ (expert_auto)

Файлы **не хранятся в git**. На проде и локально с R2 они лежат в бакете тенанта: `site/expert_auto/programs/{slug}/card-cover-desktop.webp` и `card-cover-mobile.webp`.

Источник пресетов для всех тенантов с темой `expert_auto` — **системный пул** на публичном диске:

`tenants/_system/themes/expert_auto/program-covers/{имя}.webp`

(список имён — `App\Tenant\Expert\ExpertAutoProgramCoverRegistry`).

## Как заполнить системный пул

1. **Плейсхолдеры (GD)** — один раз на окружении с настроенным `r2-public` (или local public):

   ```bash
   php artisan expert:seed-system-program-covers
   ```

2. **Реальные WebP из репозитория** — только если заведёте bundled-каталог `resources/themes/expert_auto/public/program-covers/` (у многих установок тема — только Blade в `resources/views/tenant/themes/expert_auto/`, тогда этот шаг не нужен):

   ```bash
   php artisan theme:push-system-bundled expert_auto
   ```

## Установка в пространство тенанта

По умолчанию синхронизация **сначала** строит WebP из реальных фото в `site/brand/` (hero, portrait, process-accent, credentials-bg), с кропом под баннер карточки. Если бренд-файлов нет — берутся пресеты из `_system`. Отключить: `EXPERT_AUTO_COVERS_FROM_BRAND=false`.

Обычная команда (бренд → обложки, иначе `_system`):

```bash
php artisan tenant:sync-program-cover-bundle aflyatunov
```

Очистка старых объектов и повторная заливка (опционально):

```bash
php artisan tenant:sync-program-cover-bundle aflyatunov --purge --clear-refs
```

Приёмка (главная тенанта + скачивание 7 desktop WebP с CDN, мин. размер по умолчанию 40 КБ):

```bash
php artisan tenant:accept-program-cover-bundle aflyatunov
```

Сайт отдаёт картинки через `TENANT_STORAGE_PUBLIC_CDN_URL` / публичный URL диска — **не из репозитория**.
