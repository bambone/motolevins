<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ReportsMotorcycleEditBlockFooter;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\Support\FilamentTipTapDocumentSanitizer;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;
use Throwable;

class MotorcycleDescriptionEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ReportsMotorcycleEditBlockFooter;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'description';

    /** @var list<string> */
    private const KEYS = ['full_description'];

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $this->form->fill(['full_description' => $m->full_description]);
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
        return $schema->components(MotorcycleFormFieldKit::fullDescriptionField());
    }

    /**
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
            $this->data['full_description'] = $sanitized;
        }

        return parent::prepareForValidation($attributes);
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, self::KEYS, 0);

        try {
            if (array_key_exists('full_description', $this->data)) {
                $this->data['full_description'] = FilamentTipTapDocumentSanitizer::sanitizeLivewireState(
                    $this->data['full_description'],
                );
            }

            $this->form->validate();
            $data = Arr::only($this->form->getState(), self::KEYS);
            $m = $this->resolveMotorcycle();
            $m->update($data);
            $this->initialSnapshot = $this->computeSnapshot();
            $this->touchMotorcycleEditSavedTimestamp();
            Notification::make()->title('Описание сохранено')->success()->send();
            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                true,
                array_keys($data),
                (hrtime(true) - $t0) / 1_000_000,
            );
        } catch (Throwable $e) {
            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                false,
                self::KEYS,
                (hrtime(true) - $t0) / 1_000_000,
                $e->getMessage(),
            );
            throw $e;
        }
    }

    public function getStatusLineProperty(): string
    {
        return $this->motorcycleEditFooterStatus($this->computeSnapshot() !== $this->initialSnapshot);
    }

    private function computeSnapshot(): string
    {
        $fd = $this->data['full_description'] ?? null;

        return md5((string) json_encode(['full_description' => $fd], JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-description-editor');
    }
}
