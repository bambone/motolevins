<?php

namespace App\Http\Requests;

use App\Services\TenantPublicBookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantBookingMotorcycleCalendarHintsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = currentTenant()?->id;

        return [
            'motorcycle_id' => [
                'required',
                'integer',
                Rule::exists('motorcycles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'range_from' => ['required', 'date'],
            'range_to' => ['required', 'date', 'after_or_equal:range_from'],
            'selected_start' => ['nullable', 'date'],
            'selected_end' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->failed()) {
                return;
            }

            $data = $validator->validated();
            $from = Carbon::parse($data['range_from'])->startOfDay();
            $to = Carbon::parse($data['range_to'])->startOfDay();
            if ($from->diffInDays($to) + 1 > TenantPublicBookingAvailabilityService::MAX_HINTS_WINDOW_DAYS) {
                $validator->errors()->add(
                    'range_to',
                    'Интервал календаря не более '.TenantPublicBookingAvailabilityService::MAX_HINTS_WINDOW_DAYS.' дней.',
                );
            }

            $selS = $data['selected_start'] ?? null;
            $selE = $data['selected_end'] ?? null;
            if (($selS === null || $selS === '') xor ($selE === null || $selE === '')) {
                $validator->errors()->add('selected_end', 'Укажите обе даты периода или ни одной.');
            }

            if ($selS && $selE && Carbon::parse($selE)->lt(Carbon::parse($selS))) {
                $validator->errors()->add('selected_end', 'Дата возврата не раньше даты начала.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['selected_start', 'selected_end', 'phone'] as $key) {
            $v = $this->input($key);
            if ($v === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
