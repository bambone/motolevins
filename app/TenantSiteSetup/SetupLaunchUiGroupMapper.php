<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Продуктовые группы для UI «Обзор запуска» (не путать с внутренним {@see SetupItemDefinition::categoryKey}).
 */
final class SetupLaunchUiGroupMapper
{
    public const LAUNCH_BASICS = 'launch_basics';

    public const LAUNCH_CONTENT = 'launch_content';

    public const LAUNCH_COMMUNICATION = 'launch_communication';

    /** Расширенные пункты ({@see SetupReadinessTier::Extended}) после базового контура. */
    public const LAUNCH_POLISH = 'launch_polish';

    public static function uiGroupForItemKey(string $itemKey): string
    {
        if (in_array($itemKey, ['settings.favicon', 'settings.analytics_counters', 'settings.public_canonical_url'], true)) {
            return self::LAUNCH_POLISH;
        }
        if (str_starts_with($itemKey, 'settings.')) {
            return self::LAUNCH_BASICS;
        }
        if ($itemKey === 'contact_channels.primary_phone') {
            return self::LAUNCH_BASICS;
        }
        if ($itemKey === 'contact_channels.preferred_contact_channel') {
            return self::LAUNCH_COMMUNICATION;
        }
        if (str_starts_with($itemKey, 'programs.') || str_starts_with($itemKey, 'pages.')) {
            return self::LAUNCH_CONTENT;
        }

        return self::LAUNCH_BASICS;
    }

    public static function uiGroupLabel(string $uiGroup): string
    {
        return match ($uiGroup) {
            self::LAUNCH_BASICS => 'Базовый запуск',
            self::LAUNCH_CONTENT => 'Контент запуска',
            self::LAUNCH_COMMUNICATION => 'Коммуникация',
            self::LAUNCH_POLISH => 'Расширенный запуск',
            default => $uiGroup,
        };
    }

    /**
     * @return list<string>
     */
    public static function orderedUiGroups(): array
    {
        return [
            self::LAUNCH_BASICS,
            self::LAUNCH_CONTENT,
            self::LAUNCH_COMMUNICATION,
            self::LAUNCH_POLISH,
        ];
    }
}
