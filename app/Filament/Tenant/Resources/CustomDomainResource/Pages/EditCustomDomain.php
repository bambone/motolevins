<?php

namespace App\Filament\Tenant\Resources\CustomDomainResource\Pages;

use App\Filament\Tenant\Resources\CustomDomainResource;
use App\Jobs\ProvisionTenantCustomDomainJob;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditCustomDomain extends EditRecord
{
    protected static string $resource = CustomDomainResource::class;

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
                        Placeholder::make('host_display')
                            ->label('Домен')
                            ->content(fn (): string => $this->record->host),
                    ]),
                Section::make('Инструкция по DNS')
                    ->description('Добавьте записи у регистратора. Распространение DNS может занять до 48 часов (обычно — минуты).')
                    ->schema([
                        Placeholder::make('txt')
                            ->label('TXT')
                            ->content(function (): string {
                                $prefix = config('tenancy.custom_domains.verification_prefix');
                                $token = $this->record->verification_token ?? '—';

                                return "Имя: {$prefix}\nЗначение: {$token}";
                            }),
                        Placeholder::make('a')
                            ->label('A')
                            ->content(function (): string {
                                $ip = config('tenancy.server_ip');

                                return "Имя: @\nЗначение: {$ip}";
                            }),
                        Placeholder::make('www')
                            ->label('CNAME для www')
                            ->content(fn (): string => 'Имя: www'."\n".'Значение: '.$this->record->host),
                    ])
                    ->columns(1),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $record;
    }
}
