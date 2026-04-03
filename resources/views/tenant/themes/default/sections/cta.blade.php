@php
    $heading = $data['heading'] ?? '';
    $body = $data['body'] ?? '';
    $btn = $data['button_text'] ?? '';
    $url = $data['button_url'] ?? '#';
@endphp
<section class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-6 sm:p-8">
    @if(filled($heading))
        <h2 class="text-xl font-bold text-white sm:text-2xl">{{ $heading }}</h2>
    @endif
    @if(filled($body))
        <p class="mt-3 text-silver">{{ $body }}</p>
    @endif
    @if(filled($btn))
        <a href="{{ e($url) }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-carbon hover:bg-amber-400">{{ $btn }}</a>
    @endif
</section>
