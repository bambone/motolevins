<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SchedulingTargetResource\Pages;

use App\Filament\Tenant\Resources\SchedulingTargetResource;
use App\Filament\Tenant\Support\AssertSchedulingTargetSelectedResources;
use App\Filament\Tenant\Support\SchedulingTargetResourceAttachmentSync;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedulingTarget extends CreateRecord
{
    protected static string $resource = SchedulingTargetResource::class;

    /** @var list<int> */
    protected array $pendingSchedulingResourceIds = [];

    public function mount(): void
    {
        if (! SchedulingTargetResource::canStartCreatingTarget()) {
            Notification::make()
                ->title('Сначала создайте ресурс расписания')
                ->body(
                    'Без календарного ресурса (сотрудник, зал и т.д.) поле «Ресурсы» в форме цели останется пустым. Добавьте ресурс в разделе «Запись: основа» → «Ресурсы расписания», затем вернитесь сюда.'
                )
                ->warning()
                ->send();
            $this->redirect(SchedulingTargetResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $data['schedulingResources'] ?? [];
        $this->pendingSchedulingResourceIds = array_values(array_unique(array_filter(
            array_map('intval', is_array($raw) ? $raw : []),
            static fn (int $id): bool => $id > 0,
        )));
        AssertSchedulingTargetSelectedResources::forTenantForm($this->pendingSchedulingResourceIds);
        unset($data['schedulingResources']);

        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingSchedulingResourceIds === []) {
            return;
        }

        SchedulingTargetResourceAttachmentSync::syncWithDefaultPivot(
            $this->record,
            $this->pendingSchedulingResourceIds,
        );
    }
}
