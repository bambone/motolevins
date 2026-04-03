@php
    $title = $data['title'] ?? '';
    $variant = $data['variant'] ?? 'bullets';
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
@endphp
<section class="w-full min-w-0" data-page-section-type="{{ $section->section_type }}">
    @if(filled($title))
        <h2 class="mb-4 text-balance text-xl font-semibold text-white sm:text-2xl">{{ $title }}</h2>
    @endif
    @if($items !== [])
        @if($variant === 'numbered')
            <ol class="list-decimal space-y-4 pl-5 text-sm text-silver sm:text-base">
                @foreach($items as $item)
                    @php
                        $it = is_array($item) ? $item : [];
                        $st = $it['title'] ?? '';
                        $tx = $it['text'] ?? '';
                    @endphp
                    <li class="pl-1">
                        @if(filled($st))
                            <p class="font-medium text-white">{{ $st }}</p>
                        @endif
                        @if(filled($tx))
                            <p class="mt-1 leading-relaxed">{{ $tx }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @elseif($variant === 'steps')
            <ol class="relative space-y-6 border-l border-white/15 pl-6">
                @foreach($items as $idx => $item)
                    @php
                        $it = is_array($item) ? $item : [];
                        $st = $it['title'] ?? '';
                        $tx = $it['text'] ?? '';
                    @endphp
                    <li class="relative">
                        <span class="absolute -left-[1.85rem] flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-white" aria-hidden="true">{{ $idx + 1 }}</span>
                        @if(filled($st))
                            <p class="font-medium text-white">{{ $st }}</p>
                        @endif
                        @if(filled($tx))
                            <p class="mt-1 text-sm leading-relaxed text-silver sm:text-base">{{ $tx }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <ul class="list-disc space-y-4 pl-5 text-sm text-silver marker:text-white/50 sm:text-base">
                @foreach($items as $item)
                    @php
                        $it = is_array($item) ? $item : [];
                        $st = $it['title'] ?? '';
                        $tx = $it['text'] ?? '';
                    @endphp
                    <li class="pl-1">
                        @if(filled($st))
                            <p class="font-medium text-white">{{ $st }}</p>
                        @endif
                        @if(filled($tx))
                            <p class="mt-1 leading-relaxed">{{ $tx }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</section>
