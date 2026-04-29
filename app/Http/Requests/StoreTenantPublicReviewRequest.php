<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Tenant\Reviews\TenantReviewSubmitConfig;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTenantPublicReviewRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('rating')) {
            return;
        }

        $raw = $this->input('rating');
        if ($raw === '' || $raw === null || (is_string($raw) && trim($raw) === '')) {
            $this->merge(['rating' => null]);
        }
    }

    public function authorize(): bool
    {
        $tenant = tenant();

        return $tenant !== null
            && TenantReviewSubmitConfig::forTenant((int) $tenant->id)->publicSubmitEnabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'body' => ['required', 'string', 'min:20', 'max:8000'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'consent' => ['required', 'accepted'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'name.min' => 'Имя слишком короткое.',
            'name.max' => 'Имя слишком длинное.',
            'body.required' => 'Напишите текст отзыва.',
            'body.min' => 'Отзыв слишком короткий — нужно не менее :min символов.',
            'body.max' => 'Отзыв слишком длинный.',
            'contact_email.email' => 'Укажите корректный email.',
            'consent.accepted' => 'Нужно согласие на обработку данных.',
        ];
    }
}
