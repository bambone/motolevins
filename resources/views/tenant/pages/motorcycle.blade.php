@php
    $seoMeta = $seoMeta ?? null;
    /** @var array{audience: string, use_case: array, advantages: array, rental_notes: string} $detailContent */
    $detailContent = $detailContent ?? ['audience' => '', 'use_case' => [], 'advantages' => [], 'rental_notes' => ''];
    $galleryUrls = $galleryUrls ?? [];
    /** @var array<string, array<int, array{0: string, 1: string}>> $specGroups */
    $specGroups = $specGroups ?? [];
    $relatedMotorcycles = $relatedMotorcycles ?? collect();
    $contactTelHref = $contactTelHref ?? null;
    $contactEmail = $contactEmail ?? '';
    $telLink = filled($contactTelHref) ? 'tel:'.preg_replace('/\s+/', '', (string) $contactTelHref) : null;

    $heroCard = $motorcycle->catalogCardForView();
    $heroTagline = trim((string) ($motorcycle->short_description ?? ''));
    if ($heroTagline === '' && filled($heroCard['positioning'] ?? '')) {
        $heroTagline = $heroCard['positioning'];
    }
    if ($heroTagline === '' && filled($heroCard['scenario'] ?? '')) {
        $heroTagline = $heroCard['scenario'];
    }
    $heroChips = array_slice($heroCard['highlights'] ?? [], 0, 3);
@endphp
@extends('tenant.layouts.app')

@section('content')
    <div class="mx-auto w-full min-w-0 max-w-7xl px-4 pb-14 pt-24 sm:px-6 sm:pb-16 sm:pt-28 lg:px-8">
        <nav class="mb-5 w-full min-w-0 text-sm" aria-label="Навигация">
            <a href="{{ route('home') }}#catalog"
               class="inline-flex min-h-10 items-center gap-1.5 text-zinc-400 transition-colors hover:text-zinc-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">
                <span aria-hidden="true">←</span> К каталогу
            </a>
        </nav>

        {{-- Flex вместо grid-cols-12: при CDN Tailwind без полного набора утилит col-span мог не попасть в CSS, и колонка схлопывалась в 1/12 сетки --}}
        <div class="flex w-full min-w-0 flex-col gap-8 lg:flex-row lg:items-start lg:gap-10 xl:gap-12">
            <div class="w-full min-w-0 flex-1 space-y-7">
                {{-- Product hero --}}
                <header class="w-full min-w-0 rounded-2xl border border-white/[0.08] bg-gradient-to-b from-white/[0.04] to-transparent p-5 sm:p-6 md:p-7">
                    <h1 class="w-full min-w-0 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl md:text-[2.5rem]">{{ $motorcycle->name }}</h1>
                    @if(filled($heroTagline))
                        <p class="mt-3 max-w-3xl text-lg font-medium leading-snug text-zinc-200 sm:text-xl">{{ $heroTagline }}</p>
                    @endif

                    <div class="mt-6 flex w-full min-w-0 flex-col gap-6 lg:flex-row lg:items-start lg:justify-between lg:gap-8">
                        <div class="min-w-0 w-full flex-1 lg:min-w-0">
                            <p class="text-2xl font-bold text-white sm:text-3xl">
                                от <span class="text-moto-amber">{{ number_format($motorcycle->price_per_day, 0, ',', ' ') }} ₽</span>
                                <span class="text-base font-semibold text-zinc-400">/ сутки</span>
                            </p>
                            @if(count($heroChips) > 0)
                                <ul class="mt-4 flex flex-wrap gap-2" aria-label="Кратко о модели">
                                    @foreach($heroChips as $chip)
                                        <li class="rounded-lg border border-white/12 bg-white/[0.05] px-2.5 py-1 text-xs font-semibold text-zinc-200">{{ $chip }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="flex w-full min-w-0 max-w-md flex-col gap-3 lg:max-w-sm lg:shrink-0">
                            <button type="button"
                                    class="tenant-btn-primary min-h-12 w-full gap-2 px-6 touch-manipulation"
                                    @click="$dispatch('open-booking-modal', @js(['id' => $motorcycle->id, 'name' => $motorcycle->name, 'price' => $motorcycle->price_per_day, 'start' => '', 'end' => '']))">
                                Забронировать
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                            <p class="text-sm leading-relaxed text-zinc-300">Сумму за выбранные даты увидите в форме до оплаты — без скрытых строк в калькуляторе.</p>
                            <div class="flex flex-wrap gap-2">
                                @if($telLink)
                                    <a href="{{ $telLink }}"
                                       class="tenant-btn-secondary min-h-11 flex-1 px-4 touch-manipulation sm:flex-none">
                                        Позвонить
                                    </a>
                                @endif
                                @if(filled($contacts['whatsapp'] ?? null))
                                    <a href="https://wa.me/{{ $contacts['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                                       class="tenant-btn-secondary min-h-11 flex-1 px-4 touch-manipulation sm:flex-none">
                                        WhatsApp
                                    </a>
                                @elseif(filled($contactEmail))
                                    <a href="mailto:{{ $contactEmail }}"
                                       class="tenant-btn-secondary min-h-11 flex-1 px-4 touch-manipulation sm:flex-none">
                                        Написать
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Gallery --}}
                @if(count($galleryUrls) > 0)
                    <div class="space-y-3" x-data="{ active: 0, imgs: @js($galleryUrls) }">
                        <div class="relative aspect-[16/10] w-full overflow-hidden rounded-2xl border border-white/10 bg-[#0a0a0c]">
                            <img :src="imgs[active]"
                                 src="{{ $galleryUrls[0] }}"
                                 alt="{{ $motorcycle->name }}"
                                 class="h-full w-full object-cover transition-opacity duration-200"
                                 width="1200"
                                 height="750"
                                 sizes="(max-width:1024px) 100vw, 896px"
                                 fetchpriority="high"
                                 decoding="async">
                        </div>
                        @if(count($galleryUrls) > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:gap-2.5 [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="Миниатюры галереи">
                                <template x-for="(src, idx) in imgs" :key="'t'+idx">
                                    <button type="button"
                                            role="tab"
                                            :aria-selected="active === idx"
                                            class="relative h-16 w-24 shrink-0 overflow-hidden rounded-lg border transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber sm:h-[4.5rem] sm:w-[6.5rem]"
                                            :class="active === idx ? 'border-moto-amber ring-1 ring-moto-amber/40' : 'border-white/10 opacity-80 hover:opacity-100'"
                                            @click="active = idx">
                                        <img :src="src" alt="" class="h-full w-full object-cover" width="104" height="72" loading="lazy" decoding="async">
                                    </button>
                                </template>
                            </div>
                        @endif
                    </div>
                @endif

                @if(filled($detailContent['audience']) || count($detailContent['use_case']) > 0)
                    <section class="rounded-2xl border border-white/[0.08] bg-white/[0.03] p-5 sm:p-6" aria-labelledby="moto-use-heading">
                        <h2 id="moto-use-heading" class="mb-3 text-lg font-bold text-white sm:text-xl">Кому подойдёт и для какого сценария</h2>
                        @if(filled($detailContent['audience']))
                            <p class="mb-3 text-sm leading-relaxed text-zinc-300 sm:text-base">{{ $detailContent['audience'] }}</p>
                        @endif
                        @if(count($detailContent['use_case']) > 0)
                            <ul class="space-y-2 text-sm text-zinc-200 sm:text-base">
                                @foreach($detailContent['use_case'] as $line)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-moto-amber" aria-hidden="true"></span>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endif

                @if(count($detailContent['advantages']) > 0)
                    <section class="rounded-2xl border border-white/[0.08] bg-white/[0.03] p-5 sm:p-6" aria-labelledby="moto-adv-heading">
                        <h2 id="moto-adv-heading" class="mb-3 text-lg font-bold text-white sm:text-xl">Сильные стороны</h2>
                        <ul class="grid gap-2.5 sm:grid-cols-2">
                            @foreach($detailContent['advantages'] as $item)
                                <li class="flex gap-2 rounded-xl border border-white/[0.08] bg-black/25 px-3 py-2.5 text-sm text-zinc-200">
                                    <span class="shrink-0 text-moto-amber" aria-hidden="true">✓</span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(count($specGroups) > 0)
                    <section aria-labelledby="moto-spec-heading">
                        <h2 id="moto-spec-heading" class="mb-3 text-lg font-bold text-white sm:text-xl">Характеристики</h2>
                        <p class="mb-4 max-w-2xl text-sm text-zinc-400">Сводка по технике — чтобы быстрее сравнить с другими моделями в каталоге.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($specGroups as $groupTitle => $rows)
                                <div class="rounded-2xl border border-white/[0.08] bg-white/[0.03] p-4 sm:p-5">
                                    <h3 class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-zinc-400">{{ $groupTitle }}</h3>
                                    <dl class="space-y-2.5">
                                        @foreach($rows as [$label, $value])
                                            <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-3">
                                                <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                                                <dd class="text-sm font-semibold text-zinc-100 sm:text-right">{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(filled($detailContent['rental_notes']))
                    <section class="rounded-2xl border border-white/[0.08] bg-white/[0.03] p-5 sm:p-6" aria-labelledby="moto-rent-heading">
                        <h2 id="moto-rent-heading" class="mb-2 text-lg font-bold text-white sm:text-xl">Аренда этой модели</h2>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-300">{{ $detailContent['rental_notes'] }}</p>
                    </section>
                @endif

                <p class="text-sm text-zinc-400">
                    Общие условия и документы —
                    <a href="{{ route('terms') }}" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">правила аренды</a>.
                </p>

                @if(filled($motorcycle->full_description))
                    <section class="prose prose-invert prose-p:leading-relaxed prose-p:text-zinc-300 max-w-none prose-headings:text-white prose-a:text-moto-amber prose-strong:text-zinc-100" aria-labelledby="moto-desc-heading">
                        <h2 id="moto-desc-heading" class="!mb-3 text-lg font-bold text-white sm:text-xl">Подробнее</h2>
                        <div class="text-sm sm:text-base">
                            {!! $motorcycle->full_description !!}
                        </div>
                    </section>
                @endif
            </div>

            {{-- Боковая колонка: фиксированная ширина на lg+, без sticky (избегаем конфликтов с overflow предков) --}}
            <aside class="hidden w-full min-w-0 shrink-0 lg:block lg:w-96">
                <div class="relative w-full overflow-hidden rounded-2xl border border-moto-amber/25 bg-gradient-to-b from-moto-amber/[0.09] via-carbon to-carbon p-6 shadow-2xl shadow-black/50 ring-1 ring-inset ring-white/10">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-moto-amber/10 blur-3xl"></div>
                    <div class="relative space-y-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-zinc-400">Стоимость</p>
                            <p class="mt-1 text-4xl font-extrabold tracking-tight text-white">
                                {{ number_format($motorcycle->price_per_day, 0, ',', ' ') }}
                                <span class="text-2xl font-bold text-moto-amber">₽</span>
                            </p>
                            <p class="mt-1 text-sm font-medium text-zinc-300">за сутки · итог за период в форме брони</p>
                        </div>
                        <button type="button"
                                class="tenant-btn-primary min-h-12 w-full gap-2 touch-manipulation"
                                @click="$dispatch('open-booking-modal', @js(['id' => $motorcycle->id, 'name' => $motorcycle->name, 'price' => $motorcycle->price_per_day, 'start' => '', 'end' => '']))">
                            Забронировать
                        </button>
                        <a href="{{ route('terms') }}"
                           class="tenant-btn-secondary min-h-11 w-full touch-manipulation">
                            Условия аренды
                        </a>
                        <ul class="space-y-2 border-t border-white/10 pt-4 text-xs leading-snug text-zinc-300">
                            <li class="flex gap-2">
                                <span class="mt-0.5 shrink-0 text-moto-amber" aria-hidden="true">✓</span>
                                <span>Онлайн-заявка и расчёт перед подтверждением</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-0.5 shrink-0 text-moto-amber" aria-hidden="true">✓</span>
                                <span>Можно уточнить даты и детали у команды парка</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-0.5 shrink-0 text-moto-amber" aria-hidden="true">✓</span>
                                <span>Рабочие контакты — в шапке сайта и выше на странице</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>

        @if($relatedMotorcycles->isNotEmpty())
            <section class="mt-12 border-t border-white/10 pt-10 sm:mt-14 sm:pt-12" aria-labelledby="related-heading">
                <h2 id="related-heading" class="mb-6 text-xl font-bold text-white sm:text-2xl">Похожие модели</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @foreach($relatedMotorcycles as $rel)
                        <x-related-moto-card :bike="$rel" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-booking-modal />
@endsection
