<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;
use App\Models\TenantServiceProgram;
use App\Models\User;
use App\TenantPush\TenantPushFeatureGate;
use Illuminate\Support\Facades\Gate;

/**
 * Снимок доступности модулей и поверхности UI для тенанта и текущего пользователя (источник истины для roadmap / guided).
 */
final readonly class SetupCapabilitySnapshot
{
    public function __construct(
        public bool $schedulingModuleEnabled,
        public bool $calendarIntegrationsEnabled,
        public bool $userCanManageScheduling,
        public bool $userCanManageNotifications,
        public bool $userCanManageNotificationDestinations,
        public bool $userCanManageNotificationSubscriptions,
        public bool $userCanManageSeoFiles,
        public bool $userCanManageReviews,
        public bool $userCanManagePages,
        public bool $userCanManageHomepage,
        public bool $pushSectionVisibleToUser,
        public bool $hasVisibleServiceProgram,
    ) {}

    public static function capture(Tenant $tenant, ?User $user): self
    {
        $gate = $user !== null ? Gate::forUser($user) : null;
        $allows = static fn (string $ability): bool => $gate?->allows($ability) ?? false;

        $push = app(TenantPushFeatureGate::class)->evaluate($tenant);

        $hasVisibleServiceProgram = TenantServiceProgram::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->exists();

        return new self(
            schedulingModuleEnabled: (bool) $tenant->scheduling_module_enabled,
            calendarIntegrationsEnabled: (bool) $tenant->calendar_integrations_enabled,
            userCanManageScheduling: $allows('manage_scheduling'),
            userCanManageNotifications: $allows('manage_notifications'),
            userCanManageNotificationDestinations: $allows('manage_notification_destinations'),
            userCanManageNotificationSubscriptions: $allows('manage_notification_subscriptions'),
            userCanManageSeoFiles: $allows('manage_seo_files'),
            userCanManageReviews: $allows('manage_reviews'),
            userCanManagePages: $allows('manage_pages'),
            userCanManageHomepage: $allows('manage_homepage'),
            pushSectionVisibleToUser: $push->canViewSection,
            hasVisibleServiceProgram: $hasVisibleServiceProgram,
        );
    }
}
