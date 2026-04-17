<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Волна 2: программы — дополнительные шаги сценария.
 */
final class ProgramsSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [
            'programs.two_visible_programs' => new SetupItemDefinition(
                key: 'programs.two_visible_programs',
                categoryKey: 'programs',
                title: 'Две опубликованные программы',
                description: 'Добавьте ещё одну программу или услугу, чтобы витрина выглядела увереннее.',
                importance: SetupItemImportance::Recommended,
                sortOrder: 15,
                filamentRouteName: 'filament.admin.resources.tenant-service-programs.index',
                targetKind: SetupItemTargetKind::Action,
                targetKey: 'programs.create_action',
                targetLabel: 'Список программ',
                prerequisiteKeys: [],
                skipAllowed: true,
                notNeededAllowed: true,
                launchCritical: false,
                profileDependencyKeys: [],
                completionRefreshTags: ['resource:programs'],
                themeConstraints: ['expert_auto', 'advocate_editorial'],
                featureConstraints: null,
                targetFallbackKeys: ['programs.program_form'],
                readinessTier: SetupReadinessTier::QuickLaunch,
                guidedNextHint: SetupGuidedNextHint::SaveThenNext,
                onboardingTrack: SetupOnboardingTrack::Programs,
                onboardingLayer: null,
            ),
        ];
    }
}
