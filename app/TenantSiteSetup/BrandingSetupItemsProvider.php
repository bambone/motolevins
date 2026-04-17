<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Волна 1: брендинг — пункты сверх базового реестра.
 */
final class BrandingSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [
            'settings.branding_hero_social_image' => new SetupItemDefinition(
                key: 'settings.branding_hero_social_image',
                categoryKey: 'quick_launch',
                title: 'Картинка для шапки и соцсетей',
                description: 'Загрузите изображение для шапки сайта и превью в соцсетях (если тема их использует).',
                importance: SetupItemImportance::Recommended,
                sortOrder: 25,
                filamentRouteName: 'filament.admin.pages.settings',
                targetKind: SetupItemTargetKind::Field,
                targetKey: 'settings.branding_hero_social_image',
                targetLabel: 'Картинка шапки / OG',
                prerequisiteKeys: [],
                skipAllowed: true,
                notNeededAllowed: true,
                launchCritical: false,
                profileDependencyKeys: [],
                completionRefreshTags: ['tenant_setting:branding.hero', 'tenant_setting:branding.hero_path'],
                themeConstraints: null,
                featureConstraints: null,
                settingsTabKey: 'appearance',
                settingsSectionId: null,
                readinessTier: SetupReadinessTier::QuickLaunch,
                guidedNextHint: SetupGuidedNextHint::SaveThenNext,
                onboardingTrack: SetupOnboardingTrack::Branding,
                onboardingLayer: null,
            ),
        ];
    }
}
