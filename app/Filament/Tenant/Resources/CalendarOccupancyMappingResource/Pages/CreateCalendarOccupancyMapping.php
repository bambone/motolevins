<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CalendarOccupancyMappingResource\Pages;

use App\Filament\Tenant\Resources\CalendarOccupancyMappingResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarOccupancyMapping extends CreateRecord
{
    protected static string $resource = CalendarOccupancyMappingResource::class;

    public function mount(): void
    {
        if (! CalendarOccupancyMappingResource::canStartCreatingMapping()) {
            Notification::make()
                ->title('Сначала настройте календарь')
                ->body(
                    'Нужны включённые календарные интеграции и хотя бы одна подписка на календарь в карточке подключения («Календари (подключения)»). Иначе обязательное поле «Календарь (подписка)» не заполнить.'
                )
                ->warning()
                ->send();
            $this->redirect(CalendarOccupancyMappingResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('calendar_subscription_id', $data) && $data['calendar_subscription_id'] !== null && $data['calendar_subscription_id'] !== '') {
            AssertTenantOwnedIds::assertCalendarSubscriptionForCurrentTenant(
                $data['calendar_subscription_id'],
                'calendar_subscription_id',
            );
        }
        if (array_key_exists('scheduling_target_id', $data)) {
            AssertTenantOwnedIds::assertOptionalSchedulingTargetId($data['scheduling_target_id'] ?? null);
        }
        if (array_key_exists('scheduling_resource_id', $data)) {
            AssertTenantOwnedIds::assertOptionalSchedulingResourceId($data['scheduling_resource_id'] ?? null);
        }

        return $data;
    }
}
