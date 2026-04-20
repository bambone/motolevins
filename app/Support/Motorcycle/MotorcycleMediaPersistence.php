<?php

declare(strict_types=1);

namespace App\Support\Motorcycle;

use App\Filament\Forms\Components\TenantSpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

final class MotorcycleMediaPersistence
{
    /**
     * После изменения состояния FileUpload: синхронизировать медиатеку для существующей записи.
     */
    public static function persistAfterUploadStateChange(TenantSpatieMediaLibraryFileUpload $component): void
    {
        $record = $component->getRecord();
        if (! $record instanceof Model || ! $record->exists) {
            return;
        }

        $rawState = $component->getRawState() ?? [];
        $hadTemporaryUpload = collect($rawState)->contains(
            fn (mixed $file): bool => $file instanceof TemporaryUploadedFile
        );

        try {
            $component->deleteAbandonedFiles();
            $component->saveUploadedFiles();
        } catch (ValidationException $e) {
            throw $e;
        } catch (UnableToRetrieveMetadata | UnableToCheckFileExistence $e) {
            Notification::make()
                ->title('Файл загрузки недоступен')
                ->body('Повторите загрузку изображения. Если ошибка повторяется, проверьте, что все запросы Livewire попадают на один сервер с общим диском для livewire-tmp.')
                ->danger()
                ->persistent()
                ->send();

            return;
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Не удалось сохранить файл в хранилище')
                ->body(config('app.debug') ? $e->getMessage() : 'Проверьте MEDIA_DISK, очередь конверсий и логи сервера.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if ($hadTemporaryUpload) {
            Notification::make()
                ->title('Изображение сохранено')
                ->success()
                ->duration(3500)
                ->send();
        }
    }
}
