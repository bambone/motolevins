<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as IlluminateValidator;

/**
 * Валидация контракта meta_json по типу секции (админка и публичный резолвер).
 */
final class FooterSectionMetaValidator
{
    /**
     * @param  array<string, mixed>  $meta
     * @return array{ok: true, meta: array<string, mixed>}|array{ok: false, message: string}
     */
    public function validate(string $type, array $meta): array
    {
        if ($type === FooterSectionType::CONTACTS) {
            foreach (['show_phone', 'show_telegram', 'show_whatsapp', 'show_email', 'show_address'] as $k) {
                if (! array_key_exists($k, $meta)) {
                    $meta[$k] = false;
                }
            }
        }

        $rules = $this->rulesForType($type);
        if ($rules === null) {
            return ['ok' => false, 'message' => 'Неизвестный тип секции.'];
        }

        /** @var IlluminateValidator $v */
        $v = Validator::make($meta, $rules);
        $v->after(function (IlluminateValidator $vv) use ($type): void {
            if ($type !== FooterSectionType::CTA_STRIP) {
                return;
            }
            $data = $vv->getData();
            foreach (['primary_button_url', 'secondary_button_url'] as $k) {
                $u = $data[$k] ?? null;
                if ($u === null || $u === '') {
                    continue;
                }
                if (! is_string($u) || ! preg_match('#^https?://#i', $u)) {
                    $vv->errors()->add($k, 'Укажите корректный URL (http/https).');
                }
            }
        });
        if ($v->fails()) {
            return ['ok' => false, 'message' => $v->errors()->first() ?? 'Некорректные данные секции.'];
        }

        /** @var array<string, mixed> $clean */
        $clean = $v->validated();

        return ['ok' => true, 'meta' => $this->normalizeAfterValidate($type, $clean)];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rulesForType(string $type): ?array
    {
        $short = 'nullable|string|max:'.FooterLimits::SHORT_FIELD_MAX;
        $long = 'nullable|string|max:'.FooterLimits::LONG_FIELD_MAX;

        return match ($type) {
            FooterSectionType::CTA_STRIP => [
                'headline' => $long,
                'subheadline' => $long,
                'primary_button_label' => $short,
                'primary_button_url' => ['nullable', 'string', 'max:2048'],
                'secondary_button_label' => $short,
                'secondary_button_url' => ['nullable', 'string', 'max:2048'],
                'layout_variant' => $short,
            ],
            FooterSectionType::CONTACTS => [
                'headline' => $long,
                'description' => $long,
                'show_phone' => 'boolean',
                'show_telegram' => 'boolean',
                'show_whatsapp' => 'boolean',
                'show_email' => 'boolean',
                'show_address' => 'boolean',
                'layout_variant' => $short,
            ],
            FooterSectionType::GEO_POINTS => [
                'headline' => $long,
                'items' => 'required|array|min:1|max:'.FooterLimits::LIST_ITEMS_MAX,
                'items.*' => 'required|string|max:'.FooterLimits::LONG_FIELD_MAX,
                'layout_variant' => $short,
            ],
            FooterSectionType::CONDITIONS_LIST => [
                'headline' => $long,
                'items' => 'required|array|min:1|max:'.FooterLimits::LIST_ITEMS_MAX,
                'items.*' => 'required|string|max:'.FooterLimits::LONG_FIELD_MAX,
                'layout_variant' => $short,
            ],
            FooterSectionType::LINK_GROUPS => [
                'headline' => $long,
                'group_titles' => 'nullable|array|max:'.FooterLimits::LINK_GROUPS_MAX_GROUPS,
                'group_titles.*' => 'nullable|string|max:'.FooterLimits::SHORT_FIELD_MAX,
                'layout_variant' => $short,
            ],
            FooterSectionType::BOTTOM_BAR => [
                'copyright_text' => 'nullable|string|max:'.FooterLimits::LONG_FIELD_MAX,
                'secondary_text' => 'nullable|string|max:'.FooterLimits::LONG_FIELD_MAX,
                'layout_variant' => $short,
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAfterValidate(string $type, array $validated): array
    {
        if ($type === FooterSectionType::LINK_GROUPS) {
            $gt = $validated['group_titles'] ?? [];
            if (is_array($gt)) {
                $validated['group_titles'] = array_map(
                    static fn ($v) => is_string($v) ? $v : '',
                    $gt
                );
            }
        }

        return $validated;
    }

    /**
     * Доп. правило: для включённой секции нужен минимум содержимого по типу.
     *
     * @param  array<string, mixed>  $meta
     */
    public function hasMinimumContentForEnabled(string $type, array $meta): bool
    {
        return match ($type) {
            FooterSectionType::CTA_STRIP => $this->ctaHasMinimum($meta),
            FooterSectionType::CONTACTS => $this->anyContactChannelShown($meta),
            FooterSectionType::GEO_POINTS, FooterSectionType::CONDITIONS_LIST => ! empty($meta['items']),
            FooterSectionType::LINK_GROUPS => true,
            FooterSectionType::BOTTOM_BAR => true,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function anyContactChannelShown(array $meta): bool
    {
        foreach (['show_phone', 'show_telegram', 'show_whatsapp', 'show_email', 'show_address'] as $k) {
            if (! empty($meta[$k])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function ctaHasMinimum(array $meta): bool
    {
        foreach (['headline', 'subheadline', 'primary_button_label', 'secondary_button_label'] as $k) {
            if (isset($meta[$k]) && is_string($meta[$k]) && trim($meta[$k]) !== '') {
                return true;
            }
        }

        return false;
    }
}
