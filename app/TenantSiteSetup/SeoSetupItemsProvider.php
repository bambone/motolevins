<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Волна SEO: домен, мета, файлы — расширяется по мере продукта.
 */
final class SeoSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [
            'settings.public_canonical_url' => new SetupItemDefinition(
                key: 'settings.public_canonical_url',
                categoryKey: 'extended',
                title: 'Канонический URL сайта',
                description: 'Укажите основной адрес сайта с https — для SEO и корректных ссылок.',
                importance: SetupItemImportance::Recommended,
                sortOrder: 105,
                filamentRouteName: 'filament.admin.pages.settings',
                targetKind: SetupItemTargetKind::Field,
                targetKey: 'settings.public_canonical_url',
                targetLabel: 'Канонический URL',
                prerequisiteKeys: [],
                skipAllowed: true,
                notNeededAllowed: true,
                launchCritical: false,
                profileDependencyKeys: [],
                completionRefreshTags: ['tenant_setting:general.domain'],
                themeConstraints: null,
                featureConstraints: null,
                settingsTabKey: 'advanced',
                settingsSectionId: null,
                readinessTier: SetupReadinessTier::Extended,
                guidedNextHint: SetupGuidedNextHint::SaveThenNext,
                onboardingTrack: SetupOnboardingTrack::Seo,
                onboardingLayer: null,
            ),
        ];
    }
}
