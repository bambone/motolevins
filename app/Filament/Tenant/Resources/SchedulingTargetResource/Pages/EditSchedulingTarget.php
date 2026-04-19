<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SchedulingTargetResource\Pages;

use App\Filament\Tenant\Resources\SchedulingTargetResource;
use App\Filament\Tenant\Support\AssertSchedulingTargetSelectedResources;
use App\Filament\Tenant\Support\SchedulingTargetResourceAttachmentSync;
use Filament\Resources\Pages\EditRecord;

class EditSchedulingTarget extends EditRecord
{
    protected static string $resource = SchedulingTargetResource::class;

    /** @var list<int>|null */
    protected ?array $pendingSchedulingResourceIds = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('schedulingResources', $data)) {
            $raw = $data['schedulingResources'] ?? [];
            $ids = array_values(array_unique(array_filter(
                array_map('intval', is_array($raw) ? $raw : []),
                static fn (int $id): bool => $id > 0,
            )));
            AssertSchedulingTargetSelectedResources::forTenantForm($ids);
            $this->pendingSchedulingResourceIds = $ids;
            unset($data['schedulingResources']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingSchedulingResourceIds === null) {
            return;
        }

        SchedulingTargetResourceAttachmentSync::syncPreservingExistingPivot(
            $this->record,
            $this->pendingSchedulingResourceIds,
        );
        $this->pendingSchedulingResourceIds = null;
    }
}
