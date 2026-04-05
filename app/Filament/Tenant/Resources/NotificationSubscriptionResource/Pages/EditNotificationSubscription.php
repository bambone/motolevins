<?php

namespace App\Filament\Tenant\Resources\NotificationSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\NotificationSubscriptionResource;
use Filament\Resources\Pages\EditRecord;

class EditNotificationSubscription extends EditRecord
{
    protected static string $resource = NotificationSubscriptionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('destinations');
        $data['destination_ids'] = $this->record->destinations->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->destinationIds = $data['destination_ids'] ?? [];
        unset($data['destination_ids']);

        return $data;
    }

    /** @var list<int|string> */
    protected array $destinationIds = [];

    protected function afterSave(): void
    {
        $sync = [];
        $order = 0;
        foreach ($this->destinationIds as $id) {
            $sync[(int) $id] = [
                'delivery_mode' => 'immediate',
                'delay_seconds' => null,
                'order_index' => $order++,
                'is_enabled' => true,
            ];
        }
        $this->record->destinations()->sync($sync);
    }
}
