<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\MotorcyclePricing\BookingPricingHydrator;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\RentalPricingDuration;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Fills pricing_snapshot_json + pricing_snapshot_schema_version + minor scalars for legacy booking rows.
 */
class BackfillBookingPricingSnapshotsCommand extends Command
{
    protected $signature = 'booking:backfill-pricing-snapshots
                            {--dry-run : Do not write to the database}
                            {--tenant= : Limit to tenant_id}
                            {--chunk=200 : Chunk size}';

    protected $description = 'Backfill v1 pricing snapshots and minor money columns from legacy booking totals + addons.';

    public function handle(BookingPricingHydrator $hydrator): int
    {
        $dry = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $chunk = max(1, (int) $this->option('chunk'));

        $q = Booking::query()
            ->whereNotNull('motorcycle_id')
            ->where(function ($q): void {
                $q->whereNull('pricing_snapshot_schema_version')
                    ->orWhere('pricing_snapshot_schema_version', '!=', MotorcyclePricingSchema::SNAPSHOT_VERSION);
            })
            ->orderBy('id');

        if ($tenantId !== null && $tenantId !== '') {
            $q->where('tenant_id', (int) $tenantId);
        }

        $updated = 0;
        $skippedNoDates = 0;
        $skippedNoMotorcycle = 0;
        $skippedNoOp = 0;

        $q->chunkById($chunk, function ($bookings) use ($hydrator, $dry, &$updated, &$skippedNoDates, &$skippedNoMotorcycle, &$skippedNoOp): void {
            /** @var Collection<int, Booking> $bookings */
            $bookings->load(['addons.addon', 'motorcycle']);

            foreach ($bookings as $booking) {
                /** @var Booking $booking */
                $motorcycle = $booking->motorcycle;
                if ($motorcycle === null) {
                    $skippedNoMotorcycle++;

                    continue;
                }

                $start = $booking->start_at ?? ($booking->start_date !== null ? Carbon::parse($booking->start_date)->startOfDay() : null);
                $end = $booking->end_at ?? ($booking->end_date !== null ? Carbon::parse($booking->end_date)->startOfDay() : null);
                if ($start === null || $end === null) {
                    $skippedNoDates++;

                    continue;
                }

                $days = RentalPricingDuration::inclusiveCalendarDays(
                    $start->copy()->startOfDay(),
                    $end->copy()->startOfDay(),
                );

                $addonLines = [];
                $addonsTotal = 0;
                foreach ($booking->addons as $ba) {
                    $qty = (int) $ba->quantity;
                    $unit = (int) $ba->price_snapshot;
                    $lineTotal = $unit * $qty;
                    $addonsTotal += $lineTotal;
                    $addonLines[] = [
                        'addon_id' => $ba->addon_id,
                        'name' => $ba->addon?->name ?? 'Дополнение',
                        'quantity' => $qty,
                        'price' => $unit,
                        'total' => $lineTotal,
                    ];
                }

                $totalPrice = (int) $booking->total_price;
                $basePrice = max(0, $totalPrice - $addonsTotal);
                $pricingResult = [
                    'base_price' => $basePrice,
                    'days' => $days,
                    'rental_type' => 'daily',
                    'addons' => $addonLines,
                    'addons_total' => $addonsTotal,
                    'deposit' => (int) $booking->deposit_amount,
                    'total' => $totalPrice,
                    'pricing_snapshot' => [
                        'base_price' => $basePrice,
                        'days' => $days,
                        'addons' => $addonLines,
                        'deposit' => (int) $booking->deposit_amount,
                    ],
                ];

                $attrs = $hydrator->bookingPricingAttributes($motorcycle, $days, $pricingResult, $addonLines);

                if (($attrs['pricing_snapshot_schema_version'] ?? null) !== MotorcyclePricingSchema::SNAPSHOT_VERSION) {
                    $skippedNoOp++;

                    continue;
                }

                if (! $dry) {
                    $booking->forceFill($attrs)->saveQuietly();
                }
                $updated++;
            }
        });

        $this->table(
            ['metric', 'count'],
            [
                ['rows_processed_written_or_dry', (string) $updated],
                ['skipped_missing_motorcycle', (string) $skippedNoMotorcycle],
                ['skipped_missing_dates', (string) $skippedNoDates],
                ['skipped_hydrator_no_snapshot', (string) $skippedNoOp],
            ],
        );

        if ($dry) {
            $this->info('Dry run: no rows updated.');
        }

        return self::SUCCESS;
    }
}
