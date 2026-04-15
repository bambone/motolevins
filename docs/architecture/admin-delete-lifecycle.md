# Жизненный цикл удаления в админке (AF-016)

## Контракт в коде

- **`App\Admin\Lifecycle\AdminDeleteExecutor`** — единая точка для удаления из Filament:
  - одиночное действие: `deleteOneForFilamentProcess()` (ошибки БД → понятное уведомление + `Halt`, чтобы не дублировать стандартный failure toast Filament);
  - bulk: `tryDeleteOneForBulk()` с одним пользовательским toast на первую ошибку БД в пачке;
  - массовый `query()->delete()`: `runQueryDelete()`;
  - логи: `admin_delete.success`, `admin_delete.blocked`, `admin_delete.query_exception` в канале `single` (`storage/logs/laravel.log`).
- **`App\Filament\Shared\Lifecycle\AdminFilamentDelete`**:
  - `makeBulkDeleteAction()` — подмена стандартного `DeleteBulkAction::make()`;
  - `configureTableDeleteAction()` — обёртка для `DeleteAction::make()` в таблице и на странице редактирования.

## Матрица (черновик)

| Область | Политика | Примечание |
|--------|----------|------------|
| Большинство ресурсов с таблицей | hard delete + единый bulk | Через `AdminFilamentDelete::makeBulkDeleteAction()` |
| `TenantDomain` (platform) | hard delete + бизнес-проверка «не последний домен» | Сохранена кастомная логика bulk; удаление строки через executor |
| `Page` (tenant), slug `home` | delete **запрещён** | `PagePolicy::delete` / `forceDelete`; кнопка скрыта в UI |

Дальнейшее заполнение матрицы (soft delete, архив, запрет по ролям) — по доменным правилам, с опорой на тот же executor.
