<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Tenant\Reviews\TenantReviewSubmitConfig;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTenantPublicReviewRequest extends FormRequest
{
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
        $tenant = tenant();
        $cfg = $tenant !== null ? TenantReviewSubmitConfig::forTenant((int) $tenant->id) : null;
        $ratingRules = $cfg?->showRatingField
            ? ['required', 'integer', 'min:1', 'max:5']
            : ['nullable', 'integer', 'min:1', 'max:5'];

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'body' => ['required', 'string', 'min:20', 'max:8000'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'rating' => $ratingRules,
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
            'rating.required' => 'Выберите оценку.',
            'consent.accepted' => 'Нужно согласие на обработку данных.',
        ];
    }
}
