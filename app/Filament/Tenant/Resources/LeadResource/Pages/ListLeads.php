<?php

namespace App\Filament\Tenant\Resources\LeadResource\Pages;

use App\Filament\Exports\LeadExporter;
use App\Filament\Tenant\Forms\ManualOperatorBookingForm;
use App\Filament\Tenant\Resources\LeadResource;
use App\Models\Booking;
use App\Models\Lead;
use App\Product\CRM\DTO\ManualLeadCreateData;
use App\Product\CRM\ManualLeadBookingService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string|Htmlable
    {
        return new HtmlString(
            '<div>'
            .'<span class="text-xl font-semibold tracking-tight">'.e(LeadResource::getPluralModelLabel()).'</span>'
            .'<p class="mt-2 max-w-3xl text-sm font-normal text-gray-600 dark:text-gray-400">'
            .'Входящие обращения с сайта: потенциальные клиенты и запросы на аренду. Обрабатывайте новые заявки в первую очередь — '
            .'статус и ответственный видны только вашей команде.'
            .'</p>'
            .'</div>'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_manual_lead')
                ->label('Добавить обращение')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => Gate::allows('create', Lead::class))
                ->form(ManualOperatorBookingForm::leadCreateComponents())
                ->action(function (array $data): void {
                    $tenant = currentTenant();
                    if ($tenant === null) {
                        return;
                    }

                    $createBooking = (bool) ($data['create_booking'] ?? false)
                        && Gate::allows('create', Booking::class);
                    $createCrm = ManualOperatorBookingForm::effectiveCreateCrm($data);

                    $fromYmd = $createBooking
                        ? ManualOperatorBookingForm::toYmd($data['booking_rental_date_from'] ?? null)
                        : ManualOperatorBookingForm::toYmd($data['rental_date_from'] ?? null);
                    $toYmd = $createBooking
                        ? ManualOperatorBookingForm::toYmd($data['booking_rental_date_to'] ?? null)
                        : ManualOperatorBookingForm::toYmd($data['rental_date_to'] ?? null);

                    $motorcycleId = isset($data['motorcycle_id']) ? (int) $data['motorcycle_id'] : null;
                    $rentalUnitId = isset($data['rental_unit_id']) ? (int) $data['rental_unit_id'] : null;

                    app(ManualLeadBookingService::class)->createManualLead(new ManualLeadCreateData(
                        tenantId: $tenant->id,
                        name: (string) $data['name'],
                        phone: (string) $data['phone'],
                        email: $data['email'] ?? null,
                        comment: $data['comment'] ?? null,
                        messenger: $data['messenger'] ?? null,
                        motorcycleId: $motorcycleId ?: null,
                        rentalDateFromYmd: $fromYmd,
                        rentalDateToYmd: $toYmd,
                        createCrm: $createCrm,
                        createBooking: $createBooking,
                        rentalUnitId: $rentalUnitId ?: null,
                    ));

                    Notification::make()
                        ->title('Обращение создано')
                        ->success()
                        ->send();
                }),
            ExportAction::make()
                ->exporter(LeadExporter::class)
                ->visible(fn () => Gate::allows('export_leads')),
        ];
    }
}
