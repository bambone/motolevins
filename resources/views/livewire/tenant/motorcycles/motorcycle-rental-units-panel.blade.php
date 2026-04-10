<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 p-4 shadow-sm dark:border-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Единицы парка</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Конкретные экземпляры техники для учёта доступности и бронирования. CSV относится только к этой карточке.
        </p>
        <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-200">
            Единиц в карточке: {{ $unitsCount }}
        </p>
    </div>

    {{ $this->table }}

    <x-filament-actions::modals />
</div>
