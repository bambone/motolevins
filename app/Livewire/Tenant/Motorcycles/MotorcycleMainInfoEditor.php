<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;
use Throwable;

class MotorcycleMainInfoEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'main';

    /** @var list<string> */
    private const KEYS = [
        'name', 'slug', 'brand', 'model', 'short_description',
        'catalog_scenario', 'catalog_highlight_1', 'catalog_highlight_2', 'catalog_highlight_3',
    ];

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $this->form->fill(Arr::only($m->attributesToArray(), self::KEYS));
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
                ->schema(MotorcycleFormFieldKit::mainInfoFields())
                ->columns(2),
        ]);
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, self::KEYS, 0);

        try {
            $this->form->validate();
            $data = $this->form->getState();
            $data = Arr::only($data, self::KEYS);
            $m = $this->resolveMotorcycle();
            $m->update($data);
            $this->initialSnapshot = $this->computeSnapshot();
            Notification::make()->title('Основная информация сохранена')->success()->send();
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
        if ($this->computeSnapshot() !== $this->initialSnapshot) {
            return 'Есть несохранённые изменения';
        }

        return 'Сохранено';
    }

    private function computeSnapshot(): string
    {
        $data = Arr::only($this->data, self::KEYS);

        return md5((string) json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-main-info-editor');
    }
}
