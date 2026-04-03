<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Tenant;
use App\Product\CRM\TenantBookingFromCrmConverter;
use Illuminate\Console\Command;

/**
 * Досоздание строк {@see Booking}: CRM tenant_booking в «Конверсия» и обращения в статусе «Подтверждена» без брони.
 */
class MaterializeConvertedCrmBookingsCommand extends Command
{
    protected $signature = 'bookings:materialize-from-crm-converted
                            {--tenant= : Slug или ID тенанта (иначе все)}';

    protected $description = 'Создать брони: CRM «Конверсия» (tenant_booking) и обращения «Подтверждена» без строки в bookings';

    public function handle(TenantBookingFromCrmConverter $converter): int
    {
        $tenantOpt = $this->option('tenant');
        $tenantId = null;
        if ($tenantOpt !== null && $tenantOpt !== '') {
            $tenant = is_numeric($tenantOpt)
                ? Tenant::query()->find((int) $tenantOpt)
                : Tenant::query()->where('slug', $tenantOpt)->first();
            if ($tenant === null) {
                $this->error('Тенант не найден: '.$tenantOpt);

                return self::FAILURE;
            }
            $tenantId = (int) $tenant->id;
        }

        $fromCrm = $converter->materializeAllConvertedTenantBookings($tenantId);
        $fromLeads = $converter->materializeAllConfirmedLeadsWithoutBooking($tenantId);
        $this->info('Создано броней из CRM (конверсия): '.$fromCrm);
        $this->info('Создано броней из обращений (подтверждена): '.$fromLeads);

        return self::SUCCESS;
    }
}
