@php
    $heading = $data['heading'] ?? '';
    $desc = $data['description'] ?? '';
@endphp
<section class="rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-8">
    @if(filled($heading))
        <h2 class="text-xl font-bold text-white sm:text-2xl">{{ $heading }}</h2>
    @endif
    @if(filled($desc))
        <p class="mt-3 text-silver">{{ $desc }}</p>
    @endif
    <ul class="mt-4 space-y-2 text-sm text-silver">
        @if(filled($data['phone'] ?? ''))
            <li><span class="text-white/80">Телефон:</span> {{ $data['phone'] }}</li>
        @endif
        @if(filled($data['whatsapp'] ?? ''))
            <li><span class="text-white/80">WhatsApp:</span> {{ $data['whatsapp'] }}</li>
        @endif
        @if(filled($data['telegram'] ?? ''))
            <li><span class="text-white/80">Telegram:</span> {{ $data['telegram'] }}</li>
        @endif
        @if(filled($data['address'] ?? ''))
            <li><span class="text-white/80">Адрес:</span> {{ $data['address'] }}</li>
        @endif
        @if(filled($data['map_url'] ?? ''))
            <li><a href="{{ e($data['map_url']) }}" class="text-amber-400 underline hover:text-amber-300">Открыть карту</a></li>
        @endif
    </ul>
    @if(filled($data['map_embed_html'] ?? ''))
        <div class="mt-4 min-h-[200px] overflow-hidden rounded-xl border border-white/10">
            {!! $data['map_embed_html'] !!}
        </div>
    @endif
</section>
