@php
    $catalogLocations = $catalogLocations ?? collect();
    $selectedCatalogLocation = $selectedCatalogLocation ?? null;
    $catalogLocationFormAction = $catalogLocationFormAction ?? route('home');
@endphp
@if($catalogLocations->isNotEmpty())
    <div class="mb-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 sm:mb-8 sm:p-5">
        <form method="get" action="{{ $catalogLocationFormAction }}" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
            <div class="min-w-0 flex-1">
                <label for="public-catalog-location" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-400">Точка выдачи</label>
                <select name="{{ \App\Services\Catalog\TenantPublicCatalogLocationService::QUERY_KEY }}" id="public-catalog-location"
                    class="h-11 w-full max-w-md rounded-lg border border-white/10 bg-black/40 px-3 text-sm text-white [color-scheme:dark]"
                    onchange="this.form.submit()">
                    <option value="all" @selected($selectedCatalogLocation === null)>Все точки</option>
                    @foreach($catalogLocations as $loc)
                        <option value="{{ $loc->slug }}" @selected($selectedCatalogLocation !== null && (int) $selectedCatalogLocation->id === (int) $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="max-w-xl text-xs leading-relaxed text-zinc-500 sm:text-sm">
                Пока точка не выбрана, показываем весь каталог. После выбора список и бронирование сужаются до доступных в этой точке моделей.
            </p>
            <noscript>
                <button type="submit" class="tenant-btn-secondary min-h-11 shrink-0 px-4 text-sm">Применить</button>
            </noscript>
        </form>
    </div>
@endif
