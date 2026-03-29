<?php

namespace Tests\Feature;

use App\Jobs\Mail\SendTenantMailableJob;
use App\Mail\AdminIssuedPasswordMail;
use App\Models\Tenant;
use App\Models\TenantMailLog;
use App\Models\User;
use App\Services\CurrentTenantManager;
use App\Services\Mail\TenantMailer;
use App\Services\Mail\TenantMailLimitResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TenantMailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['cache.default' => 'array', 'queue.default' => 'sync']);
    }

    public function test_tenant_mailer_throws_without_tenant_context(): void
    {
        $user = User::factory()->create();

        $this->expectException(\LogicException::class);

        app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'Secret12345'));
    }

    public function test_tenant_mailer_dispatches_job_and_creates_log(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        app(CurrentTenantManager::class)->setTenant($tenant);

        app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'Secret12345'));

        Queue::assertPushed(SendTenantMailableJob::class, function (SendTenantMailableJob $job) use ($tenant, $user): bool {
            return $job->tenantId === $tenant->id && ($job->to[0] ?? '') === $user->email;
        });

        $this->assertDatabaseHas('tenant_mail_logs', [
            'tenant_id' => $tenant->id,
            'status' => TenantMailLog::STATUS_QUEUED,
        ]);
    }

    public function test_eleventh_mail_in_same_minute_is_throttled_and_not_sent(): void
    {
        Mail::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => 'active',
            'mail_rate_limit_per_minute' => 10,
        ]);

        $user = User::factory()->create();

        app(CurrentTenantManager::class)->setTenant($tenant);

        for ($i = 0; $i < 10; $i++) {
            app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'p'.$i));
        }

        app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'p11'));

        Mail::assertSent(AdminIssuedPasswordMail::class, 10);

        $this->assertSame(10, TenantMailLog::query()->where('status', TenantMailLog::STATUS_SENT)->count());
        $this->assertSame(1, TenantMailLog::query()->where('status', TenantMailLog::STATUS_DEFERRED)->count());
        $this->assertSame(11, TenantMailLog::query()->count());
    }

    public function test_tenant_isolation_for_rate_limit(): void
    {
        Mail::fake();

        $tenantA = Tenant::query()->create([
            'name' => 'A',
            'slug' => 'a',
            'status' => 'active',
            'mail_rate_limit_per_minute' => 10,
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'B',
            'slug' => 'b',
            'status' => 'active',
            'mail_rate_limit_per_minute' => 10,
        ]);

        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            app(CurrentTenantManager::class)->setTenant($tenantA);
            app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'a'.$i));
        }
        for ($i = 0; $i < 10; $i++) {
            app(CurrentTenantManager::class)->setTenant($tenantB);
            app(TenantMailer::class)->to($user->email)->queue(new AdminIssuedPasswordMail($user, 'b'.$i));
        }

        Mail::assertSent(AdminIssuedPasswordMail::class, 20);
        $this->assertSame(0, TenantMailLog::query()->where('status', TenantMailLog::STATUS_DEFERRED)->count());
    }

    public function test_limit_resolver_uses_tenant_field_and_defaults(): void
    {
        $resolver = app(TenantMailLimitResolver::class);

        $t = new Tenant(['mail_rate_limit_per_minute' => 60]);
        $this->assertSame(60, $resolver->resolvePerMinuteForTenant($t));

        $t2 = new Tenant(['mail_rate_limit_per_minute' => 0]);
        $this->assertSame($resolver->defaultPerMinute(), $resolver->resolvePerMinuteForTenant($t2));
    }
}
