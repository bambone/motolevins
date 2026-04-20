<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ReportsMotorcycleEditBlockFooter;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\Models\SeoMeta;
use App\Services\Seo\TenantSeoPublicPreviewService;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Throwable;

class MotorcycleSeoEditor extends Component implements HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithSchemas;
    use ReportsMotorcycleEditBlockFooter;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'seo';

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $m->loadMissing('seoMeta');
        $seo = $m->seoMeta;
        $fillable = (new SeoMeta)->getFillable();
        $skip = ['tenant_id', 'seoable_type', 'seoable_id'];
        $keys = array_values(array_diff($fillable, $skip));
        $row = $seo !== null ? Arr::only($seo->toArray(), $keys) : [];
        $row['is_indexable'] = (bool) ($row['is_indexable'] ?? true);
        $row['is_followable'] = (bool) ($row['is_followable'] ?? true);

        $this->form->fill(['seoMeta' => $row]);
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
            MotorcycleFormFieldKit::seoMetaSection(),
        ]);
    }

    public function getSeoPreviewProperty(): HtmlString
    {
        $m = $this->resolveMotorcycle();
        $tenant = tenant();
        if ($tenant === null) {
            return new HtmlString('');
        }
        $snippet = app(TenantSeoPublicPreviewService::class)->motorcycleSnippet($tenant, $m->fresh(['seoMeta', 'category']));
        $t = e($snippet['title']);
        $d = e($snippet['description']);

        return new HtmlString(
            '<div class="space-y-2 text-sm"><p><span class="font-medium text-gray-600 dark:text-gray-400">Title:</span> '.$t.'</p>'
            .'<p><span class="font-medium text-gray-600 dark:text-gray-400">Description:</span> '.$d.'</p></div>'
        );
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        $fillable = (new SeoMeta)->getFillable();
        $keys = array_values(array_diff($fillable, ['tenant_id', 'seoable_type', 'seoable_id']));

        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, $keys, 0);

        try {
            $this->form->validate();
            $state = $this->form->getState();
            $payload = Arr::get($state, 'seoMeta', []);
            if (! is_array($payload)) {
                $payload = [];
            }
            $payload = Arr::only($payload, $keys);

            $m = $this->resolveMotorcycle();
            $m->seoMeta()->updateOrCreate(
                [
                    'seoable_id' => $m->getKey(),
                    'seoable_type' => $m->getMorphClass(),
                ],
                array_merge($payload, [
                    'tenant_id' => $m->tenant_id,
                ])
            );

            $m->refresh();
            $m->load('seoMeta');
            $seo = $m->seoMeta;
            $row = $seo !== null ? Arr::only($seo->toArray(), $keys) : [];
            $row['is_indexable'] = (bool) ($row['is_indexable'] ?? true);
            $row['is_followable'] = (bool) ($row['is_followable'] ?? true);
            $this->form->fill(['seoMeta' => $row]);
            $this->initialSnapshot = $this->computeSnapshot();

            Notification::make()->title('SEO сохранено')->success()->send();

            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                true,
                array_keys($payload),
                (hrtime(true) - $t0) / 1_000_000,
            );
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
        return $this->motorcycleEditFooterStatus($this->computeSnapshot() !== $this->initialSnapshot);
    }

    private function computeSnapshot(): string
    {
        $seo = $this->data['seoMeta'] ?? [];

        return md5((string) json_encode(is_array($seo) ? $seo : [], JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-seo-editor');
    }
}
