<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

final class SetupCategoryRegistry
{
    /**
     * @return list<SetupCategoryDefinition>
     */
    public static function all(): array
    {
        return [
            new SetupCategoryDefinition('quick_launch', 'Быстрый запуск', 'База бренда и первое впечатление', 10, 1, true, 'heroicon-o-rocket-launch'),
            new SetupCategoryDefinition('contacts', 'Контакты', 'Как с вами связаться', 20, 2, true, 'heroicon-o-phone'),
            new SetupCategoryDefinition('programs', 'Программы', 'Услуги и предложения', 30, 3, true, 'heroicon-o-academic-cap'),
            new SetupCategoryDefinition('content', 'Контент', 'Главная страница и блоки', 40, 4, true, 'heroicon-o-document-text'),
            new SetupCategoryDefinition('seo', 'SEO', 'Поиск и метаданные', 50, 5, false, 'heroicon-o-magnifying-glass'),
            new SetupCategoryDefinition('infrastructure', 'Инфраструктура', 'Домен, интеграции, команда', 60, 6, false, 'heroicon-o-server-stack'),
        ];
    }
}
