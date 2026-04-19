<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CustomDomainResource\Pages;

use App\Filament\Tenant\Resources\CustomDomainResource;
use App\Filament\Tenant\Support\CustomDomainDnsHostResolution;
use App\Filament\Tenant\Support\CustomDomainDnsRegistrarGuide;
use App\Jobs\ProvisionTenantCustomDomainJob;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditCustomDomain extends EditRecord
{
    protected static string $resource = CustomDomainResource::class;

    protected static ?string $breadcrumb = 'Подключение';

    public string $dnsRegistrarGuideKey = '';

    public string $dnsRegistrarGuideVariantKey = '';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->dnsRegistrarGuideVariantKey = CustomDomainDnsRegistrarGuide::defaultRuCenterVariantKey();
    }

    public function updatedDnsRegistrarGuideKey(?string $value): void
    {
        if ($value !== CustomDomainDnsRegistrarGuide::KEY_RU_CENTER) {
            $this->dnsRegistrarGuideVariantKey = CustomDomainDnsRegistrarGuide::defaultRuCenterVariantKey();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Подключение домена';
    }

    /**
     * Страница по сути инструкционная: селект регистратора и подсказки не пишутся в БД через эту форму.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provision')
                ->label('Проверить и подключить')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('Запускается проверка DNS и постановка выпуска SSL в очередь. Это может занять несколько минут.')
                ->action(function (): void {
                    ProvisionTenantCustomDomainJob::dispatch($this->record->id);
                    Notification::make()
                        ->title('Задача поставлена в очередь')
                        ->body('После успешной проверки DNS будет запущено подключение сертификата.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Домен')
                    ->schema([
                        TextEntry::make('host_display')
                            ->label('Домен')
                            ->state(fn (): string => $this->record->host),
                    ]),
                Section::make('Инструкция по DNS')
                    ->description(
                        'После изменения DNS записи могут примениться не сразу. '
                        .'Чаще всего это занимает от нескольких минут до нескольких часов, иногда — до 48 часов.'
                    )
                    ->schema([
                        ViewField::make('dns_registrar_guide')
                            ->hiddenLabel()
                            ->view('filament.tenant.custom-domain-dns-guide')
                            ->viewData(fn (): array => $this->getDnsGuideData()),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDnsGuideData(): array
    {
        $resolution = CustomDomainDnsHostResolution::resolve((string) $this->record->host);
        $variant = $this->dnsRegistrarGuideKey === CustomDomainDnsRegistrarGuide::KEY_RU_CENTER
            ? ($this->dnsRegistrarGuideVariantKey !== '' ? $this->dnsRegistrarGuideVariantKey : null)
            : null;

        $tokenRaw = $this->record->verification_token ?? null;
        $hasVerificationToken = is_string($tokenRaw) && $tokenRaw !== '';

        return [
            'recordId' => $this->record->getKey(),
            'resolution' => $resolution,
            'verificationPrefix' => (string) config('tenancy.custom_domains.verification_prefix'),
            'verificationToken' => $hasVerificationToken ? $tokenRaw : '',
            'hasVerificationToken' => $hasVerificationToken,
            'serverIp' => (string) config('tenancy.server_ip'),
            'registrarOptions' => CustomDomainDnsRegistrarGuide::options(),
            'ruCenterVariantOptions' => CustomDomainDnsRegistrarGuide::ruCenterVariantOptions(),
            'registrarGuide' => CustomDomainDnsRegistrarGuide::guide($this->dnsRegistrarGuideKey, $variant),
            'guideKeyRuCenter' => CustomDomainDnsRegistrarGuide::KEY_RU_CENTER,
            'dnsRegistrarGuideKey' => $this->dnsRegistrarGuideKey,
        ];
    }

    /**
     * Страница без редактируемых полей модели; нижние действия формы скрыты. Заглушка оставлена на случай
     * программного вызова save() — не пишем пустой payload в БД.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $record;
    }
}
