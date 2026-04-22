<?php

declare(strict_types=1);

namespace App\NotificationCenter;

use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent: личный email-получатель (владелец тенанта) + правило crm_request.created.
 *
 * @see \App\Product\CRM\Actions\CreateCrmRequestFromPublicForm
 */
final class TenantCrmNewRequestEmailDefaults
{
    public const string SUBSCRIPTION_NAME = 'Новая заявка (email, владелец)';

    public const string DESTINATION_NAME = 'Email (владелец)';

    private const string BOOTSTRAP_MARK = 'tenant_crm_owner_email_v1';

    /**
     * @param  bool  $forceEnable  Если подписка создаётся заново — включить. Уже существующую не трогает.
     */
    public function ensureForTenant(Tenant $tenant, bool $forceEnable = true): void
    {
        $ownerId = $tenant->owner_user_id;
        if ($ownerId === null) {
            return;
        }

        $user = User::query()->find($ownerId);
        if ($user === null || ! $this->isNotifiableEmail($user->email)) {
            return;
        }

        DB::transaction(function () use ($tenant, $ownerId, $user, $forceEnable): void {
            $dest = NotificationDestination::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $ownerId)
                ->where('type', NotificationChannelType::Email->value)
                ->first();

            if ($dest === null) {
                $dest = NotificationDestination::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $ownerId,
                    'name' => self::DESTINATION_NAME,
                    'type' => NotificationChannelType::Email->value,
                    'status' => NotificationDestinationStatus::Verified->value,
                    'is_shared' => false,
                    'config_json' => ['email' => (string) $user->email],
                ]);
            } else {
                $config = is_array($dest->config_json) ? $dest->config_json : [];
                $config['email'] = (string) $user->email;
                $dest->update([
                    'name' => self::DESTINATION_NAME,
                    'config_json' => $config,
                ]);
            }

            $sub = NotificationSubscription::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $ownerId)
                ->where('event_key', 'crm_request.created')
                ->where('name', self::SUBSCRIPTION_NAME)
                ->first();

            if ($sub === null) {
                $sub = NotificationSubscription::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $ownerId,
                    'name' => self::SUBSCRIPTION_NAME,
                    'event_key' => 'crm_request.created',
                    'enabled' => $forceEnable,
                    'conditions_json' => ['__bootstrap' => self::BOOTSTRAP_MARK],
                    'schedule_json' => null,
                    'severity_min' => null,
                    'created_by_user_id' => null,
                ]);
            } else {
                $conditions = is_array($sub->conditions_json) ? $sub->conditions_json : [];
                if (! isset($conditions['__bootstrap'])) {
                    $conditions['__bootstrap'] = self::BOOTSTRAP_MARK;
                    $sub->update(['conditions_json' => $conditions]);
                }
            }

            if (! $sub->destinations()->where('notification_destinations.id', $dest->id)->exists()) {
                $sub->destinations()->attach($dest->id, [
                    'delivery_mode' => 'immediate',
                    'delay_seconds' => null,
                    'order_index' => 0,
                    'is_enabled' => true,
                ]);
            }
        });
    }

    private function isNotifiableEmail(?string $email): bool
    {
        if ($email === null) {
            return false;
        }

        $e = trim($email);

        return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
    }
}
