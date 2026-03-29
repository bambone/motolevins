@extends('platform.layouts.marketing')

@section('title', 'Главная')

@section('meta_description')
{{ config('platform_marketing.entity_core') }}
@endsection

@php
    $pm = config('platform_marketing');
    $base = request()->getSchemeAndHttpHost();
    $org = $pm['organization'] ?? [];
    $graph = [
        [
            '@type' => 'Organization',
            'name' => $org['name'] ?? 'RentBase',
            'url' => $base,
            'description' => $org['description'] ?? '',
        ],
        [
            '@type' => 'WebSite',
            'name' => ($pm['brand_name'] ?? 'RentBase').' — официальный сайт',
            'url' => $base,
        ],
        [
            '@type' => 'SoftwareApplication',
            'name' => $pm['brand_name'] ?? 'RentBase',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => $pm['entity_core'] ?? '',
        ],
    ];
@endphp

@push('jsonld')
    <x-platform.marketing.json-ld :graph="$graph" />
@endpush

@section('content')
    @include('platform.marketing.partials.home-hero', ['pm' => $pm])
    @include('platform.marketing.partials.home-trust', ['pm' => $pm])
    @include('platform.marketing.partials.home-audience', ['pm' => $pm])
    @include('platform.marketing.partials.home-benefits', ['pm' => $pm])
    @include('platform.marketing.partials.home-control', ['pm' => $pm])
    @include('platform.marketing.partials.home-how', ['pm' => $pm])
    @include('platform.marketing.partials.home-cases', ['pm' => $pm])
    @include('platform.marketing.partials.home-kpi', ['pm' => $pm])
    @include('platform.marketing.partials.home-model', ['pm' => $pm])
    @include('platform.marketing.partials.home-pricing', ['pm' => $pm])
    @include('platform.marketing.partials.home-quality', ['pm' => $pm])
    @include('platform.marketing.partials.home-compare', ['pm' => $pm])
    @include('platform.marketing.partials.home-final-cta', ['pm' => $pm])
@endsection
