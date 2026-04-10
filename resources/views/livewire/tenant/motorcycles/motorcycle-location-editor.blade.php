<div>
    <x-motorcycle.edit-block-chrome
        title="Доступность по локациям"
        description="Везде, только в выбранных точках или отдельно по каждой единице парка (если включены единицы). На сайте каталог можно сузить по выбранной локации."
        save-label="Сохранить локации"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
        @php
            $m = \App\Models\Motorcycle::query()->whereKey($recordId)->first();
        @endphp
        @if($m && $m->uses_fleet_units && ($m->location_mode?->value ?? '') === \App\Enums\MotorcycleLocationMode::PerUnit->value)
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                <p class="font-medium">Локации по единицам</p>
                <p class="mt-1 text-amber-900/90 dark:text-amber-100/90">Укажите локации на вкладке «Единицы парка» для каждой строки. При переключении с «общих» локаций карточки совпадающие наборы копируются на единицы без своих локаций при первом сохранении.</p>
            </div>
        @endif
    </x-motorcycle.edit-block-chrome>
</div>
