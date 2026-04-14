<?php

namespace App\Services\Seo;

use App\Models\LocationLandingPage;
use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoLandingPage;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Money\MoneyBindingRegistry;

final class FallbackSeoGenerator
{
    public function siteName(Tenant $tenant): string
    {
        $name = trim((string) TenantSetting::getForTenant($tenant->id, 'general.site_name', ''));

        if ($name !== '') {
            return $name;
        }

        return $tenant->defaultPublicSiteName();
    }

    /**
     * @return array{title: string, description: string, h1: string}
     */
    public function forRouteOnly(Tenant $tenant, string $routeName): array
    {
        $site = $this->siteName($tenant);

        return [
            'title' => $site,
            'description' => '',
            'h1' => $site,
        ];
    }

    /**
     * @return array{title: string, description: string, h1: string}
     */
    public function forPage(Tenant $tenant, Page $page): array
    {
        $site = $this->siteName($tenant);
        $pageName = trim((string) $page->name) ?: $page->slug;
        $title = $site === '' ? $pageName : $site.' — '.$pageName;
        $plain = $this->mainSectionPlainText($page);
        $description = $this->excerptFromPlain($plain, 160);
        if ($description === '') {
            $description = $this->excerptFromPlain($pageName, 160);
        }

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $pageName,
        ];
    }

    /**
     * @return array{title: string, description: string, h1: string}
     */
    public function forMotorcycle(Tenant $tenant, Motorcycle $moto): array
    {
        $site = $this->siteName($tenant);
        $name = trim((string) $moto->name) ?: (string) $moto->slug;
        $title = $site === '' ? $name : $name.' — '.$site;

        $plain = TenantSeoMerge::isFilled($moto->short_description)
            ? strip_tags((string) $moto->short_description)
            : (TenantSeoMerge::isFilled($moto->full_description)
                ? strip_tags((string) $moto->full_description)
                : '');
        if ($plain === '') {
            $card = $moto->catalogCardForView();
            $bits = array_filter([
                trim((string) ($card['positioning'] ?? '')),
                trim((string) ($card['scenario'] ?? '')),
            ]);
            $plain = implode(' ', $bits);
        }
        $description = $this->excerptFromPlain($plain, 160);
        if ($description === '') {
            $description = $this->excerptFromPlain('Аренда '.$name.' у '.$site.'.', 160);
        }

        $geoPriceBits = array_filter([
            trim((string) TenantSetting::getForTenant($tenant->id, 'general.primary_city', '')),
            $moto->price_per_day > 0 ? 'от '.tenant_money_format((int) $moto->price_per_day, MoneyBindingRegistry::MOTORCYCLE_PRICE_PER_DAY, $tenant).'/сутки' : '',
        ]);
        if ($geoPriceBits !== [] && $description !== '') {
            $description = $this->excerptFromPlain($description.' '.implode(', ', $geoPriceBits), 160);
        }

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $name,
        ];
    }

    /**
     * @return array{title: string, description: string, h1: string}
     */
    public function forLocationLandingPage(Tenant $tenant, LocationLandingPage $page): array
    {
        $site = $this->siteName($tenant);
        $titleName = trim((string) $page->title) ?: (string) $page->slug;
        $title = $site === '' ? $titleName : $titleName.' — '.$site;
        $plain = trim(strip_tags((string) ($page->intro ?? '')));
        if ($plain === '') {
            $plain = trim(strip_tags((string) ($page->body ?? '')));
        }
        $description = $this->excerptFromPlain($plain, 160);
        if ($description === '') {
            $description = $this->excerptFromPlain($titleName, 160);
        }
        $h1 = trim((string) ($page->h1 ?? '')) ?: $titleName;

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $h1,
        ];
    }

    /**
     * @return array{title: string, description: string, h1: string}
     */
    public function forSeoLandingPage(Tenant $tenant, SeoLandingPage $page): array
    {
        $site = $this->siteName($tenant);
        $titleName = trim((string) $page->title) ?: (string) $page->slug;
        $title = $site === '' ? $titleName : $titleName.' — '.$site;
        $plain = trim(strip_tags((string) ($page->intro ?? '')));
        if ($plain === '') {
            $plain = trim(strip_tags((string) ($page->body ?? '')));
        }
        $description = $this->excerptFromPlain($plain, 160);
        if ($description === '') {
            $description = $this->excerptFromPlain($titleName, 160);
        }
        $h1 = trim((string) ($page->h1 ?? '')) ?: $titleName;

        return [
            'title' => $title,
            'description' => $description,
            'h1' => $h1,
        ];
    }

    private function mainSectionPlainText(Page $page): string
    {
        $section = PageSection::query()
            ->where('page_id', $page->id)
            ->where('tenant_id', $page->tenant_id)
            ->where('section_key', 'main')
            ->where('status', 'published')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($section === null || ! is_array($section->data_json)) {
            return '';
        }

        $content = $section->data_json['content'] ?? null;
        if (! is_string($content) || $content === '') {
            return '';
        }

        return strip_tags($content);
    }

    private function excerptFromPlain(string $plain, int $maxLen): string
    {
        $plain = preg_replace('/\s+/u', ' ', trim($plain)) ?? '';
        if ($plain === '') {
            return '';
        }
        if (mb_strlen($plain) <= $maxLen) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $maxLen - 1)).'…';
    }
}
