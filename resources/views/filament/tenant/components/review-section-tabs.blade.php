@props([
    'active' => 'reviews',
    'reviewsUrl' => '#',
    'sourcesUrl' => '#',
    'candidatesUrl' => '#',
])
<nav data-review-section-tabs class="-mx-1 mb-4 flex flex-wrap gap-1 border-b border-gray-200 pb-px dark:border-white/10" aria-label="{{ __('Разделы отзывов') }}">
    <a
        href="{{ $reviewsUrl }}"
        @class([
            'fi-tabs-item flex items-center gap-x-2 rounded-t-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
            'fi-active fi-tabs-item-active bg-gray-50 text-primary-600 dark:bg-white/5 dark:text-primary-400' => $active === 'reviews',
            'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200' => $active !== 'reviews',
        ])
        wire:navigate
    >{{ __('Отзывы') }}</a>
    <a
        href="{{ $sourcesUrl }}"
        @class([
            'fi-tabs-item flex items-center gap-x-2 rounded-t-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
            'fi-active fi-tabs-item-active bg-gray-50 text-primary-600 dark:bg-white/5 dark:text-primary-400' => $active === 'sources',
            'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200' => $active !== 'sources',
        ])
        wire:navigate
    >{{ __('Источники') }}</a>
    <a
        href="{{ $candidatesUrl }}"
        @class([
            'fi-tabs-item flex items-center gap-x-2 rounded-t-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
            'fi-active fi-tabs-item-active bg-gray-50 text-primary-600 dark:bg-white/5 dark:text-primary-400' => $active === 'candidates',
            'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200' => $active !== 'candidates',
        ])
        wire:navigate
    >{{ __('Кандидаты') }}</a>
</nav>
