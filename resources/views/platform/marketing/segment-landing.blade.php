@extends('platform.layouts.marketing')

@php
    $pm = config('platform_marketing');
    $seg = is_array($pm['segments'][$segmentKey] ?? null) ? $pm['segments'][$segmentKey] : [];
    $pageTitle = (string) ($seg['page_title'] ?? 'RentBase');
    $pmCases = $pm;
    if (! empty($seg['cases']) && is_array($seg['cases'])) {
        $pmCases = array_merge($pm, ['cases' => $seg['cases']]);
    }
    $base = request()->getSchemeAndHttpHost();
    $graph = [
        [
            '@type' => 'Service',
            'name' => (string) ($seg['jsonld_name'] ?? $pageTitle),
            'description' => (string) ($seg['jsonld_description'] ?? ($seg['lead'] ?? '')),
            'provider' => [
                '@type' => 'Organization',
                'name' => $pm['brand_name'] ?? 'RentBase',
                'url' => $base,
            ],
            'areaServed' => 'RU',
        ],
    ];
    $urlLaunch = platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch');
    $urlDemo = platform_marketing_demo_url();
@endphp

@section('title', $pageTitle)

@section('meta_description')
{{ (string) ($seg['meta_description'] ?? $pm['entity_core']) }}
@endsection

@push('jsonld')
    <x-platform.marketing.json-ld :graph="$graph" />
@endpush

@section('content')
<section class="border-b border-slate-200 bg-slate-50 py-12 sm:py-16 md:py-20" aria-labelledby="segment-hero-heading">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 md:px-6">
        <h1 id="segment-hero-heading" class="text-balance text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl md:text-5xl">{{ $seg['h1'] ?? '' }}</h1>
        <p class="mt-4 max-w-3xl text-lg text-slate-600 sm:text-xl">{{ $seg['lead'] ?? '' }}</p>
        <div class="mt-8 flex flex-col gap-4 sm:flex-row">
            <a href="{{ $urlLaunch }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-pm-accent px-8 py-3 text-base font-bold text-white shadow-premium transition-all hover:-translate-y-0.5 hover:bg-pm-accent-hover" data-pm-event="cta_click" data-pm-cta="primary" data-pm-location="segment_{{ $segmentKey }}">
                {{ $pm['cta']['primary'] ?? 'Запустить проект' }}
            </a>
            <a href="{{ $urlDemo }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-8 py-3 text-base font-semibold text-slate-700 transition-colors hover:bg-slate-50" data-pm-event="cta_click" data-pm-cta="secondary" data-pm-location="segment_{{ $segmentKey }}">
                {{ $pm['cta']['secondary'] ?? 'Посмотреть демо' }}
            </a>
        </div>
        <p class="mt-4 text-sm text-slate-500">{{ $pm['hero_cta_subline'] ?? '' }}</p>
    </div>
</section>

@include('platform.marketing.partials.home-benefits', ['pm' => $pm])
@include('platform.marketing.partials.home-cases', ['pm' => $pmCases])
@endsection
