@props([
    'channel',
    /** @var \App\PageBuilder\Contacts\ResolvedContactChannel $channel */
    'variant' => 'moto_cta',
])
@php
    use App\PageBuilder\Contacts\ContactChannelType;
    $t = $channel->type;
    $motoCard = match ($t) {
        ContactChannelType::Whatsapp => 'border-[#25D366]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#25D366]/60 hover:shadow-xl hover:shadow-[#25D366]/15 focus-visible:ring-[#25D366]/50',
        ContactChannelType::Telegram => 'border-[#2AABEE]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#2AABEE]/60 hover:shadow-xl hover:shadow-[#2AABEE]/15 focus-visible:ring-[#2AABEE]/50',
        ContactChannelType::Vk => 'border-[#0077FF]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#0077FF]/60 hover:shadow-xl hover:shadow-[#0077FF]/15 focus-visible:ring-[#0077FF]/50',
        ContactChannelType::Instagram => 'border-[#E4405F]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#E4405F]/60 hover:shadow-xl hover:shadow-[#E4405F]/15 focus-visible:ring-[#E4405F]/50',
        ContactChannelType::FacebookMessenger => 'border-[#0084FF]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#0084FF]/60 hover:shadow-xl hover:shadow-[#0084FF]/15 focus-visible:ring-[#0084FF]/50',
        ContactChannelType::Viber => 'border-[#7360f2]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#7360f2]/60 hover:shadow-xl hover:shadow-[#7360f2]/15 focus-visible:ring-[#7360f2]/50',
        ContactChannelType::Max => 'border-[#7C4DFF]/35 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-[#7C4DFF]/60 hover:shadow-xl hover:shadow-[#7C4DFF]/15 focus-visible:ring-[#7C4DFF]/50',
        default => 'border-moto-amber/30 bg-gradient-to-b from-obsidian/95 to-carbon/85 ring-white/5 hover:-translate-y-0.5 hover:border-moto-amber/55 hover:shadow-xl hover:shadow-moto-amber/15 focus-visible:ring-moto-amber/50',
    };
    $motoIcon = match ($t) {
        ContactChannelType::Whatsapp => 'bg-[#25D366]/15 text-[#25D366] ring-[#25D366]/35 group-hover:bg-[#25D366]/25',
        ContactChannelType::Telegram => 'bg-[#2AABEE]/15 text-[#2AABEE] ring-[#2AABEE]/35 group-hover:bg-[#2AABEE]/25',
        ContactChannelType::Vk => 'bg-[#0077FF]/15 text-[#0077FF] ring-[#0077FF]/35 group-hover:bg-[#0077FF]/25',
        ContactChannelType::Instagram => 'bg-[#E4405F]/15 text-[#E4405F] ring-[#E4405F]/35 group-hover:bg-[#E4405F]/25',
        ContactChannelType::FacebookMessenger => 'bg-[#0084FF]/15 text-[#0084FF] ring-[#0084FF]/35 group-hover:bg-[#0084FF]/25',
        ContactChannelType::Viber => 'bg-[#7360f2]/15 text-[#7360f2] ring-[#7360f2]/35 group-hover:bg-[#7360f2]/25',
        ContactChannelType::Max => 'bg-[#7C4DFF]/15 text-[#7C4DFF] ring-[#7C4DFF]/35 group-hover:bg-[#7C4DFF]/25',
        default => 'bg-moto-amber/15 text-moto-amber ring-moto-amber/30 group-hover:bg-moto-amber/25',
    };
@endphp
@if($variant === 'moto_cta')
    <a href="{{ $channel->href }}"
       class="group flex min-h-[168px] flex-col justify-between rounded-2xl border p-7 shadow-lg shadow-black/35 ring-1 ring-inset transition-all duration-200 focus-visible:outline focus-visible:ring-2 sm:min-h-[176px] sm:p-8 {{ $motoCard }}"
       @if($channel->openInNewTab) target="_blank" rel="{{ $channel->rel }}" @endif
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-xl ring-1 transition {{ $motoIcon }}" aria-hidden="true">
            @include('tenant.partials.contact-channel-icon', ['icon' => $channel->icon, 'class' => 'h-6 w-6'])
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
       class="flex items-start gap-4 rounded-xl border border-white/8 bg-obsidian/40 p-5 ring-1 ring-inset ring-white/5 transition hover:border-white/15 focus-visible:outline focus-visible:ring-2 focus-visible:ring-moto-amber/45"
       @if($channel->openInNewTab) target="_blank" rel="{{ $channel->rel }}" @endif
    >
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/5 text-moto-amber/90" aria-hidden="true">
            @include('tenant.partials.contact-channel-icon', ['icon' => $channel->icon, 'class' => 'h-5 w-5'])
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
