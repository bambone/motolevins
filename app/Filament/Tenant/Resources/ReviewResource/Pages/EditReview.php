<?php

namespace App\Filament\Tenant\Resources\ReviewResource\Pages;

use App\Filament\Tenant\Resources\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\EditRecord;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['rating'] = ($data['rating'] ?? null) === null ? '' : (string) (int) $data['rating'];

        if (blank($data['body'] ?? null) && $this->record instanceof Review) {
            // Временный UX до полного backfill: подставляем legacy в форму; при сохранении текст окажется в `body`.
            $legacy = $this->record->legacyFullTextRawForRead();
            if (filled($legacy)) {
                $data['body'] = $legacy;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! isset($data['rating']) || $data['rating'] === '' || $data['rating'] === null) {
            $data['rating'] = null;
        } else {
            $data['rating'] = max(1, min(5, (int) $data['rating']));
        }

        return $data;
    }
}
