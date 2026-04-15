<?php

declare(strict_types=1);

namespace App\Tenant\Expert;

use App\Models\Tenant;
use App\Models\TenantSetting;

/**
 * Единый контракт CTA записи на публичном сайте (AF-005 / AF-006 / AF-007).
 * Настройки: сначала enrollment.*, затем fallback на programs.* для обратной совместимости.
 */
class TenantEnrollmentCtaConfig
{
    public const MODE_MODAL = 'modal';

    public const MODE_PAGE = 'page';

    public const MODE_SCROLL = 'scroll';

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public static function forCurrent(): ?static
    {
        $t = tenant();

        return $t instanceof Tenant ? new static($t) : null;
    }

    private function strSetting(string $enrollmentKey, string $legacyKey, string $default): string
    {
        $primary = TenantSetting::getForTenant($this->tenant->id, $enrollmentKey, null);
        if (is_string($primary)) {
            $p = trim($primary);
            if ($p !== '') {
                return $p;
            }
        }

        return trim((string) TenantSetting::getForTenant($this->tenant->id, $legacyKey, $default));
    }

    /**
     * modal — диалог; page — переход на страницу; scroll — якорь к форме на странице.
     */
    public function mode(): string
    {
        $raw = $this->strSetting('enrollment.cta_behavior', 'programs.cta_behavior', self::MODE_MODAL);

        return match ($raw) {
            self::MODE_PAGE, self::MODE_SCROLL => $raw,
            default => self::MODE_MODAL,
        };
    }

    public function enrollmentPageSlug(): string
    {
        $s = $this->strSetting('enrollment.enrollment_page_slug', 'programs.enrollment_page_slug', 'programs');

        return $s !== '' ? $s : 'programs';
    }

    public function modalTitle(): string
    {
        $t = $this->strSetting('enrollment.modal_title', 'programs.modal_title', '');

        return $t !== '' ? $t : 'Записаться на занятие';
    }

    public function modalSuccessMessage(): string
    {
        $t = $this->strSetting('enrollment.modal_success_message', 'programs.modal_success_message', '');

        return $t !== '' ? $t : 'Спасибо! Заявка отправлена. Мы свяжемся с вами.';
    }
}
