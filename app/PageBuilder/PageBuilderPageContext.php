<?php

namespace App\PageBuilder;

use App\Models\Page;

/**
 * Единый источник режима страницы для Page Sections Builder (без «плавающего» home/content).
 */
final readonly class PageBuilderPageContext
{
    public function __construct(
        public bool $isHome,
        public string $slug,
        /** landing | content */
        public string $mode,
        public string $modeLabel,
        public string $modeHint,
    ) {}

    public static function fromPage(Page $page): self
    {
        $slug = trim((string) ($page->slug ?? ''));
        $isHome = $slug === 'home';

        if ($isHome) {
            return new self(
                isHome: true,
                slug: $slug,
                mode: 'landing',
                modeLabel: 'Главная',
                modeHint: 'Лендинг: блоки ниже формируют публичную главную. Основной текст страницы здесь не задаётся.',
            );
        }

        return new self(
            isHome: false,
            slug: $slug,
            mode: 'content',
            modeLabel: 'Контентная страница',
            modeHint: 'Блоки дополняют основной текст этой страницы. Порядок = порядок на сайте.',
        );
    }

    /**
     * Подпись типа в карточке: на обычных страницах hero не называем «как на главной».
     */
    public function typeLabelForUi(string $typeId, string $registryLabel): string
    {
        if ($typeId === 'hero' && ! $this->isHome) {
            return 'Баннер страницы';
        }

        return $registryLabel;
    }

    /**
     * Описание в каталоге для hero на контентной странице.
     */
    public function catalogDescriptionForType(string $typeId, string $defaultDescription): string
    {
        if ($typeId === 'hero' && ! $this->isHome) {
            return 'Вводный баннер вверху этой страницы: тексты, медиа, кнопка. Не путать с главной — это только текущий URL.';
        }

        return $defaultDescription;
    }
}
