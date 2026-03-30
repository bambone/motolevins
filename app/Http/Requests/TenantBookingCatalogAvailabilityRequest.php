<?php

namespace App\Http\Requests;

use App\Services\TenantPublicBookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantBookingCatalogAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = currentTenant()?->id;

        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'motorcycle_ids' => ['required', 'array', 'max:'.TenantPublicBookingAvailabilityService::MAX_CATALOG_MOTORCYCLE_IDS],
            'motorcycle_ids.*' => [
                'integer',
                Rule::exists('motorcycles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->failed()) {
                return;
            }

            $data = $validator->validated();
            $from = Carbon::parse($data['start_date'])->startOfDay();
            $to = Carbon::parse($data['end_date'])->startOfDay();
            if ($from->diffInDays($to) + 1 > 366) {
                $validator->errors()->add('end_date', 'Слишком длинный период.');
            }
        });
    }
}
