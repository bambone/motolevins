<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Уведомления и бриф «запись + уведомления» в мастере запуска.
 */
final class NotificationsSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [
            'setup.booking_notifications_brief' => new SetupItemDefinition(
                key: 'setup.booking_notifications_brief',
                categoryKey: 'infrastructure',
                title: 'Бриф: запись и уведомления',
                description: 'Анкета в кабинете создаёт пресет, получателей и правила. Выполнение шага считается по актуальным объектам мастера (если их удалили — шаг снова открыт). Факт нажатия «Применить» дополнительно фиксируется в настройках для аудита.',
                importance: SetupItemImportance::Recommended,
                sortOrder: 50,
                filamentRouteName: 'filament.admin.pages.site-setup-booking-notifications',
                targetKind: SetupItemTargetKind::Page,
                targetKey: 'setup.booking_notifications_brief',
                targetLabel: 'Бриф записи и уведомлений',
                prerequisiteKeys: [],
                skipAllowed: true,
                notNeededAllowed: true,
                launchCritical: false,
                profileDependencyKeys: [],
                completionRefreshTags: [
                    'tenant_setting:setup.booking_notifications_applied_at',
                    'tenant_setting:setup.booking_notifications_questionnaire',
                    'resource:booking_settings_presets',
                    'resource:notification_destinations',
                    'resource:notification_subscriptions',
                ],
                themeConstraints: null,
                featureConstraints: null,
                readinessTier: SetupReadinessTier::Extended,
                guidedNextHint: SetupGuidedNextHint::SaveThenNext,
                onboardingTrack: null,
                onboardingLayer: null,
            ),
        ];
    }
}
