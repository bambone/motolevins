<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationEvent;
use App\Models\Tenant;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\NotificationPayloadDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationDedupeSameTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_records_same_dedupe_in_one_transaction_second_is_duplicate(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'dd-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);
        $payload = new NotificationPayloadDto('t', 'b', null, null, []);
        $recorder = app(NotificationEventRecorder::class);

        $first = null;
        $second = null;

        DB::transaction(function () use ($recorder, $tenant, $payload, &$first, &$second): void {
            $first = $recorder->record(
                $tenant->id,
                'crm_request.created',
                'CrmRequest',
                1,
                $payload,
                dedupeKey: 'race-key',
            );
            $second = $recorder->record(
                $tenant->id,
                'crm_request.created',
                'CrmRequest',
                1,
                $payload,
                dedupeKey: 'race-key',
            );
        });

        $this->assertFalse($first['duplicate']);
        $this->assertTrue($second['duplicate']);
        $this->assertSame(1, NotificationEvent::query()->where('tenant_id', $tenant->id)->count());
    }
}
