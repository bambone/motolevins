@php
    use App\Services\PageBuilder\SectionViewResolver;

    $seoMeta = $seoMeta ?? null;
    $sectionResolver = app(SectionViewResolver::class);
    $sections = $page->sections()
        ->where('status', 'published')
        ->where('is_visible', true)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();
    $mainSection = $sections->firstWhere('section_key', 'main');
    $extraSections = $sections->filter(fn ($s) => $s->section_key !== 'main');
@endphp
@extends('tenant.layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl px-3 pb-12 pt-24 sm:px-4 sm:pb-16 sm:pt-28 md:px-8">
        <h1 class="mb-6 text-balance text-2xl font-bold leading-tight text-white sm:mb-8 sm:text-3xl md:text-4xl">{{ $page->name }}</h1>

        @if($mainSection && is_array($mainSection->data_json) && filled($mainSection->data_json['content'] ?? null))
            <div class="prose prose-invert mb-12 max-w-none text-sm text-silver prose-headings:text-white prose-p:leading-relaxed sm:text-base">
                {!! $mainSection->data_json['content'] !!}
            </div>
        @endif

        <div class="flex flex-col gap-12">
            @foreach($extraSections as $section)
                @php
                    $data = is_array($section->data_json) ? $section->data_json : [];
                    $viewName = $sectionResolver->resolveViewName($section);
                @endphp
                @if($viewName !== null)
                    @include($viewName, ['section' => $section, 'data' => $data])
                @else
                    <div class="prose prose-invert max-w-none text-sm text-silver prose-headings:text-white prose-p:leading-relaxed sm:text-base">
                        @if($data !== [])
                            @if(! empty($data['content']))
                                {!! $data['content'] !!}
                            @elseif(! empty($data['heading']))
                                <h2>{{ $data['heading'] }}</h2>
                            @endif
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection
