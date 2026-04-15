<?php

declare(strict_types=1);

namespace App\Tenant\Reviews;

use App\Models\TenantSetting;

/**
 * Публичная отправка отзывов: флаги и тексты из tenant_settings.
 */
final class TenantReviewSubmitConfig
{
    public function __construct(
        public bool $publicSubmitEnabled,
        public bool $moderationEnabled,
        public bool $showRatingField,
        public string $successMessagePending,
        public string $successMessagePublished,
    ) {}

    public static function forTenant(int $tenantId): self
    {
        $enabled = (bool) TenantSetting::getForTenant($tenantId, 'reviews.public_submit_enabled', true);
        $moderation = (bool) TenantSetting::getForTenant($tenantId, 'reviews.moderation_enabled', true);
        $rating = (bool) TenantSetting::getForTenant($tenantId, 'reviews.form_show_rating', true);

        $pending = trim((string) TenantSetting::getForTenant(
            $tenantId,
            'reviews.success_message_pending',
            'Спасибо! Ваш отзыв отправлен на проверку и появится на сайте после модерации.',
        ));
        $published = trim((string) TenantSetting::getForTenant(
            $tenantId,
            'reviews.success_message_published',
            'Спасибо! Ваш отзыв успешно отправлен.',
        ));

        return new self(
            publicSubmitEnabled: $enabled,
            moderationEnabled: $moderation,
            showRatingField: $rating,
            successMessagePending: $pending !== '' ? $pending : 'Спасибо! Ваш отзыв отправлен на проверку и появится на сайте после модерации.',
            successMessagePublished: $published !== '' ? $published : 'Спасибо! Ваш отзыв успешно отправлен.',
        );
    }
}
