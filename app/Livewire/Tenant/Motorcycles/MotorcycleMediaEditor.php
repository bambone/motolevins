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
use Livewire\Component;
use Throwable;

class MotorcycleMediaEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'media';

    /** @var list<string> */
    private const KEYS = ['cover', 'gallery'];

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $m->load('media');
        $this->form->fill([
            'cover' => $m->getFirstMedia('cover')?->getAttribute('uuid'),
            'gallery' => $m->getMedia('gallery')->pluck('uuid')->values()->all(),
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
            Section::make('Медиа')
                ->description('Обложка и галерея. Новые файлы обычно сохраняются сразу после загрузки; эта кнопка дожимает состояние формы (порядок, удаления).')
                ->schema(MotorcycleFormFieldKit::mediaUploadFields()),
        ]);
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, self::KEYS, 0);

        try {
            $this->form->validate();
            $this->form->getState();
            $m = $this->resolveMotorcycle()->fresh();
            $m->load('media');
            $this->form->fill([
                'cover' => $m->getFirstMedia('cover')?->getAttribute('uuid'),
                'gallery' => $m->getMedia('gallery')->pluck('uuid')->values()->all(),
            ]);
            $this->initialSnapshot = $this->computeSnapshot();
            Notification::make()->title('Медиа сохранены')->success()->send();
            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                true,
                self::KEYS,
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
        return $this->computeSnapshot() !== $this->initialSnapshot
            ? 'Есть несохранённые изменения'
            : 'Сохранено';
    }

    private function computeSnapshot(): string
    {
        $cover = $this->data['cover'] ?? null;
        $gallery = $this->data['gallery'] ?? [];

        return md5((string) json_encode(['cover' => $cover, 'gallery' => $gallery], JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-media-editor');
    }
}
