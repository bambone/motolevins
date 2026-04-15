<?php

declare(strict_types=1);

namespace App\Filament\Shared\Lifecycle;

use App\Admin\Lifecycle\AdminDeleteExecutor;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Единая точка подключения delete-логики Filament к {@see AdminDeleteExecutor}.
 */
final class AdminFilamentDelete
{
    /**
     * Таблица: массовое удаление с теми же правилами, что и у Filament по умолчанию,
     * плюс обработка ошибок БД и логирование.
     *
     * @param  array<string, mixed>  $context
     */
    public static function makeBulkDeleteAction(array $context = []): DeleteBulkAction
    {
        $context['entry'] = $context['entry'] ?? 'filament.table.bulk_delete';

        return DeleteBulkAction::make()
            ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records) use ($context): void {
                if (! $action->shouldFetchSelectedRecords()) {
                    try {
                        $count = AdminDeleteExecutor::runQueryDelete(
                            static fn (): int => (int) $action->getSelectedRecordsQuery()->delete(),
                            $context + ['query' => true],
                        );
                        $action->reportBulkProcessingSuccessfulRecordsCount($count);
                    } catch (Halt) {
                        $action->reportCompleteBulkProcessingFailure();
                    }

                    return;
                }

                $mayNotifyUser = true;

                $records->each(function (Model $record) use ($action, &$mayNotifyUser, $context): void {
                    $ok = AdminDeleteExecutor::tryDeleteOneForBulk($record, $context, $mayNotifyUser);

                    if ($ok) {
                        return;
                    }

                    $action->reportBulkProcessingFailure();
                });
            });
    }

    /**
     * Обычная кнопка «Удалить» в строке таблицы или на странице редактирования.
     *
     * @param  array<string, mixed>  $context
     */
    public static function configureTableDeleteAction(DeleteAction $action, array $context = []): DeleteAction
    {
        $context['entry'] = $context['entry'] ?? 'filament.table.delete';

        return $action->using(static fn (Model $record): bool => AdminDeleteExecutor::deleteOneForFilamentProcess($record, $context));
    }
}
