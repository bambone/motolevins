<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns;

use App\Support\FilamentTipTapDocumentSanitizer;
use Filament\Schemas\Schema;

/**
 * Перед валидацией и {@see Schema::getState()} чинит битый JSON TipTap из Livewire,
 * иначе возможен OOM в RichEditorStateCast / tiptap-php.
 */
trait SanitizesTipTapFullDescriptionBeforeValidate
{
    protected function beforeValidate(): void
    {
        $this->sanitizeTipTapFullDescriptionInFormState();
    }

    /**
     * Livewire validate() passes {@see getDataForValidation()}: top-level props, form fields under `data`.
     * Sanitize here so Filament schema / casts never see corrupt TipTap JSON.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        if (isset($attributes['data']) && is_array($attributes['data'])
            && array_key_exists('full_description', $attributes['data'])) {
            $sanitized = FilamentTipTapDocumentSanitizer::sanitizeLivewireState(
                $attributes['data']['full_description'],
            );
            $attributes['data']['full_description'] = $sanitized;
            if (is_array($this->data)) {
                $this->data['full_description'] = $sanitized;
            }
        }

        return parent::prepareForValidation($attributes);
    }

    protected function sanitizeTipTapFullDescriptionInFormState(): void
    {
        if (! is_array($this->data) || ! array_key_exists('full_description', $this->data)) {
            return;
        }

        $this->data['full_description'] = FilamentTipTapDocumentSanitizer::sanitizeLivewireState(
            $this->data['full_description'],
        );
    }
}
