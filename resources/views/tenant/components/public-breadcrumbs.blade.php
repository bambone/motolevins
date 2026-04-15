@php
    $crumbs = [];
    if (isset($resolvedSeo) && $resolvedSeo instanceof \App\Services\Seo\SeoResolvedData && $resolvedSeo->breadcrumbs !== []) {
        $crumbs = $resolvedSeo->breadcrumbs;
    }
@endphp
@if ($crumbs !== [])
    <nav class="rb-public-breadcrumbs mx-auto w-full max-w-6xl px-3 pb-2 pt-6 sm:px-4 sm:pt-8 md:px-8 advocate-editorial-theme:text-stone-700" aria-label="Хлебные крошки">
        <ol class="flex flex-wrap items-center gap-x-1 gap-y-1 text-xs sm:text-sm">
            @foreach ($crumbs as $idx => $c)
                @php
                    $isLast = $idx === count($crumbs) - 1;
                    $name = trim((string) ($c['name'] ?? ''));
                    $url = trim((string) ($c['url'] ?? ''));
                @endphp
                @if ($name !== '' && $url !== '')
                    <li class="flex min-w-0 items-center gap-1">
                        @if ($idx > 0)
                            <span class="text-silver/50 advocate-editorial-theme:text-stone-400" aria-hidden="true">/</span>
                        @endif
                        @if ($isLast)
                            <span class="truncate font-medium text-white/90 advocate-editorial-theme:text-stone-900" aria-current="page">{{ $name }}</span>
                        @else
                            <a href="{{ $url }}" class="truncate text-silver/85 underline-offset-2 transition hover:text-white hover:underline advocate-editorial-theme:text-stone-600 advocate-editorial-theme:hover:text-stone-900" hreflang="{{ \App\Services\Seo\TenantPublicHtmlLang::attribute(tenant()) }}">
                                {{ $name }}
                            </a>
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
