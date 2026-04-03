@php
    $h = $data['section_heading'] ?? '';
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
@endphp
<section>
    @if(filled($h))
        <h2 class="mb-6 text-balance text-xl font-bold text-white sm:text-2xl">{{ $h }}</h2>
    @endif
    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach($items as $item)
            <li class="rounded-xl border border-white/10 bg-white/5 p-4">
                <h3 class="font-semibold text-white">{{ $item['title'] ?? '' }}</h3>
                @if(filled($item['description'] ?? ''))
                    <p class="mt-2 text-sm text-silver">{{ $item['description'] }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
