<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendDailyOperationsDigestJob;
use App\Models\NotificationEvent;
use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\DigestOperationsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationDigestJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_digest_job_dedupes_per_tenant_per_day(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'dg-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);

        $day = Carbon::parse('2026-04-01', 'UTC');

        $job = new SendDailyOperationsDigestJob($day);
        $job->handle(
            app(NotificationEventRecorder::class),
            app(DigestOperationsPresenter::class),
        );

        $job->handle(
            app(NotificationEventRecorder::class),
            app(DigestOperationsPresenter::class),
        );

        $this->assertSame(1, NotificationEvent::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_key', 'digest.daily_operations')
            ->count());
    }
}
