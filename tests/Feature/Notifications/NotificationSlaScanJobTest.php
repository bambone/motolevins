<?php

namespace Tests\Feature\Notifications;

use App\Jobs\ScanCrmRequestSlaNotificationsJob;
use App\Models\CrmRequest;
use App\Models\NotificationEvent;
use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\CrmRequestNotificationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationSlaScanJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unviewed_5m_emitted_once_with_stable_dedupe(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'sla-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00', 'UTC'));

        $crm = CrmRequest::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead',
            'phone' => '+70000000000',
            'email' => null,
            'message' => 'x',
            'request_type' => 'tenant_booking',
            'source' => 'test',
            'channel' => 'web',
            'pipeline' => 'inbound',
            'status' => CrmRequest::STATUS_NEW,
            'last_activity_at' => now(),
            'first_viewed_at' => null,
        ]);
        $crm->forceFill([
            'created_at' => Carbon::parse('2026-04-06 11:54:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-04-06 11:54:00', 'UTC'),
        ])->saveQuietly();

        app(ScanCrmRequestSlaNotificationsJob::class)->handle(
            app(NotificationEventRecorder::class),
            app(CrmRequestNotificationPresenter::class),
        );

        $this->assertSame(1, NotificationEvent::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_key', 'crm_request.unviewed_5m')
            ->where('subject_id', $crm->id)
            ->count());

        app(ScanCrmRequestSlaNotificationsJob::class)->handle(
            app(NotificationEventRecorder::class),
            app(CrmRequestNotificationPresenter::class),
        );

        $this->assertSame(1, NotificationEvent::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_key', 'crm_request.unviewed_5m')
            ->where('subject_id', $crm->id)
            ->count());

        $crm->update(['first_viewed_at' => now()]);

        app(ScanCrmRequestSlaNotificationsJob::class)->handle(
            app(NotificationEventRecorder::class),
            app(CrmRequestNotificationPresenter::class),
        );

        $this->assertSame(1, NotificationEvent::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_key', 'crm_request.unviewed_5m')
            ->where('subject_id', $crm->id)
            ->count());
    }
}
