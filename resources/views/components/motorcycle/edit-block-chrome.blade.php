@props([
    'title',
    'description' => null,
    'saveLabel' => 'Сохранить',
    'statusText' => '',
])

<div {{ $attributes->merge(['class' => 'motorcycle-block-editor fi-section rounded-xl border border-gray-200 p-4 shadow-sm dark:border-white/10']) }}>
    <div class="mb-4">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
        @if (filled($description))
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>

    {{ $slot }}

    <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
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
            class="fi-btn fi-btn-color-primary inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
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
