<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Преобразует setup.profile.primary_goal в тексты и приоритетные дорожки (согласовано с {@see SetupJourneyOrdering}).
 */
final class SetupPrimaryGoalPresenter
{
    public function __construct(
        private readonly SetupProfileRepository $profiles,
    ) {}

    public function present(int $tenantId): SetupPrimaryGoalPresentation
    {
        $code = trim((string) ($this->profiles->getMerged($tenantId)['primary_goal'] ?? ''));
        if ($code === '') {
            $code = 'default';
        }

        return match ($code) {
            'booking' => new SetupPrimaryGoalPresentation(
                code: 'booking',
                label: 'Получать записи и заявки',
                hint: 'В приоритете: контакты, программы или услуги, главная страница и сценарий записи.',
                recommendedTracks: $this->mergeRecommended([
                    SetupOnboardingTrack::Contacts->value,
                    SetupOnboardingTrack::Programs->value,
                    SetupOnboardingTrack::Content->value,
                    SetupOnboardingTrack::Base->value,
                ]),
            ),
            'leads' => new SetupPrimaryGoalPresentation(
                code: 'leads',
                label: 'Собирать обращения',
                hint: 'В приоритете: контакты, блоки на главной и базовые настройки сайта.',
                recommendedTracks: $this->mergeRecommended([
                    SetupOnboardingTrack::Contacts->value,
                    SetupOnboardingTrack::Content->value,
                    SetupOnboardingTrack::Branding->value,
                ]),
            ),
            'catalog' => new SetupPrimaryGoalPresentation(
                code: 'catalog',
                label: 'Показывать каталог и предложения',
                hint: 'В приоритете: главная, программы/каталог, затем контакты.',
                recommendedTracks: $this->mergeRecommended([
                    SetupOnboardingTrack::Content->value,
                    SetupOnboardingTrack::Programs->value,
                    SetupOnboardingTrack::Catalog->value,
                    SetupOnboardingTrack::Contacts->value,
                ]),
            ),
            'info' => new SetupPrimaryGoalPresentation(
                code: 'info',
                label: 'Информационный сайт',
                hint: 'В приоритете: контент на главной и базовое оформление.',
                recommendedTracks: $this->mergeRecommended([
                    SetupOnboardingTrack::Content->value,
                    SetupOnboardingTrack::Branding->value,
                ]),
            ),
            default => new SetupPrimaryGoalPresentation(
                code: $code === 'default' ? 'default' : $code,
                label: $code === 'default' ? 'Без выбранной цели' : 'Цель: '.$code,
                hint: 'Задайте цель в профиле сайта — подскажем порядок шагов.',
                recommendedTracks: $this->mergeRecommended([]),
            ),
        };
    }

    /**
     * @param  list<string>  $values  SetupOnboardingTrack::value
     * @return array<string, bool>
     */
    private function mergeRecommended(array $values): array
    {
        $out = [];
        foreach (SetupOnboardingTrack::cases() as $track) {
            $out[$track->value] = false;
        }
        foreach ($values as $v) {
            $out[$v] = true;
        }

        return $out;
    }
}
