@props([
    'channel',
    /** @var \App\PageBuilder\Contacts\ResolvedContactChannel $channel */
    'variant' => 'moto_cta',
])
@php
    use App\PageBuilder\Contacts\ContactChannelType;
    $t = $channel->type;
    $motoCard = match ($t) {
        ContactChannelType::Whatsapp => 'border-cc-wa/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-wa/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-wa/50',
        ContactChannelType::Telegram => 'border-cc-tg/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-tg/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-tg/50',
        ContactChannelType::Vk => 'border-cc-vk/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-vk/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-vk/50',
        ContactChannelType::Instagram => 'border-cc-ig/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-ig/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-ig/50',
        ContactChannelType::FacebookMessenger => 'border-cc-msg/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-msg/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-msg/50',
        ContactChannelType::Viber => 'border-cc-viber/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-viber/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-viber/50',
        ContactChannelType::Max => 'border-cc-max/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-cc-max/60 hover:shadow-xl hover:shadow-black/45 focus-visible:ring-cc-max/50',
        default => 'border-moto-amber/30 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-moto-amber/55 hover:shadow-xl hover:shadow-moto-amber/15 focus-visible:ring-moto-amber/50',
    };
    $motoIcon = match ($t) {
        ContactChannelType::Whatsapp => 'bg-cc-wa/20 text-cc-wa ring-cc-wa/40 group-hover:bg-cc-wa/30',
        ContactChannelType::Telegram => 'bg-cc-tg/20 text-cc-tg ring-cc-tg/40 group-hover:bg-cc-tg/30',
        ContactChannelType::Vk => 'bg-cc-vk/20 text-cc-vk ring-cc-vk/40 group-hover:bg-cc-vk/30',
        ContactChannelType::Instagram => 'bg-cc-ig/20 text-cc-ig ring-cc-ig/40 group-hover:bg-cc-ig/30',
        ContactChannelType::FacebookMessenger => 'bg-cc-msg/20 text-cc-msg ring-cc-msg/40 group-hover:bg-cc-msg/30',
        ContactChannelType::Viber => 'bg-cc-viber/20 text-cc-viber ring-cc-viber/40 group-hover:bg-cc-viber/30',
        ContactChannelType::Max => 'bg-cc-max/20 text-cc-max ring-cc-max/40 group-hover:bg-cc-max/30',
        default => 'bg-moto-amber/15 text-moto-amber ring-moto-amber/30 group-hover:bg-moto-amber/25',
    };
    $motoCompactShell = match ($t) {
        ContactChannelType::Whatsapp => 'border-cc-wa/25 bg-gradient-to-br from-cc-wa/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-wa/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-wa/50',
        ContactChannelType::Telegram => 'border-cc-tg/25 bg-gradient-to-br from-cc-tg/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-tg/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-tg/50',
        ContactChannelType::Vk => 'border-cc-vk/25 bg-gradient-to-br from-cc-vk/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-vk/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-vk/50',
        ContactChannelType::Instagram => 'border-cc-ig/25 bg-gradient-to-br from-cc-ig/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-ig/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-ig/50',
        ContactChannelType::FacebookMessenger => 'border-cc-msg/25 bg-gradient-to-br from-cc-msg/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-msg/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-msg/50',
        ContactChannelType::Viber => 'border-cc-viber/25 bg-gradient-to-br from-cc-viber/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-viber/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-viber/50',
        ContactChannelType::Max => 'border-cc-max/25 bg-gradient-to-br from-cc-max/12 via-obsidian/50 to-carbon/80 ring-white/10 shadow-md shadow-black/30 hover:border-cc-max/55 hover:shadow-lg hover:shadow-black/40 hover:-translate-y-0.5 focus-visible:ring-cc-max/50',
        default => 'border-white/12 bg-obsidian/40 ring-white/8 shadow-md shadow-black/25 hover:border-moto-amber/45 hover:shadow-lg hover:shadow-black/35 focus-visible:ring-moto-amber/45',
    };
@endphp
@if($variant === 'moto_cta')
    <a href="{{ $channel->href }}"
       class="group flex min-h-[168px] flex-col justify-between rounded-2xl border p-7 shadow-lg shadow-black/35 ring-1 ring-inset transition-all duration-200 focus-visible:outline focus-visible:ring-2 sm:min-h-[176px] sm:p-8 {{ $motoCard }}"
       @if($channel->openInNewTab) target="_blank" rel="{{ $channel->rel }}" @endif
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-xl ring-1 transition {{ $motoIcon }}" aria-hidden="true">
            <x-app-icon :name="$channel->icon" class="h-6 w-6" />
        </span>
        <div class="mt-5">
            <span class="block text-lg font-semibold text-white sm:text-xl">{{ $channel->ctaLabel }}</span>
            <span class="mt-1.5 block text-sm text-silver/90 transition group-hover:text-white">{{ $channel->displayValue }}</span>
            @if(filled($channel->note))
                <span class="mt-1 block text-xs text-silver/70">{{ $channel->note }}</span>
            @endif
        </div>
    </a>
@elseif($variant === 'moto_compact')
    <a href="{{ $channel->href }}"
       class="group flex items-start gap-4 rounded-2xl border p-5 ring-1 ring-inset transition-all duration-200 focus-visible:outline focus-visible:ring-2 sm:gap-5 sm:p-6 {{ $motoCompactShell }}"
       @if($channel->openInNewTab) target="_blank" rel="{{ $channel->rel }}" @endif
    >
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl ring-1 transition {{ $motoIcon }}" aria-hidden="true">
            <x-app-icon :name="$channel->icon" class="h-7 w-7 sm:h-8 sm:w-8" />
        </span>
        <div class="min-w-0">
            <h3 class="text-sm font-medium text-white/90">{{ $channel->ctaLabel }}</h3>
            <p class="mt-1 break-words text-sm text-silver/85">{{ $channel->displayValue }}</p>
            @if(filled($channel->note))
                <p class="mt-1 text-xs text-silver/65">{{ $channel->note }}</p>
            @endif
        </div>
    </a>
@else
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-white/50">{{ $channel->ctaLabel }}</dt>
        <dd class="mt-1">
            <a href="{{ $channel->href }}"
               class="text-white underline decoration-white/30 underline-offset-2 hover:decoration-white focus-visible:outline focus-visible:ring-2 focus-visible:ring-white/40 rounded-sm break-all"
               @if($channel->openInNewTab) target="_blank" rel="{{ $channel->rel }}" @endif
            >{{ $channel->displayValue }}</a>
            @if(filled($channel->note))
                <p class="mt-1 text-xs text-silver/75">{{ $channel->note }}</p>
            @endif
        </dd>
    </div>
@endif
