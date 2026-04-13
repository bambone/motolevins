<?php

namespace App\Http\Requests;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\TenantContactChannelsStore;
use App\Support\Phone\IntlPhoneNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpertInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => IntlPhoneNormalizer::normalizePhone((string) $this->input('phone')),
            ]);
        }

        $this->merge([
            'preferred_contact_channel' => $this->input('preferred_contact_channel', ContactChannelType::Phone->value),
        ]);

        if ($this->has('preferred_schedule')) {
            $this->merge([
                'preferred_schedule' => trim((string) $this->input('preferred_schedule')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenant = currentTenant();
        $allowed = $tenant !== null
            ? app(TenantContactChannelsStore::class)->allowedPreferredChannelIds($tenant->id)
            : [ContactChannelType::Phone->value];

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:16',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! IntlPhoneNormalizer::validatePhone($value)) {
                        $fail('Укажите корректный телефон в международном формате (например +7 для России).');
                    }
                },
            ],
            'goal_text' => ['required', 'string', 'max:2000'],
            'preferred_schedule' => [
                'nullable',
                'string',
                'max:32',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->assertValidPreferredScheduleInterval($value, $fail);
                },
            ],
            'district' => ['nullable', 'string', 'max:255'],
            'has_own_car' => ['nullable', 'string', 'max:32'],
            'transmission' => ['nullable', 'string', 'max:64'],
            'has_license' => ['nullable', 'string', 'max:32'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'program_slug' => ['nullable', 'string', 'max:128'],
            'expert_domain' => ['nullable', 'string', 'max:64'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'preferred_contact_channel' => ['required', 'string', Rule::in($allowed)],
            'preferred_contact_value' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @param  \Closure(string): void  $fail
     */
    protected function assertValidPreferredScheduleInterval(mixed $value, \Closure $fail): void
    {
        if ($value === null) {
            return;
        }
        if (! is_string($value)) {
            $fail('Некорректное значение удобного времени.');

            return;
        }
        $trim = trim($value);
        if ($trim === '') {
            return;
        }
        if (! preg_match('/^(\d{2}:\d{2})\s*[\x{2013}\x{2014}-]\s*(\d{2}:\d{2})$/u', $trim, $m)) {
            $fail('Укажите интервал как ЧЧ:ММ – ЧЧ:ММ (например 18:00 – 21:00) или оставьте оба поля пустыми.');

            return;
        }
        foreach ([$m[1], $m[2]] as $hm) {
            if (! self::isValidHourMinuteToken($hm)) {
                $fail('Время должно быть от 00:00 до 23:59.');

                return;
            }
        }
    }

    protected static function isValidHourMinuteToken(string $hm): bool
    {
        if (! preg_match('/^(\d{2}):(\d{2})$/', $hm, $p)) {
            return false;
        }
        $h = (int) $p[1];
        $i = (int) $p[2];

        return $h >= 0 && $h <= 23 && $i >= 0 && $i <= 59;
    }
}
