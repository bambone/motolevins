<?php

declare(strict_types=1);

namespace App\Admin\Lifecycle;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Централизованное удаление для Filament: понятные сообщения пользователю,
 * логирование, исключение «двойных» уведомлений при FK через {@see Halt}.
 */
final class AdminDeleteExecutor
{
    public const LOG_CHANNEL = 'single';

    /**
     * Для {@see DeleteAction::process()} — вернуть bool или бросить {@see Halt} после уведомления.
     *
     * @param  array<string, mixed>  $context
     */
    public static function deleteOneForFilamentProcess(Model $record, array $context = []): bool
    {
        $context = self::mergeContext($record, $context);

        try {
            $deleted = $record->delete();

            if ($deleted === false) {
                self::logFailure($context, 'model_delete_returned_false');

                return false;
            }

            self::logSuccess($context);

            return true;
        } catch (QueryException $e) {
            self::notifyQueryException($e, $context);
            self::logQueryException($e, $context);
            throw new Halt;
        } catch (Throwable $e) {
            self::notifyGenericFailure($e, $context);
            report($e);
            throw new Halt;
        }
    }

    /**
     * Удаление одной записи в bulk-цикле: без Halt, только bool + reportBulkProcessingFailure снаружи.
     * Параметр {@see $mayNotifyUser} по ссылке: после первого уведомления об ошибке БД/исключении
     * следующие строки bulk не дублируют toast (как в стандартном Filament).
     *
     * @param  array<string, mixed>  $context
     */
    public static function tryDeleteOneForBulk(Model $record, array $context = [], bool &$mayNotifyUser = true): bool
    {
        $context = self::mergeContext($record, $context);

        try {
            $deleted = $record->delete();

            if ($deleted === false) {
                self::logFailure($context, 'model_delete_returned_false');

                return false;
            }

            self::logSuccess($context);

            return true;
        } catch (QueryException $e) {
            if ($mayNotifyUser) {
                self::notifyQueryException($e, $context);
                $mayNotifyUser = false;
            }
            self::logQueryException($e, $context);

            return false;
        } catch (Throwable $e) {
            if ($mayNotifyUser) {
                self::notifyGenericFailure($e, $context);
                $mayNotifyUser = false;
            }
            report($e);

            return false;
        }
    }

    /**
     * Удаление через query (без выборки записей): обёртка над массовым delete.
     *
     * @param  array<string, mixed>  $context
     */
    public static function runQueryDelete(callable $run, array $context = []): int
    {
        try {
            $count = (int) $run();

            self::logSuccess($context + ['query_delete_count' => $count]);

            return $count;
        } catch (QueryException $e) {
            self::notifyQueryException($e, $context);
            self::logQueryException($e, $context);
            throw new Halt;
        } catch (Throwable $e) {
            self::notifyGenericFailure($e, $context);
            report($e);
            throw new Halt;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function mergeContext(Model $record, array $context): array
    {
        $base = [
            'model' => $record::class,
            'key' => $record->getKey(),
        ];

        if (method_exists($record, 'getAttribute') && $record->getAttribute('tenant_id') !== null) {
            $base['tenant_id'] = $record->getAttribute('tenant_id');
        }

        return array_merge($base, $context);
    }

    public static function userMessageForQueryException(QueryException $e): string
    {
        $errorInfo = $e->errorInfo ?? null;
        $sqlState = is_array($errorInfo) ? (string) ($errorInfo[0] ?? '') : '';

        if ($sqlState === '23000' || str_contains($e->getMessage(), 'foreign key') || str_contains($e->getMessage(), 'FOREIGN KEY')) {
            return 'Нельзя удалить запись: есть связанные данные (ограничения в базе). Сначала удалите или отвяжите зависимости, либо используйте архивирование/скрытие, если оно доступно для этой сущности.';
        }

        if ($sqlState === 'HY000' && str_contains($e->getMessage(), 'Duplicate')) {
            return 'Нельзя удалить запись: операция нарушит уникальность данных.';
        }

        return 'Не удалось удалить запись из-за ошибки базы данных. Обратитесь к поддержке, если проблема повторяется.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function notifyQueryException(QueryException $e, array $context): void
    {
        Notification::make()
            ->danger()
            ->title('Не удалось удалить')
            ->body(self::userMessageForQueryException($e))
            ->persistent()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function notifyGenericFailure(Throwable $e, array $context): void
    {
        Notification::make()
            ->danger()
            ->title('Не удалось удалить')
            ->body('Произошла ошибка при удалении. Попробуйте ещё раз или обратитесь в поддержку.')
            ->persistent()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function logSuccess(array $context): void
    {
        Log::channel(self::LOG_CHANNEL)->info('admin_delete.success', $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function logFailure(array $context, string $reason): void
    {
        Log::channel(self::LOG_CHANNEL)->notice('admin_delete.blocked', $context + ['reason' => $reason]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function logQueryException(QueryException $e, array $context): void
    {
        Log::channel(self::LOG_CHANNEL)->warning('admin_delete.query_exception', $context + [
            'exception_class' => $e::class,
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ]);
    }
}
