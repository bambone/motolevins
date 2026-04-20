<?php

declare(strict_types=1);

namespace App\Support\Motorcycle;

use App\Models\Motorcycle;
use App\MotorcyclePricing\MotorcyclePricingProfileLoader;
use App\MotorcyclePricing\MotorcyclePricingProfileValidator;
use App\MotorcyclePricing\PricingProfileValidity;

/**
 * Чеклист полноты и микро-статусы TOC для экрана редактирования мотоцикла (без «магического %»).
 */
final class MotorcycleEditCompleteness
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARN = 'warn';

    public const STATUS_TODO = 'todo';

    /**
     * Худший статус побеждает: todo &gt; warn &gt; ok.
     *
     * @param  list<self::STATUS_*>  $statuses
     * @return self::STATUS_*
     */
    public static function worst(array $statuses): string
    {
        if ($statuses === []) {
            return self::STATUS_OK;
        }
        if (in_array(self::STATUS_TODO, $statuses, true)) {
            return self::STATUS_TODO;
        }
        if (in_array(self::STATUS_WARN, $statuses, true)) {
            return self::STATUS_WARN;
        }

        return self::STATUS_OK;
    }

    /**
     * @return list<array{key: string, label: string, status: self::STATUS_*}>
     */
    public static function checklistItems(Motorcycle $m): array
    {
        $m->loadMissing(['seoMeta']);

        $items = [];

        $name = trim((string) $m->name);
        $items[] = [
            'key' => 'name',
            'label' => 'Название',
            'status' => $name !== '' ? self::STATUS_OK : self::STATUS_TODO,
        ];

        $slug = trim((string) $m->slug);
        $items[] = [
            'key' => 'slug',
            'label' => 'URL (slug)',
            'status' => $slug !== '' ? self::STATUS_OK : self::STATUS_TODO,
        ];

        $hasCover = $m->getFirstMedia('cover') !== null;
        $items[] = [
            'key' => 'cover',
            'label' => 'Обложка',
            'status' => $hasCover ? self::STATUS_OK : self::STATUS_TODO,
        ];

        $profile = app(MotorcyclePricingProfileLoader::class)->loadOrSynthesize($m);
        $v = app(MotorcyclePricingProfileValidator::class)->validate($profile);
        $tariffStatus = match ($v['validity']) {
            PricingProfileValidity::Invalid => self::STATUS_TODO,
            PricingProfileValidity::ValidWithWarnings => self::STATUS_WARN,
            default => self::STATUS_OK,
        };
        $items[] = [
            'key' => 'tariffs',
            'label' => 'Тарифы',
            'status' => $tariffStatus,
        ];

        $short = trim((string) $m->short_description);
        $items[] = [
            'key' => 'short_description',
            'label' => 'Краткое описание (каталог)',
            'status' => $short !== '' ? self::STATUS_OK : self::STATUS_WARN,
        ];

        $full = trim((string) $m->full_description);
        $items[] = [
            'key' => 'full_description',
            'label' => 'Полное описание',
            'status' => $full !== '' ? self::STATUS_OK : self::STATUS_WARN,
        ];

        $seo = $m->seoMeta;
        $title = trim((string) ($seo?->meta_title ?? ''));
        $desc = trim((string) ($seo?->meta_description ?? ''));
        $items[] = [
            'key' => 'seo_title',
            'label' => 'SEO: заголовок',
            'status' => $title !== '' ? self::STATUS_OK : self::STATUS_WARN,
        ];
        $items[] = [
            'key' => 'seo_description',
            'label' => 'SEO: описание',
            'status' => $desc !== '' ? self::STATUS_OK : self::STATUS_WARN,
        ];

        $pubOk = (bool) $m->show_in_catalog
            && ! in_array((string) $m->status, ['hidden', 'archived'], true);
        $items[] = [
            'key' => 'publication',
            'label' => 'Публикация в каталоге',
            'status' => $pubOk ? self::STATUS_OK : self::STATUS_WARN,
        ];

        return $items;
    }

    /**
     * Секции TOC с агрегированным статусом по чеклисту.
     *
     * @return list<array{id: string, label: string, status: self::STATUS_*, href: string}>
     */
    public static function tocSections(Motorcycle $m): array
    {
        $items = self::checklistItems($m);
        $byKey = [];
        foreach ($items as $row) {
            $byKey[$row['key']] = $row['status'];
        }

        $section = function (string $id, string $label, array $keys) use ($byKey): array {
            $st = [];
            foreach ($keys as $k) {
                $st[] = $byKey[$k] ?? self::STATUS_OK;
            }

            return [
                'id' => $id,
                'label' => $label,
                'status' => self::worst($st),
                'href' => '#'.$id,
            ];
        };

        return [
            $section('moto-main', 'Основное', ['name', 'slug', 'short_description']),
            $section('moto-pricing', 'Тарифы', ['tariffs']),
            $section('moto-media', 'Медиа', ['cover']),
            [
                'id' => 'moto-page',
                'label' => 'Контент страницы',
                'status' => self::pageSectionStatus($m),
                'href' => '#moto-page',
            ],
            [
                'id' => 'moto-specs',
                'label' => 'Характеристики',
                'status' => self::specsSectionStatus($m),
                'href' => '#moto-specs',
            ],
            $section('moto-desc', 'Описание', ['full_description']),
            $section('moto-seo', 'SEO', ['seo_title', 'seo_description']),
        ];
    }

    private static function pageSectionStatus(Motorcycle $m): string
    {
        $audience = trim((string) $m->detail_audience) !== '';
        $useCases = is_array($m->detail_use_case_bullets) && $m->detail_use_case_bullets !== [];
        $adv = is_array($m->detail_advantage_bullets) && $m->detail_advantage_bullets !== [];
        $notes = trim((string) $m->detail_rental_notes) !== '';

        if ($audience || $useCases || $adv || $notes) {
            return self::STATUS_OK;
        }

        return self::STATUS_WARN;
    }

    private static function specsSectionStatus(Motorcycle $m): string
    {
        $json = $m->specs_json;
        $hasJson = is_array($json) && $json !== [];
        $hasBasics = trim((string) $m->engine_cc) !== ''
            || trim((string) $m->power) !== ''
            || trim((string) $m->transmission) !== ''
            || trim((string) $m->year) !== ''
            || trim((string) $m->mileage) !== '';

        if ($hasJson || $hasBasics) {
            return self::STATUS_OK;
        }

        return self::STATUS_WARN;
    }
}
