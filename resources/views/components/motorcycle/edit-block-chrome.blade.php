@props([
    'title',
    'description' => null,
    'saveLabel' => 'Сохранить',
    'statusText' => '',
    'variant' => 'secondary',
    'compact' => false,
])

@php
    $variant = in_array($variant, ['primary', 'secondary', 'quiet'], true) ? $variant : 'secondary';
    $rootClass = match ($variant) {
        'primary' => 'fi-moto-chrome-primary border-primary-200/80 dark:border-primary-500/25',
        'quiet' => 'fi-moto-chrome-quiet border-dashed border-gray-200/90 dark:border-white/15',
        default => 'fi-moto-chrome-secondary',
    };
    $pad = $compact ? 'p-3 sm:p-4' : 'p-4';
    $titleClass = match ($variant) {
        'primary' => 'text-lg font-semibold text-gray-950 dark:text-white',
        'quiet' => 'text-sm font-medium text-gray-800 dark:text-gray-200',
        default => 'text-base font-semibold text-gray-950 dark:text-white',
    };
    $descClass = $compact
        ? 'mt-0.5 text-xs text-gray-600 dark:text-gray-400'
        : 'mt-1 text-sm text-gray-600 dark:text-gray-400';
    $mergeClass = 'motorcycle-block-editor fi-section rounded-xl border border-gray-200 shadow-sm dark:border-white/10 '.$rootClass.' '.$pad;
@endphp

<div {{ $attributes->merge(['class' => $mergeClass]) }}>
    <div @class(['mb-3' => $compact, 'mb-4' => ! $compact])>
        <h3 class="{{ $titleClass }}">{{ $title }}</h3>
        @if (filled($description))
            <p class="{{ $descClass }}">{{ $description }}</p>
        @endif
    </div>

    {{ $slot }}

    <div @class([
        'mt-3 flex flex-col gap-2 border-t border-gray-100 pt-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between' => $compact,
        'mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between' => ! $compact,
    ])>
        <p class="text-sm text-gray-600 dark:text-gray-400" wire:loading.remove wire:target="save">
            {{ $statusText }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400" wire:loading wire:target="save">
            Сохранение…
        </p>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
            @class([
                'fi-btn fi-btn-color-primary inline-flex min-h-9 items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold text-white disabled:opacity-60' => $compact,
                'fi-btn fi-btn-color-primary inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-60' => ! $compact,
            ])
        >
            <span wire:loading.remove wire:target="save">{{ $saveLabel }}</span>
            <span wire:loading wire:target="save">Сохранение…</span>
        </button>
    </div>

    @if ($errors->any())
        <ul class="mt-3 list-inside list-disc text-sm text-danger-600 dark:text-danger-400">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    @endif
</div>
