@props([
    'id' => '',
    'title' => '',
    'icon' => null,
])

<div id="{{ $id }}" class="scroll-mt-28 md:scroll-mt-32 mb-8 md:mb-12 group last:mb-0">
    <div class="flex items-center gap-3 sm:gap-4 mb-5 sm:mb-6">
        @if($icon)
            <div class="shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-moto-amber/10 flex items-center justify-center text-moto-amber ring-1 ring-moto-amber/20 group-hover:bg-moto-amber/20 transition-colors duration-300">
                {!! $icon !!}
            </div>
        @endif
        <h2 class="text-xl sm:text-2xl font-bold text-white">{{ $title }}</h2>
    </div>
    
    <div class="rounded-2xl border border-white/5 bg-obsidian/60 p-5 sm:p-7 md:p-8 transition-colors hover:bg-obsidian">
        <!-- Robust prose styles for readability -->
        <div class="prose prose-invert prose-sm sm:prose-base max-w-none 
                    prose-p:leading-relaxed prose-p:text-silver/90 
                    prose-ul:text-silver/90 prose-ol:text-silver/90
                    prose-li:marker:text-moto-amber 
                    prose-strong:text-white 
                    prose-a:text-moto-amber prose-a:no-underline hover:prose-a:underline
                    prose-headings:text-white prose-headings:font-bold">
            {{ $slot }}
        </div>
    </div>
</div>
