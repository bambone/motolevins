<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Слой готовности (quick → growth). Может задаваться в {@see SetupItemDefinition} или выводиться из tier.
 */
enum SetupOnboardingLayer: string
{
    case QuickLaunch = 'quick_launch';
    case PublicReadiness = 'public_readiness';
    case OperationalReadiness = 'operational_readiness';
    case GrowthReadiness = 'growth_readiness';
}
