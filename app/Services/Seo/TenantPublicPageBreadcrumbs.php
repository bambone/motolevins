<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\LocationLandingPage;
use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\SeoLandingPage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Единый источник цепочки «Главная → … → текущая страница» для UI и JSON-LD.
 *
 * @phpstan-type Crumb array{name: string, url: string}
 */
final class TenantPublicPageBreadcrumbs
{
    public function __construct(
        private PublicBreadcrumbsBuilder $breadcrumbs,
    ) {}

    /**
     * @return list<Crumb>
     */
    public function resolve(Tenant $tenant, string $routeName, ?Model $model, string $canonicalUrl): array
    {
        if ($routeName === 'home') {
            return [];
        }

        if ($model instanceof Motorcycle && in_array($routeName, ['motorcycle.show', 'booking.show'], true)) {
            return $this->alignLastCrumbToCanonical(
                $this->breadcrumbs->forMotorcycle($tenant, $model),
                $canonicalUrl,
            );
        }

        if ($routeName === 'motorcycles.index') {
            return $this->alignLastCrumbToCanonical(
                $this->breadcrumbs->forMotorcyclesIndex($tenant),
                $canonicalUrl,
            );
        }

        if ($model instanceof LocationLandingPage && $routeName === 'location.show') {
            return $this->alignLastCrumbToCanonical(
                $this->breadcrumbs->forLocationLanding($tenant, $model),
                $canonicalUrl,
            );
        }

        if ($model instanceof SeoLandingPage && $routeName === 'seo_landing.show') {
            return $this->alignLastCrumbToCanonical(
                $this->breadcrumbs->forSeoLanding($tenant, $model),
                $canonicalUrl,
            );
        }

        if ($model instanceof Page) {
            return $this->alignLastCrumbToCanonical(
                $this->breadcrumbs->forCmsPage($tenant, $model),
                $canonicalUrl,
            );
        }

        $label = $this->labelForRouteWithoutPageModel($routeName);
        if ($label === null) {
            return [];
        }

        return $this->breadcrumbs->twoLevel($tenant, $label, $canonicalUrl);
    }

    /**
     * Последний пункт цепочки должен совпадать с абсолютным canonical (источник истины для rel=canonical).
     *
     * @param  list<Crumb>  $crumbs
     * @return list<Crumb>
     */
    private function alignLastCrumbToCanonical(array $crumbs, string $canonicalUrl): array
    {
        if ($crumbs === []) {
            return $crumbs;
        }
        $keys = array_keys($crumbs);
        $last = end($keys);
        if ($last === false) {
            return $crumbs;
        }
        $canonicalUrl = trim($canonicalUrl);
        if ($canonicalUrl === '') {
            return $crumbs;
        }
        $crumbs[$last]['url'] = $canonicalUrl;

        return $crumbs;
    }

    /**
     * Маршруты без загруженной модели Page: фиксированные подписи для второго уровня.
     */
    private function labelForRouteWithoutPageModel(string $routeName): ?string
    {
        return match ($routeName) {
            'reviews' => 'Отзывы',
            'faq' => 'Вопросы и ответы',
            'contacts' => 'Контакты',
            'terms' => 'Условия аренды',
            'prices' => 'Цены',
            'order' => 'Заявка на аренду',
            'about' => 'О компании',
            'offline' => 'Офлайн',
            'delivery.anapa' => 'Доставка в Анапу',
            'delivery.gelendzhik' => 'Доставка в Геленджик',
            'booking.index' => 'Онлайн-бронирование',
            'booking.checkout' => 'Оформление бронирования',
            'booking.thank-you' => 'Заявка принята',
            'articles.index' => 'Статьи',
            default => null,
        };
    }
}
