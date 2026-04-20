<?php

namespace Database\Seeders;

use App\Models\DomainLocalizationPreset;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantFooterLink;
use App\Models\TenantFooterSection;
use App\Models\TenantSetting;
use App\Tenant\Footer\TenantFooterLinkKind;
use App\Tenant\Footer\FooterSectionType;
use App\Services\Tenancy\TenantDomainService;
use Illuminate\Database\Seeder;

class MotoLevinsTenantSeeder extends Seeder
{
    public function run(): void
    {
        $planId = Plan::query()->value('id');
        $motoPresetId = DomainLocalizationPreset::query()->where('slug', 'moto_rental')->value('id');

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'motolevins'],
            [
                'name' => 'Moto Levins',
                'brand_name' => 'Moto Levins',
                'theme_key' => 'moto',
                'status' => 'active',
                'timezone' => 'Europe/Moscow',
                'locale' => 'ru',
                'currency' => 'RUB',
                'plan_id' => $planId,
                'domain_localization_preset_id' => $motoPresetId,
            ]
        );

        if ($tenant->domain_localization_preset_id === null && $motoPresetId !== null) {
            $tenant->update(['domain_localization_preset_id' => $motoPresetId]);
        }

        app(TenantDomainService::class)->createDefaultSubdomain($tenant, $tenant->slug);

        $publicUrl = $this->canonicalPublicSiteUrlForSeededTenant($tenant);

        TenantSetting::setForTenant($tenant->id, 'general.site_name', 'Moto Levins');
        TenantSetting::setForTenant(
            $tenant->id,
            'general.footer_tagline',
            'Бронирование подтверждается оператором. Условия и детали согласуются перед выдачей.'
        );
        TenantSetting::setForTenant($tenant->id, 'general.domain', $publicUrl);
        TenantSetting::setForTenant($tenant->id, 'contacts.phone', '+7 (913) 060-86-89');
        TenantSetting::setForTenant($tenant->id, 'contacts.phone_alt', '');
        TenantSetting::setForTenant($tenant->id, 'contacts.whatsapp', '79130608689');
        TenantSetting::setForTenant($tenant->id, 'contacts.telegram', 'motolevins');
        TenantSetting::setForTenant($tenant->id, 'contacts.email', '');
        TenantSetting::setForTenant($tenant->id, 'contacts.address', '');
        TenantSetting::setForTenant(
            $tenant->id,
            'contacts.public_office_address',
            'Зона выдачи и выезда — по согласованию с оператором (Москва и область).'
        );
        TenantSetting::setForTenant($tenant->id, 'contacts.hours', '');
        TenantSetting::setForTenant($tenant->id, 'branding.primary_color', '#E85D04');

        $this->seedTypedFooterForTenant($tenant);

        $hosts = [];
        $defaultHost = config('app.tenant_default_host');
        if (is_string($defaultHost) && trim($defaultHost) !== '') {
            $hosts[] = trim($defaultHost);
        }
        if (app()->environment('local')) {
            $hosts[] = 'localhost';
            $hosts[] = '127.0.0.1';
        }

        foreach ($hosts as $index => $host) {
            $normalized = TenantDomain::normalizeHost((string) $host);
            if ($normalized === '') {
                continue;
            }

            if (TenantDomain::where('host', $normalized)->exists()) {
                continue;
            }

            TenantDomain::query()->create([
                'tenant_id' => $tenant->id,
                'host' => $normalized,
                'type' => TenantDomain::TYPE_SUBDOMAIN,
                'is_primary' => $index === 0 && ! $tenant->domains()->exists(),
                'status' => TenantDomain::STATUS_ACTIVE,
                'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
                'verified_at' => now(),
                'activated_at' => now(),
            ]);
        }
    }

    /**
     * Один источник правды с {@see TenantDomainService::createDefaultSubdomain}: slug + TENANCY_ROOT_DOMAIN, без env на каждого тенанта.
     *
     * Typed-секции подвала создаются/обновляются здесь; у tenant, созданного до появления таблиц или без повторного seed,
     * записей может не быть — тогда на сайте будет {@see \App\Tenant\Footer\TenantFooterResolver} в режиме minimal до backfill или ручного ввода в Filament.
     */
    private function seedTypedFooterForTenant(Tenant $tenant): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tenant_footer_sections')) {
            return;
        }

        TenantFooterSection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'section_key' => 'moto_contacts_v1',
            ],
            [
                'type' => FooterSectionType::CONTACTS,
                'meta_json' => [
                    'headline' => 'Контакты',
                    'description' => '',
                    'show_phone' => true,
                    'show_telegram' => true,
                    'show_whatsapp' => true,
                    'show_email' => false,
                    'show_address' => true,
                ],
                'sort_order' => 10,
                'is_enabled' => true,
            ],
        );

        TenantFooterSection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'section_key' => 'moto_geo_v1',
            ],
            [
                'type' => FooterSectionType::GEO_POINTS,
                'meta_json' => [
                    'headline' => 'География и точки',
                    'items' => [
                        'Москва и область — детали выдачи согласуются с оператором.',
                        'Самовывоз с точки выдачи — по предварительной договорённости.',
                    ],
                ],
                'sort_order' => 20,
                'is_enabled' => true,
            ],
        );

        TenantFooterSection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'section_key' => 'moto_conditions_v1',
            ],
            [
                'type' => FooterSectionType::CONDITIONS_LIST,
                'meta_json' => [
                    'headline' => 'Условия аренды (кратко)',
                    'items' => [
                        'Нужны паспорт и водительское удостоверение категории A.',
                        'Залог и подтверждение брони — по согласованию с оператором.',
                        'Полные правила — на странице «Правила аренды».',
                    ],
                ],
                'sort_order' => 30,
                'is_enabled' => true,
            ],
        );

        $navSection = TenantFooterSection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'section_key' => 'moto_nav_v1',
            ],
            [
                'type' => FooterSectionType::LINK_GROUPS,
                'meta_json' => [
                    'headline' => 'Навигация и документы',
                    'group_titles' => ['default' => ''],
                ],
                'sort_order' => 40,
                'is_enabled' => true,
            ],
        );

        // Относительные пути — без route() (корректно при сидере/CLI и на любом домене тенанта).
        $navLinks = [
            ['label' => 'Автопарк', 'url' => '/motorcycles', 'sort_order' => 0],
            ['label' => 'Контакты', 'url' => '/contacts', 'sort_order' => 10],
            ['label' => 'Правила аренды', 'url' => '/usloviya-arenda', 'sort_order' => 20],
            ['label' => 'Политика конфиденциальности', 'url' => '/politika-konfidencialnosti', 'sort_order' => 30],
        ];
        foreach ($navLinks as $row) {
            TenantFooterLink::query()->updateOrCreate(
                [
                    'section_id' => $navSection->id,
                    'label' => $row['label'],
                ],
                [
                    'group_key' => null,
                    'url' => $row['url'],
                    'link_kind' => TenantFooterLinkKind::Internal,
                    'target' => '_self',
                    'sort_order' => $row['sort_order'],
                    'is_enabled' => true,
                ],
            );
        }

        TenantFooterSection::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'section_key' => 'moto_bottom_v1',
            ],
            [
                'type' => FooterSectionType::BOTTOM_BAR,
                'meta_json' => [
                    'copyright_text' => '',
                    'secondary_text' => '',
                ],
                'sort_order' => 90,
                'is_enabled' => true,
            ],
        );
    }

    private function canonicalPublicSiteUrlForSeededTenant(Tenant $tenant): string
    {
        $root = trim((string) config('tenancy.root_domain', ''), " \t\n\r\0\x0B.");
        $appUrl = (string) config('app.url', '');
        $scheme = str_starts_with($appUrl, 'https://') ? 'https' : 'http';

        if ($root !== '') {
            return $scheme.'://'.TenantDomain::normalizeHost($tenant->slug.'.'.$root);
        }

        $host = parse_url($appUrl, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return $scheme.'://'.TenantDomain::normalizeHost($tenant->slug.'.'.$host);
        }

        return rtrim($appUrl, '/') ?: 'http://localhost';
    }
}
