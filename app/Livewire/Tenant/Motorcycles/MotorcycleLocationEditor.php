<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\Models\Motorcycle;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class MotorcycleLocationEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'location';

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $this->form->fill([
            'uses_fleet_units' => (bool) $m->uses_fleet_units,
            'location_mode' => ($m->location_mode ?? MotorcycleLocationMode::Everywhere)->value,
            'tenant_location_ids' => $m->tenantLocations()->pluck('tenant_locations.id')->all(),
        ]);
        $this->initialSnapshot = $this->computeSnapshot();
    }

    protected function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->resolveMotorcycle())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema(MotorcycleFormFieldKit::fleetAndLocationCardFields())
                ->columns(1),
        ]);
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, [], 0);

        try {
            $this->form->validate();
            $data = $this->form->getState();
            $m = $this->resolveMotorcycle();
            $prevMode = $m->location_mode ?? MotorcycleLocationMode::Everywhere;
            $prevCardLocationIds = $m->tenantLocations()->pluck('tenant_locations.id')->all();

            $usesFleet = (bool) ($data['uses_fleet_units'] ?? false);
            $mode = MotorcycleLocationMode::from($data['location_mode'] ?? MotorcycleLocationMode::Everywhere->value);
            if (! $usesFleet && $mode === MotorcycleLocationMode::PerUnit) {
                $mode = MotorcycleLocationMode::Everywhere;
            }

            $m->update([
                'uses_fleet_units' => $usesFleet,
                'location_mode' => $mode,
            ]);

            $ids = isset($data['tenant_location_ids']) && is_array($data['tenant_location_ids'])
                ? array_values(array_unique(array_map('intval', array_filter($data['tenant_location_ids'], fn ($v) => $v !== null && $v !== ''))))
                : [];

            if ($mode === MotorcycleLocationMode::Selected) {
                $m->tenantLocations()->sync($ids);
                $this->clearTenantLocationsFromAllUnits($m);
            } elseif ($mode === MotorcycleLocationMode::Everywhere) {
                $m->tenantLocations()->sync([]);
                $this->clearTenantLocationsFromAllUnits($m);
            } else {
                $m->tenantLocations()->sync([]);
                if ($prevMode === MotorcycleLocationMode::Selected && $prevCardLocationIds !== []) {
                    foreach ($m->rentalUnits()->get() as $unit) {
                        if ($unit->tenantLocations()->count() === 0) {
                            $unit->tenantLocations()->sync($prevCardLocationIds);
                        }
                    }
                }
            }

            $m->refresh();
            $this->form->fill([
                'uses_fleet_units' => (bool) $m->uses_fleet_units,
                'location_mode' => ($m->location_mode ?? MotorcycleLocationMode::Everywhere)->value,
                'tenant_location_ids' => $m->tenantLocations()->pluck('tenant_locations.id')->all(),
            ]);
            $this->initialSnapshot = $this->computeSnapshot();

            Notification::make()->title('Доступность по локациям сохранена')->success()->send();
            $this->dispatch('motorcycle-settings-updated');
            MotorcycleBlockSaveLogger::log(self::BLOCK.'_done', self::BLOCK, $this->recordId, true, [], (hrtime(true) - $t0) / 1_000_000);
        } catch (Throwable $e) {
            MotorcycleBlockSaveLogger::log(self::BLOCK.'_done', self::BLOCK, $this->recordId, false, [], (hrtime(true) - $t0) / 1_000_000, $e->getMessage());
            throw $e;
        }
    }

    private function clearTenantLocationsFromAllUnits(Motorcycle $motorcycle): void
    {
        foreach ($motorcycle->rentalUnits()->get() as $unit) {
            $unit->tenantLocations()->sync([]);
        }
    }

    public function getStatusLineProperty(): string
    {
        return $this->computeSnapshot() !== $this->initialSnapshot
            ? 'Есть несохранённые изменения'
            : 'Сохранено';
    }

    private function computeSnapshot(): string
    {
        $payload = [
            'uses_fleet_units' => $this->data['uses_fleet_units'] ?? false,
            'location_mode' => $this->data['location_mode'] ?? '',
            'tenant_location_ids' => $this->data['tenant_location_ids'] ?? [],
        ];

        return md5((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-location-editor');
    }
}
