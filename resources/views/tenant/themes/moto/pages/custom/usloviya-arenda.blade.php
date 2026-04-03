@php
    $seoMeta = $seoMeta ?? null;
    
    $sections = $page->sections()
        ->where('status', 'published')
        ->where('is_visible', true)
        ->orderBy('sort_order')
        ->get();
        
    $hasStructuredSections = $sections->count() > 0;
    
    // Prepare navigation items array from sections
    $navItems = [];
    if ($hasStructuredSections) {
        foreach ($sections as $section) {
            $d = $section->data_json ?? [];
            $title = $d['heading'] ?? $section->name ?? 'Раздел ' . $section->id;
            $navItems['section-' . $section->id] = $title;
        }
    }
    
    $title = $page->name ?? 'Условия аренды';
    $subtitle = $page->data_json['subtitle'] ?? 'Прозрачные правила для вашей безопасности и комфорта';
    $legacyContent = $page->content ?? null;
@endphp

@extends('tenant.layouts.app')

@section('content')
<div class="w-full min-w-0 bg-carbon pb-16 sm:pb-24">
    <!-- Hero -->
    <x-custom-pages.terms.hero :title="$title" :subtitle="$subtitle" />

    <!-- Content Area -->
    <div class="mx-auto max-w-5xl px-3 sm:px-4 md:px-8 mt-10 sm:mt-14 relative z-20">
        
        @if($hasStructuredSections)
            <div class="flex flex-col lg:flex-row gap-8 xl:gap-12 items-start relative">
                <!-- Navigation -->
                <x-custom-pages.terms.sticky-sidebar-nav :sections="$navItems" />
                
                <!-- Sections Content -->
                <div class="flex-1 w-full min-w-0 pb-16">
                    @foreach($sections as $section)
                        @php
                            $d = $section->data_json ?? [];
                            $heading = $d['heading'] ?? $section->name ?? null;
                            $content = $d['content'] ?? '';
                            // Optionally extract SVG icon if stored in JSON or mapped by section_key
                            $icon = $d['icon'] ?? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                        @endphp
                        
                        <x-custom-pages.terms.policy-section-card 
                            id="section-{{ $section->id }}"
                            :title="$heading"
                            :icon="$icon">
                            {!! $content !!}
                        </x-custom-pages.terms.policy-section-card>
                    @endforeach
                </div>
            </div>
        @else
            <!-- Fallback for unstructured legacy content -->
            <div class="mx-auto max-w-3xl rounded-2xl border border-white/5 bg-obsidian p-6 sm:p-10 md:p-12 transition-colors hover:bg-obsidian/80">
                @if($legacyContent)
                    <div class="prose prose-invert max-w-none 
                                prose-p:leading-relaxed prose-p:text-silver/90 
                                prose-headings:text-white prose-headings:font-bold
                                prose-a:text-moto-amber prose-a:no-underline hover:prose-a:underline
                                prose-li:marker:text-moto-amber prose-ul:text-silver/90 prose-ol:text-silver/90">
                        {!! $legacyContent !!}
                    </div>
                @else
                    <div class="text-center py-12 text-silver/60">
                        <p>Содержание страницы обновляется...</p>
                    </div>
                @endif
            </div>
        @endif
        
    </div>
</div>

@if($hasStructuredSections)
    @push('tenant-scripts')
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endpush
@endif
@endsection
