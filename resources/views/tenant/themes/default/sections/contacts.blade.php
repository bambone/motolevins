@php
    use App\PageBuilder\Contacts\ContactChannelsResolver;

    $presentation = app(ContactChannelsResolver::class)->present(is_array($data ?? null) ? $data : []);
    $heading = $data['heading'] ?? '';
    $desc = $data['description'] ?? '';
    $channelRows = array_merge($presentation->primaryChannels, $presentation->secondaryChannels);
    $hasChannel = $channelRows !== []
        || $presentation->hasAddress()
        || filled($data['social_note'] ?? '')
        || $presentation->hasMap();
    if (! filled($heading) && ! filled($desc) && ! $hasChannel) {
        return;
    }
@endphp
<section class="rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-8">
    @if(filled($heading))
        <h2 class="text-xl font-bold text-white sm:text-2xl">{{ $heading }}</h2>
    @endif
    @if(filled($desc))
        <p class="mt-3 text-silver">{{ $desc }}</p>
    @endif
    @if($channelRows !== [] || $presentation->hasAddress())
        <dl class="mt-4 grid gap-4 text-sm text-silver sm:grid-cols-2 sm:gap-6 sm:text-base">
            @foreach($channelRows as $ch)
                @include('tenant.partials.contact-channel-link', ['channel' => $ch, 'variant' => 'default_dl'])
            @endforeach
            @if($presentation->hasAddress())
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Адрес</dt>
                    <dd class="mt-1 whitespace-pre-line text-white/90">{{ $presentation->address }}</dd>
                </div>
            @endif
        </dl>
    @endif
    @if(filled($data['social_note'] ?? ''))
        <p class="mt-4 text-sm text-silver/90">
            <span class="text-white/80">Дополнительно:</span> {{ $data['social_note'] }}
        </p>
    @endif
    @if($presentation->hasMap())
        <div class="mt-6">
            <x-custom-pages.contacts.map-block :view="$presentation->mapBlock" />
        </div>
    @endif
</section>
