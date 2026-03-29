<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformMarketingContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $intentKeys = array_keys(config('platform_marketing.contact_page.intents', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'required_without:email'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'required_without:phone'],
            'message' => ['required', 'string', 'min:15', 'max:2000'],
            'intent' => ['nullable', 'string', Rule::in($intentKeys)],
            'company_site' => ['prohibited'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
            'utm_term' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Коротко опишите задачу (не менее :min символов) — так мы лучше подготовим ответ.',
        ];
    }
}
