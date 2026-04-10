<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\LinkedBookableServiceManager;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class MotorcycleSchedulingEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'scheduling';

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        if (! LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            $this->initialSnapshot = '';

            return;
        }

        $m = $this->resolveMotorcycle();
        $this->form->fill(LinkedBookableSchedulingForm::linkedFormDataForMotorcycle($m));
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
        return $schema->components(
            LinkedBookableSchedulingForm::motorcycleOnlineBookingEditorSchema()
        );
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        $keys = LinkedBookableSchedulingForm::linkedFieldNames();
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, $keys, 0);

        if (! LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            MotorcycleBlockSaveLogger::log(self::BLOCK.'_done', self::BLOCK, $this->recordId, true, [], (hrtime(true) - $t0) / 1_000_000);

            return;
        }

        try {
            $this->form->validate();
            $rawPayload = Arr::only($this->form->getState(), $keys);

            /** @var array<string, mixed> $payload */
            $payload = Validator::make($rawPayload, [
                'linked_booking_enabled' => ['boolean'],
                'linked_sync_title_from_source' => ['boolean'],
                'linked_duration_minutes' => ['required', 'integer', 'min:1'],
                'linked_slot_step_minutes' => ['required', 'integer', 'min:5'],
                'linked_buffer_before_minutes' => ['required', 'integer', 'min:0'],
                'linked_buffer_after_minutes' => ['required', 'integer', 'min:0'],
                'linked_min_booking_notice_minutes' => ['required', 'integer', 'min:0'],
                'linked_max_booking_horizon_days' => ['required', 'integer', 'min:1'],
                'linked_requires_confirmation' => ['boolean'],
                'linked_sort_weight' => ['nullable', 'integer'],
            ])->validate();

            $payload['linked_booking_enabled'] = (bool) ($payload['linked_booking_enabled'] ?? false);
            $payload['linked_sync_title_from_source'] = (bool) ($payload['linked_sync_title_from_source'] ?? true);
            $payload['linked_requires_confirmation'] = (bool) ($payload['linked_requires_confirmation'] ?? true);
            $payload['linked_sort_weight'] = (int) ($payload['linked_sort_weight'] ?? 0);

            $m = $this->resolveMotorcycle();
            app(LinkedBookableServiceManager::class)->applyMotorcycleLinkedForm(
                $m->fresh(),
                SchedulingScope::Tenant,
                $payload,
            );

            $this->form->fill(LinkedBookableSchedulingForm::linkedFormDataForMotorcycle($m->fresh()));
            $this->initialSnapshot = $this->computeSnapshot();

            Notification::make()->title('Настройки онлайн-записи сохранены')->success()->send();

            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                true,
                array_keys($payload),
                (hrtime(true) - $t0) / 1_000_000,
            );
        } catch (ValidationException $e) {
            if (LinkedBookableSchedulingForm::validationExceptionAffectsLinkedFields($e)) {
                $this->js(LinkedBookableSchedulingForm::jsSyncBrowserTabToOnlineBooking(
                    $this->getId(),
                    LinkedBookableSchedulingForm::MOTORCYCLE_TAB_QUERY_KEY,
                ));
            }
            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                false,
                $keys,
                (hrtime(true) - $t0) / 1_000_000,
                'validation',
            );
            throw $e;
        } catch (Throwable $e) {
            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                false,
                $keys,
                (hrtime(true) - $t0) / 1_000_000,
                $e->getMessage(),
            );
            throw $e;
        }
    }

    public function getStatusLineProperty(): string
    {
        if (! LinkedBookableSchedulingForm::schedulingSectionVisible()) {
            return '';
        }

        return $this->computeSnapshot() !== $this->initialSnapshot
            ? 'Есть несохранённые изменения'
            : 'Сохранено';
    }

    private function computeSnapshot(): string
    {
        $keys = LinkedBookableSchedulingForm::linkedFieldNames();
        $slice = Arr::only($this->data, $keys);

        return md5((string) json_encode($slice, JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-scheduling-editor');
    }
}
