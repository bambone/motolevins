@php
    $t = $data['title'] ?? 'Контакты';
    $desc = $data['description'] ?? '';
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $wa = $data['whatsapp'] ?? '';
    $tg = $data['telegram'] ?? '';
    $addr = $data['address'] ?? '';
    $hours = $data['working_hours'] ?? '';
    $mapEmbed = $data['map_embed'] ?? '';
    $mapLink = $data['map_link'] ?? '';
@endphp
<section class="w-full min-w-0 rounded-xl border border-white/10 bg-white/[0.03] p-5 sm:p-6" data-page-section-type="{{ $section->section_type }}">
    @if(filled($t))
        <h2 class="mb-2 text-xl font-semibold text-white sm:text-2xl">{{ $t }}</h2>
    @endif
    @if(filled($desc))
        <p class="mb-6 text-sm leading-relaxed text-silver sm:text-base">{{ $desc }}</p>
    @endif
    <dl class="grid gap-4 text-sm text-silver sm:grid-cols-2 sm:gap-6 sm:text-base">
        @if(filled($phone))
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Телефон</dt>
                <dd class="mt-1"><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="text-white underline decoration-white/30 underline-offset-2 hover:decoration-white">{{ $phone }}</a></dd>
            </div>
        @endif
        @if(filled($email))
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Email</dt>
                <dd class="mt-1"><a href="mailto:{{ $email }}" class="text-white underline decoration-white/30 underline-offset-2 hover:decoration-white">{{ $email }}</a></dd>
            </div>
        @endif
        @if(filled($wa))
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">WhatsApp</dt>
                <dd class="mt-1 break-all text-white">{{ $wa }}</dd>
            </div>
        @endif
        @if(filled($tg))
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Telegram</dt>
                <dd class="mt-1 break-all text-white">{{ $tg }}</dd>
            </div>
        @endif
        @if(filled($addr))
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Адрес</dt>
                <dd class="mt-1 whitespace-pre-line text-white/90">{{ $addr }}</dd>
            </div>
        @endif
        @if(filled($hours))
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-white/50">Режим работы</dt>
                <dd class="mt-1 whitespace-pre-line text-white/90">{{ $hours }}</dd>
            </div>
        @endif
    </dl>
    @if(filled($mapLink))
        <p class="mt-6">
            <a href="{{ $mapLink }}" class="text-sm font-medium text-primary-300 underline underline-offset-2 hover:text-primary-200" target="_blank" rel="noopener noreferrer">Открыть на карте</a>
        </p>
    @endif
    @if(filled($mapEmbed))
        <div class="mt-6 overflow-hidden rounded-lg border border-white/10">
            <div class="aspect-video w-full max-h-80 [&_iframe]:h-full [&_iframe]:min-h-[200px] [&_iframe]:w-full">
                {!! $mapEmbed !!}
            </div>
        </div>
    @endif
</section>
