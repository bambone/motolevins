<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalUnitResource\Pages;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
use App\Filament\Tenant\Resources\RentalUnitResource;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\LinkedBookableServiceManager;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRentalUnit extends EditRecord
{
    protected static string $resource = RentalUnitResource::class;

    /** @var array<string, mixed>|null */
    protected ?array $pendingLinkedBookingForm = null;

    /** @var list<int>|null */
    protected ?array $pendingTenantLocationIds = null;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $e) {
            if (LinkedBookableSchedulingForm::validationExceptionAffectsLinkedFields($e)) {
                $this->js(LinkedBookableSchedulingForm::jsSyncBrowserTabToOnlineBooking(
                    $this->getId(),
                    LinkedBookableSchedulingForm::RENTAL_UNIT_TAB_QUERY_KEY,
                ));
            }

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRedirectUrlParameters(): array
    {
        return [
            ...parent::getRedirectUrlParameters(),
            LinkedBookableSchedulingForm::RENTAL_UNIT_TAB_QUERY_KEY => request()->query(
                LinkedBookableSchedulingForm::RENTAL_UNIT_TAB_QUERY_KEY,
                LinkedBookableSchedulingForm::TAB_KEY_MAIN,
            ),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Удалить единицу парка'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['tenant_location_ids'] = $this->record->tenantLocations->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if (! LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            return $data;
        }

        $manager = app(LinkedBookableServiceManager::class);
        $service = $manager->findLinkedForRentalUnit($this->record, SchedulingScope::Tenant);

        if ($service === null) {
            $data['linked_booking_enabled'] = false;
            $data['linked_sync_title_from_source'] = true;
            $data['linked_duration_minutes'] = 60;
            $data['linked_slot_step_minutes'] = 15;
            $data['linked_buffer_before_minutes'] = 0;
            $data['linked_buffer_after_minutes'] = 0;
            $data['linked_min_booking_notice_minutes'] = 120;
            $data['linked_max_booking_horizon_days'] = 60;
            $data['linked_requires_confirmation'] = true;
            $data['linked_sort_weight'] = 0;

            return $data;
        }

        $target = $service->schedulingTarget;
        $data['linked_booking_enabled'] = $service->is_active && ($target?->scheduling_enabled ?? false);
        $data['linked_sync_title_from_source'] = $service->sync_title_from_source;
        $data['linked_duration_minutes'] = $service->duration_minutes;
        $data['linked_slot_step_minutes'] = $service->slot_step_minutes;
        $data['linked_buffer_before_minutes'] = $service->buffer_before_minutes;
        $data['linked_buffer_after_minutes'] = $service->buffer_after_minutes;
        $data['linked_min_booking_notice_minutes'] = $service->min_booking_notice_minutes;
        $data['linked_max_booking_horizon_days'] = $service->max_booking_horizon_days;
        $data['linked_requires_confirmation'] = $service->requires_confirmation;
        $data['linked_sort_weight'] = $service->sort_weight;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('tenant_location_ids', $data)) {
            $raw = $data['tenant_location_ids'] ?? [];
            $this->pendingTenantLocationIds = array_values(array_filter(
                array_map('intval', is_array($raw) ? $raw : []),
                fn (int $id): bool => $id > 0,
            ));
        } else {
            $this->pendingTenantLocationIds = null;
        }
        unset($data['tenant_location_ids']);

        if (LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            $names = LinkedBookableSchedulingForm::linkedFieldNames();
            $this->pendingLinkedBookingForm = [];
            foreach ($names as $name) {
                if (array_key_exists($name, $data)) {
                    $this->pendingLinkedBookingForm[$name] = $data[$name];
                    unset($data[$name]);
                }
            }
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        $record = $this->record->fresh(['motorcycle']);
        if ($record !== null) {
            $motorcycle = $record->motorcycle;
            $perUnit = $motorcycle !== null
                && $motorcycle->uses_fleet_units
                && ($motorcycle->location_mode ?? null) === MotorcycleLocationMode::PerUnit;
            if (! $perUnit) {
                $record->tenantLocations()->sync([]);
            } elseif ($this->pendingTenantLocationIds !== null) {
                $record->tenantLocations()->sync($this->pendingTenantLocationIds);
            }
        }
        $this->pendingTenantLocationIds = null;

        if ($this->pendingLinkedBookingForm === null || $this->pendingLinkedBookingForm === []) {
            return;
        }

        if (! LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            return;
        }

        app(LinkedBookableServiceManager::class)->applyRentalUnitLinkedForm(
            $this->record->fresh(),
            SchedulingScope::Tenant,
            $this->pendingLinkedBookingForm
        );
        $this->pendingLinkedBookingForm = null;
    }
}
