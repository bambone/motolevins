<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\CustomDomainResource\Pages\EditCustomDomain;
use App\Filament\Tenant\Support\CustomDomainDnsRegistrarGuide;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CustomDomainDnsGuideFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_edit_custom_domain_dns_guide_renders_host_token_prefix_and_reg_ru_option(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenant = Tenant::query()->create([
            'name' => 'DNS Guide Tenant',
            'slug' => 'dns-guide-tenant',
            'status' => 'active',
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dns-guide-tenant.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dnsguide-feat.invalid',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'verification_method' => 'dns_txt',
            'verification_token' => 'rb-test-token-dns-guide-unique',
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant));

        $prefix = (string) config('tenancy.custom_domains.verification_prefix');
        $ip = (string) config('tenancy.server_ip');

        Livewire::test(EditCustomDomain::class, ['record' => $domain->getKey()])
            ->assertSee('Подключение домена')
            ->assertSee('Где у вас управляется DNS')
            ->assertSee('Не сохраняется')
            // Подпись кнопки из перевода Filament; при смене локали/копирайта проверить вручную.
            ->assertDontSee('Сохранить')
            ->assertSee('dnsguide-feat.invalid')
            ->assertSee('rb-test-token-dns-guide-unique')
            ->assertSee($prefix)
            ->assertSee($ip)
            ->assertSee('REG.RU')
            ->assertSee('Общая инструкция')
            ->assertSee('data-dns-cname-www');
    }

    public function test_dns_guide_switches_registrar_content_between_reg_ru_and_nic_ua(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenant = Tenant::query()->create([
            'name' => 'DNS Guide Switch',
            'slug' => 'dns-guide-switch',
            'status' => 'active',
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dns-guide-switch.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dnsguide-switch.invalid',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'verification_method' => 'dns_txt',
            'verification_token' => 'rb-switch-token',
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant));

        $regRuMarker = 'DNS-серверы и управление зоной';
        $nicUaMarker = '«парковых» NS';

        Livewire::test(EditCustomDomain::class, ['record' => $domain->getKey()])
            ->set('dnsRegistrarGuideKey', CustomDomainDnsRegistrarGuide::KEY_REG_RU)
            ->assertSee($regRuMarker)
            ->set('dnsRegistrarGuideKey', CustomDomainDnsRegistrarGuide::KEY_NIC_UA)
            ->assertSee($nicUaMarker)
            ->assertDontSee($regRuMarker);
    }

    public function test_subdomain_custom_host_hides_www_cname_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenant = Tenant::query()->create([
            'name' => 'DNS Subdomain',
            'slug' => 'dns-subdomain',
            'status' => 'active',
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dns-subdomain.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'www.dnsguide-sub.invalid',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'verification_method' => 'dns_txt',
            'verification_token' => 'rb-sub-token',
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant));

        Livewire::test(EditCustomDomain::class, ['record' => $domain->getKey()])
            ->assertDontSee('data-dns-cname-www')
            ->assertSee('data-dns-guide="subdomain-context"', false);
    }

    public function test_missing_verification_token_shows_warning(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenant = Tenant::query()->create([
            'name' => 'DNS No Token',
            'slug' => 'dns-no-token',
            'status' => 'active',
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dns-no-token.apex.test',
            'type' => TenantDomain::TYPE_SUBDOMAIN,
            'is_primary' => true,
            'status' => TenantDomain::STATUS_ACTIVE,
            'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
            'verified_at' => now(),
            'activated_at' => now(),
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => 'dnsguide-notoken.invalid',
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'verification_method' => 'dns_txt',
            'verification_token' => null,
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant));

        Livewire::test(EditCustomDomain::class, ['record' => $domain->getKey()])
            ->assertSee('Нет кода верификации TXT');
    }
}
