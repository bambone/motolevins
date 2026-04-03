@php
    $seoMeta = $seoMeta ?? null;
    
    // Priority: Structured fields -> Tenant Settings -> Fallback
    $dataJson = $page->data_json ?? [];
    
    $title = $dataJson['title'] ?? $page->name ?? 'Контакты';
    
    $tenantId = tenant()->id ?? null;
    
    $phonePrimary = $dataJson['contacts']['phone_primary'] ?? ($contacts['phone'] ?? null);
    $whatsapp = $dataJson['contacts']['whatsapp'] ?? ($contacts['whatsapp'] ?? null);
    $telegram = rtrim(ltrim($dataJson['contacts']['telegram'] ?? ($contacts['telegram'] ?? ''), '@'), '/');
    
    // Address
    $address = $dataJson['contacts']['address'] ?? \App\Models\TenantSetting::getForTenant($tenantId, 'contacts.address', null);
    
    // Working hours
    $workingHours = $dataJson['contacts']['working_hours'] ?? \App\Models\TenantSetting::getForTenant($tenantId, 'contacts.working_hours', null);
    
    // Map
    $mapEmbed = $dataJson['contacts']['map_embed_code'] ?? \App\Models\TenantSetting::getForTenant($tenantId, 'contacts.map_embed', null);
    $mapUrl = $dataJson['contacts']['map_url'] ?? \App\Models\TenantSetting::getForTenant($tenantId, 'contacts.map_url', null);
    
    // Fallback prose content from existing DB content if needed
    $legacyContent = $page->content ?? null;
@endphp

@extends('tenant.layouts.app')

@section('content')
<div class="w-full min-w-0 bg-carbon pb-16 sm:pb-24">
    <!-- Hero -->
    <x-custom-pages.contacts.hero :title="$title" />

    <!-- Content Area -->
    <div class="mx-auto max-w-5xl px-3 sm:px-4 md:px-8 -mt-6 sm:-mt-8 relative z-20">
        
        <x-custom-pages.contacts.methods-grid 
            :phone-primary="$phonePrimary"
            :whatsapp="$whatsapp"
            :telegram="$telegram"
            :address="$address"
        />

        <x-custom-pages.contacts.working-hours 
            :working-hours="$workingHours" 
        />

        <x-custom-pages.contacts.map-block 
            :map-embed-code="$mapEmbed"
            :map-url="$mapUrl"
        />

        @if($legacyContent && empty($address) && empty($phonePrimary) && empty($workingHours))
            <!-- Legacy Content Fallback if everything else is missing -->
            <div class="mt-8 rounded-xl border border-white/5 bg-obsidian/60 p-6 sm:p-8 prose prose-invert max-w-none prose-p:leading-relaxed prose-a:text-moto-amber">
                {!! $legacyContent !!}
            </div>
        @endif
        
    </div>
</div>
@endsection
