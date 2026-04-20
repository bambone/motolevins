<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles;

use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Livewire\Tenant\Motorcycles\Concerns\HasMotorcycleBlockFormState;
use App\Livewire\Tenant\Motorcycles\Concerns\ReportsMotorcycleEditBlockFooter;
use App\Livewire\Tenant\Motorcycles\Concerns\ResolvesMotorcycleRecord;
use App\MotorcyclePricing\MotorcyclePricingProfileValidator;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\PricingProfileValidity;
use App\Support\Motorcycle\MotorcycleBlockSaveLogger;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class MotorcyclePricingProfileEditor extends Component implements HasActions, HasSchemas
{
    use HasMotorcycleBlockFormState;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use ReportsMotorcycleEditBlockFooter;
    use ResolvesMotorcycleRecord;

    private const BLOCK = 'pricing_profile';

    /** @var list<string> */
    private const KEYS = [
        'pricing_currency',
        'pricing_tariffs',
        'pricing_card_primary_tariff_id',
        'pricing_card_secondary_mode',
        'pricing_card_secondary_text',
        'pricing_card_secondary_tariff_id',
        'pricing_detail_tariffs_limit',
        'pricing_deposit_amount',
        'pricing_prepayment_amount',
        'pricing_catalog_price_note',
    ];

    public string $initialSnapshot = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $m = $this->resolveMotorcycle();
        $this->form->fill(MotorcycleFormFieldKit::extractPricingProfileFormDefaults($m));
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
        return $schema->components(MotorcycleFormFieldKit::pricingProfileFields());
    }

    public function save(): void
    {
        $t0 = hrtime(true);
        MotorcycleBlockSaveLogger::log(self::BLOCK.'_start', self::BLOCK, $this->recordId, false, self::KEYS, 0);

        try {
            $this->form->validate();
            $slice = Arr::only($this->form->getState(), self::KEYS);
            $merged = MotorcycleFormFieldKit::mergePricingProfileIntoMotorcycleData($slice);
            $profile = $merged['pricing_profile_json'] ?? null;
            if (! is_array($profile)) {
                throw ValidationException::withMessages(['pricing_tariffs' => 'Не удалось собрать профиль.']);
            }

            $v = app(MotorcyclePricingProfileValidator::class)->validate($profile);
            if ($v['validity'] === PricingProfileValidity::Invalid) {
                Notification::make()
                    ->title('Профиль не сохранён')
                    ->body('Исправьте ошибки: '.implode(', ', $v['errors']))
                    ->danger()
                    ->send();
                MotorcycleBlockSaveLogger::log(
                    self::BLOCK.'_done',
                    self::BLOCK,
                    $this->recordId,
                    false,
                    self::KEYS,
                    (hrtime(true) - $t0) / 1_000_000,
                    implode(',', $v['errors']),
                );

                return;
            }

            $m = $this->resolveMotorcycle();
            $m->update([
                'pricing_profile_json' => $profile,
                'pricing_profile_schema_version' => $merged['pricing_profile_schema_version'] ?? MotorcyclePricingSchema::PROFILE_VERSION,
            ]);

            $this->form->fill(MotorcycleFormFieldKit::extractPricingProfileFormDefaults($m->fresh()));
            $this->initialSnapshot = $this->computeSnapshot();
            $this->touchMotorcycleEditSavedTimestamp();

            $title = $v['validity'] === PricingProfileValidity::ValidWithWarnings
                ? 'Тарифы сохранены (есть предупреждения)'
                : 'Тарифы и условия сохранены';
            Notification::make()->title($title)->success()->send();
            if ($v['warnings'] !== []) {
                Notification::make()
                    ->title('Предупреждения')
                    ->body(implode('; ', $v['warnings']))
                    ->warning()
                    ->send();
            }

            MotorcycleBlockSaveLogger::log(
                self::BLOCK.'_done',
                self::BLOCK,
                $this->recordId,
                true,
                self::KEYS,
                (hrtime(true) - $t0) / 1_000_000,
            );
            $this->dispatch('motorcycle-settings-updated');
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
        $slice = Arr::only($this->data, self::KEYS);
        $merged = MotorcycleFormFieldKit::mergePricingProfileIntoMotorcycleData($slice);
        $profile = $merged['pricing_profile_json'] ?? [];

        return md5((string) json_encode(is_array($profile) ? $profile : [], JSON_UNESCAPED_UNICODE));
    }

    public function render(): View
    {
        return view('livewire.tenant.motorcycles.motorcycle-pricing-profile-editor');
    }
}
