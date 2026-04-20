@php
    $meta = $block['meta'] ?? [];
    $presentation = $block['presentation'] ?? [];
    $showPhone = ! empty($meta['show_phone']);
    $showTg = ! empty($meta['show_telegram']);
    $showWa = ! empty($meta['show_whatsapp']);
    $showEmail = ! empty($meta['show_email']);
    $showAddr = ! empty($meta['show_address']);
    $hasContactBlock = ($showPhone && filled($presentation['phone_href'] ?? null))
        || ($showTg && filled($presentation['telegram_url'] ?? null))
        || ($showWa && filled($presentation['whatsapp_url'] ?? null))
        || ($showEmail && filled($presentation['email'] ?? null))
        || ($showAddr && filled($presentation['office_address'] ?? null));
@endphp
@if($hasContactBlock)
    <section class="tenant-site-footer-moto__card mb-8 rounded-[1.25rem] border border-white/[0.08] bg-[rgb(18_22_28)]/95 p-6 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] sm:p-8 md:p-10">
        <h3 class="text-balance text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ filled($meta['headline'] ?? '') ? $meta['headline'] : 'Контакты' }}</h3>
        @if(filled($meta['description'] ?? ''))
            <p class="mt-3 max-w-prose text-pretty text-sm text-white/75">{{ \App\Support\Typography\RussianTypography::tiePrepositionsToNextWord((string) $meta['description']) }}</p>
        @endif
        <div class="mt-8 grid gap-10 md:grid-cols-2 md:gap-12 lg:gap-16">
            <div class="min-w-0 space-y-8">
                @if($showPhone && filled($presentation['phone_href'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">Телефон</p>
                        <a href="{{ $presentation['phone_href'] }}" class="mt-2 inline-flex min-h-10 items-center text-lg font-semibold tracking-tight text-white transition-colors hover:text-moto-amber">{{ $presentation['phone_display'] }}</a>
                    </div>
                @endif
                @if(filled($presentation['vk_url'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">Написать во ВКонтакте</p>
                        <a href="{{ $presentation['vk_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex min-h-10 max-w-full items-center break-all text-[15px] font-medium text-white underline-offset-4 transition-colors hover:text-moto-amber hover:underline">{{ $presentation['vk_url'] }}</a>
                    </div>
                @endif
                @if($showAddr && filled($presentation['office_address'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">Адрес и зона выезда</p>
                        <p class="mt-2 text-pretty text-[15px] leading-relaxed text-white/90">{{ \App\Support\Typography\RussianTypography::tiePrepositionsToNextWord((string) $presentation['office_address']) }}</p>
                    </div>
                @endif
            </div>
            <div class="min-w-0 space-y-8">
                @if($showTg && filled($presentation['telegram_url'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">Telegram</p>
                        <a href="{{ $presentation['telegram_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex min-h-10 items-center text-base font-medium text-white/95 transition-colors hover:text-moto-amber">{{ $presentation['telegram_display'] ?? '' }}</a>
                    </div>
                @endif
                @if($showEmail && filled($presentation['email'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">Написать на почту</p>
                        <a href="{{ $presentation['email_href'] ?? ('mailto:'.$presentation['email']) }}" class="mt-2 inline-flex min-h-10 max-w-full items-center break-all text-[15px] font-medium text-white transition-colors hover:text-moto-amber">{{ $presentation['email'] }}</a>
                    </div>
                @endif
                @if($showWa && filled($presentation['whatsapp_url'] ?? null))
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/45">WhatsApp</p>
                        <a href="{{ $presentation['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex min-h-10 items-center text-base font-semibold text-white/95 underline-offset-4 transition hover:text-moto-amber hover:underline">Написать в WhatsApp</a>
                    </div>
                @endif
            </div>
        </div>
        <p class="mt-8 border-t border-white/[0.06] pt-6">
            <a href="/contacts" class="inline-flex min-h-10 items-center text-[14px] font-semibold text-moto-amber underline-offset-4 transition hover:underline">Страница контактов и форма связи</a>
        </p>
    </section>
@endif
