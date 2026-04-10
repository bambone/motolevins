@extends('tenant.layouts.app')

@section('content')
    <div class="mx-auto w-full min-w-0 max-w-3xl px-4 pb-16 pt-24 sm:px-6 sm:pt-28 lg:px-8">
        <article class="prose prose-invert max-w-none">
            <h1 class="text-3xl font-bold text-white sm:text-4xl">{{ ($resolvedSeo ?? null)?->h1 ?? $page->h1 ?? $page->title }}</h1>
            @if(filled($page->intro))
                <p class="mt-4 text-silver">{{ $page->intro }}</p>
            @endif
            @if(filled($page->body))
                <div class="mt-6 text-silver">{!! $page->body !!}</div>
            @endif
        </article>
        <p class="mt-10">
            <a href="{{ route('motorcycles.index') }}" class="text-moto-amber underline-offset-2 hover:underline">К каталогу</a>
        </p>
    </div>
@endsection
