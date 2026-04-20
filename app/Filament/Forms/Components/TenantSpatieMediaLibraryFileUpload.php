<?php

namespace App\Filament\Forms\Components;

use App\Models\Tenant;
use App\Support\Storage\TenantStorage;
use App\Support\Storage\TenantStorageDisks;
use App\Tenant\StorageQuota\StorageQuotaExceededException;
use App\Tenant\StorageQuota\TenantStorageQuotaService;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Как {@see SpatieMediaLibraryFileUpload}, но URL для уже сохранённых файлов ведёт на same-origin stream,
 * иначе fetch() превью в админке блокируется CORS при {@code AWS_URL} / R2 на другом хосте.
 */
final class TenantSpatieMediaLibraryFileUpload extends SpatieMediaLibraryFileUpload
{
    public function maxSize(int|Closure|null $size): static
    {
        $this->maxSize = $size;

        $this->rule(static function (BaseFileUpload $component): Closure {
            return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $maxKb = $component->getMaxSize();
                if (! filled($maxKb) || ! $value instanceof TemporaryUploadedFile) {
                    return;
                }

                try {
                    $sizeKb = $value->getSize() / 1024;
                } catch (UnableToRetrieveMetadata) {
                    $fail('Временный файл загрузки недоступен. Повторите загрузку изображения.');

                    return;
                }

                if ($sizeKb > $maxKb) {
                    $fail(__(
                        'validation.max.file',
                        ['attribute' => $component->getValidationAttribute(), 'max' => $maxKb],
                    ));
                }
            };
        });

        return $this;
    }

    public function minSize(int|Closure|null $size): static
    {
        $this->minSize = $size;

        $this->rule(static function (BaseFileUpload $component): Closure {
            return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $minKb = $component->getMinSize();
                if (! filled($minKb) || ! $value instanceof TemporaryUploadedFile) {
                    return;
                }

                try {
                    $sizeKb = $value->getSize() / 1024;
                } catch (UnableToRetrieveMetadata) {
                    $fail('Временный файл загрузки недоступен. Повторите загрузку изображения.');

                    return;
                }

                if ($sizeKb < $minKb) {
                    $fail(__(
                        'validation.min.file',
                        ['attribute' => $component->getValidationAttribute(), 'min' => $minKb],
                    ));
                }
            };
        });

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            $unavailable = fn (): ValidationException => ValidationException::withMessages([
                $component->getStatePath() => ['Временный файл загрузки недоступен. Повторите загрузку изображения.'],
            ]);

            try {
                try {
                    if (! $file->exists()) {
                        throw $unavailable();
                    }
                } catch (UnableToCheckFileExistence) {
                    throw $unavailable();
                }

                $tenant = currentTenant();
                if ($tenant instanceof Tenant && TenantStorageQuotaService::isQuotaEnforcementActive()) {
                    try {
                        $bytes = (int) $file->getSize();
                    } catch (UnableToRetrieveMetadata) {
                        throw $unavailable();
                    }

                    try {
                        app(TenantStorageQuotaService::class)->assertCanStoreBytes($tenant, $bytes, 'media_upload');
                    } catch (StorageQuotaExceededException $e) {
                        throw ValidationException::withMessages([
                            $component->getStatePath() => [$e->getMessage()],
                        ]);
                    }
                }

                if (! $record instanceof HasMedia) {
                    throw ValidationException::withMessages([
                        $component->getStatePath() => ['Невозможно сохранить файл: запись недоступна или не поддерживает медиабиблиотеку.'],
                    ]);
                }

                /** @var Model&HasMedia $record */
                $mediaAdder = $record->addMediaFromString($file->get());

                $filename = $component->getUploadedFileNameForStorage($file);

                try {
                    $mimeType = $file->getMimeType();
                } catch (UnableToRetrieveMetadata) {
                    throw $unavailable();
                }

                $uploadHeaders = ['ContentType' => $mimeType];
                $diskName = $component->getDiskName();
                if ($diskName !== '' && $diskName !== TenantStorageDisks::privateDiskName()) {
                    $uploadDisk = Storage::disk($diskName);
                    $uploadHeaders = TenantStorage::mergedOptionsForPublicObjectWrite($uploadDisk, $uploadHeaders);
                }

                $media = $mediaAdder
                    ->addCustomHeaders([...$uploadHeaders, ...$component->getCustomHeaders()])
                    ->usingFileName($filename)
                    ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                    ->withCustomProperties($component->getCustomProperties($file))
                    ->withManipulations($component->getManipulations())
                    ->withResponsiveImagesIf($component->hasResponsiveImages())
                    ->withProperties($component->getProperties())
                    ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

                return $media->getAttributeValue('uuid');
            } catch (ValidationException $e) {
                throw $e;
            } catch (UnableToRetrieveMetadata) {
                throw $unavailable();
            }
        });

        $this->getUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
            if (! $component->getRecord()) {
                return null;
            }

            /** @var ?Media $media */
            $media = $component->getRecord()->getRelationValue('media')->firstWhere('uuid', $file);

            $url = null;

            if ($component->getVisibility() === 'private') {
                $conversion = $component->getConversion();

                try {
                    $url = $media?->getTemporaryUrl(
                        now()->addMinutes(30)->endOfHour(),
                        (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '',
                    );
                } catch (Throwable) {
                }
            }

            if ($url === null && $component->getVisibility() === 'public' && $media !== null) {
                $conversion = $component->getConversion();
                $conv = (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '';
                $url = filament_tenant_spatie_media_preview_url($media, $conv);
            }

            if ($url === null && $component->getConversion() && $media?->hasGeneratedConversion($component->getConversion())) {
                $url = $media->getUrl($component->getConversion());
            }

            $url ??= $media?->getUrl();

            return [
                'name' => $media?->getAttributeValue('name') ?? $media?->getAttributeValue('file_name'),
                'size' => $media?->getAttributeValue('size'),
                'type' => $media?->getAttributeValue('mime_type'),
                'url' => $url,
            ];
        });
    }
}
