<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewImportSourceResource\Pages;

use App\Filament\Tenant\Resources\ReviewImportSourceResource;
use App\Reviews\Import\ReviewImportSourceStatus;
use App\Services\Reviews\Imports\ReviewSourceDetector;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReviewImportSource extends CreateRecord
{
    protected static string $resource = ReviewImportSourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['provider'] ?? '') === 'auto') {
            $data['provider'] = ReviewSourceDetector::providerFromUrl((string) ($data['source_url'] ?? ''));
        }

        $data['created_by'] = Auth::id();

        if (($data['provider'] ?? '') === 'two_gis') {
            $data['status'] = ReviewImportSourceStatus::UNSUPPORTED;
        } elseif (($data['provider'] ?? '') === 'yandex_maps') {
            $data['status'] = ReviewImportSourceStatus::NEEDS_AUTH;
        } else {
            $data['status'] = ReviewImportSourceStatus::DRAFT;
        }

        return $data;
    }
}
