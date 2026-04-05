<?php

namespace App\Filament\Tenant\Resources\NotificationSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\NotificationSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotificationSubscription extends CreateRecord
{
    protected static string $resource = NotificationSubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = currentTenant();
        $data['tenant_id'] = $tenant?->id;
        $data['created_by_user_id'] = Auth::id();
        $this->destinationIds = $data['destination_ids'] ?? [];
        unset($data['destination_ids']);

        return $data;
    }

    /** @var list<int|string> */
    protected array $destinationIds = [];

    protected function afterCreate(): void
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
