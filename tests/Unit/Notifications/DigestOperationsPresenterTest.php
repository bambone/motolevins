<?php

namespace Tests\Unit\Notifications;

use App\Models\Tenant;
use App\NotificationCenter\Presenters\DigestOperationsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DigestOperationsPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_payload_has_no_action_url_stays_tenant_agnostic_in_builder(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'dg-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);

        $payload = app(DigestOperationsPresenter::class)->dailyPayloadForTenant(
            $tenant,
            Carbon::parse('2026-04-01', 'UTC'),
        );

        $this->assertNull($payload->actionUrl);
        $this->assertNotSame('', trim($payload->title));
        $this->assertStringContainsString('2026', $payload->body);
    }
}
