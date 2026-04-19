<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Motorcycle;
use App\MotorcyclePricing\MotorcycleQuoteEngine;
use App\MotorcyclePricing\RentalPricingDuration;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TenantMotorcycleQuoteController extends Controller
{
    public function __invoke(Request $request, MotorcycleQuoteEngine $engine): JsonResponse
    {
        $tenant = currentTenant();
        abort_if($tenant === null, 404);
        $tenantId = (int) $tenant->id;

        $validated = $request->validate([
            'motorcycle_id' => ['required', Rule::exists('motorcycles', 'id')->where('tenant_id', $tenantId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $motorcycle = Motorcycle::query()
            ->where('tenant_id', $tenantId)
            ->whereKey((int) $validated['motorcycle_id'])
            ->firstOrFail();

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        $days = RentalPricingDuration::inclusiveCalendarDays($start, $end);

        $payload = $engine->quoteForDays($motorcycle, $days);

        return response()->json($payload);
    }
}
