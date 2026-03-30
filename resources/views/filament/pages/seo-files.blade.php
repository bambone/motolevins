<x-filament-panels::page>
    <form wire:submit="saveSettings">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-3">
            <x-filament::button type="submit">
                Сохранить настройки SEO
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
