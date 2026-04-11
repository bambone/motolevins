<?php

namespace App\Tenant\Expert;

/**
 * Пресеты обложек карточек программ для темы expert_auto: файлы в bundled-теме на публичном диске
 * {@code tenants/_system/themes/expert_auto/program-covers/…} (см. theme:push-system-bundled, expert:seed-system-program-covers).
 */
final class ExpertAutoProgramCoverRegistry
{
    public const THEME_KEY = 'expert_auto';

    /**
     * @return array<string, array{desktop: string, mobile: string}>
     */
    public static function relativeFilesByProgramSlug(): array
    {
        return [
            'single-session' => ['desktop' => 'single-session.webp', 'mobile' => 'single-session-mobile.webp'],
            'confidence' => ['desktop' => 'confidence.webp', 'mobile' => 'confidence-mobile.webp'],
            'counter-emergency' => ['desktop' => 'counter-emergency.webp', 'mobile' => 'counter-emergency-mobile.webp'],
            'parking' => ['desktop' => 'parking.webp', 'mobile' => 'parking-mobile.webp'],
            'city-driving' => ['desktop' => 'city-driving.webp', 'mobile' => 'city-driving-mobile.webp'],
            'route' => ['desktop' => 'route.webp', 'mobile' => 'route-mobile.webp'],
            'motorsport' => ['desktop' => 'motorsport.webp', 'mobile' => 'motorsport-mobile.webp'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function imageAltByProgramSlug(): array
    {
        return [
            'single-session' => 'Индивидуальное занятие по вождению',
            'confidence' => 'Уверенное спокойное вождение в городе',
            'counter-emergency' => 'Контраварийная подготовка на зимней площадке',
            'parking' => 'Практика парковки в городских условиях',
            'city-driving' => 'Городское движение и перестроения',
            'route' => 'Практика по маршруту в реальном районе',
            'motorsport' => 'Тренировка и сопровождение в автоспорте',
        ];
    }
}
