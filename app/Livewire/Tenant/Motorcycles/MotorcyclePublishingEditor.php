<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ReportsMotorcycleEditBlockFooter;
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

class MotorcyclePublishingEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ReportsMotorcycleEditBlockFooter;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'publishing';

    /** @var list<string> */
    private const KEYS = [
        'status', 'sort_order', 'category_id',
        'show_on_home', 'show_in_catalog', 'is_recommended',
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
                ->schema(MotorcycleFormFieldKit::publishingVisibilityFields())
                ->columns(1),
        ]);
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, self::KEYS, 0);

        try {
            $this->form->validate();
            $data = Arr::only($this->form->getState(), self::KEYS);
            $data['show_on_home'] = (bool) ($data['show_on_home'] ?? false);
            $data['show_in_catalog'] = (bool) ($data['show_in_catalog'] ?? false);
            $data['is_recommended'] = (bool) ($data['is_recommended'] ?? false);
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
            $data['category_id'] = $data['category_id'] !== null && $data['category_id'] !== ''
                ? (int) $data['category_id']
                : null;

            $m = $this->resolveMotorcycle();
            $m->update($data);
            $this->initialSnapshot = $this->computeSnapshot();
            $this->touchMotorcycleEditSavedTimestamp();
            Notification::make()->title('Публикация сохранена')->success()->send();
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
        return md5((string) json_encode(Arr::only($this->data, self::KEYS), JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-publishing-editor');
    }
}
