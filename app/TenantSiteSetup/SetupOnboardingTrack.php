<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Дорожка onboarding (см. SetupTracksResolver): влияет на порядок и применимость шагов в roadmap.
 */
enum SetupOnboardingTrack: string
{
    case Base = 'base';
    case Branding = 'branding';
    case Contacts = 'contacts';
    case Content = 'content';
    case Programs = 'programs';
    case Catalog = 'catalog';
    case Scheduling = 'scheduling';
    case Notifications = 'notifications';
    case Seo = 'seo';
    case Reviews = 'reviews';
    case Push = 'push';
}
