@props([
    'sections' => []
])

@if(count($sections) > 0)
    <!-- Desktop Sticky Sidebar -->
    <div class="hidden lg:block w-72 shrink-0">
        <div class="sticky top-28 flex flex-col gap-1">
            <h3 class="text-xs font-bold uppercase tracking-widest text-silver/50 mb-3 px-3">Содержание</h3>
            <nav class="flex flex-col border-l border-white/5" aria-label="Навигация по условиям">
                @foreach($sections as $id => $label)
                    <a href="#{{ $id }}" 
                       @click.prevent="document.getElementById('{{ $id }}').scrollIntoView({behavior: 'smooth'})"
                       class="relative flex items-center py-2.5 pl-4 pr-3 text-sm text-silver/80 transition-colors hover:text-white hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-moto-amber/50 rounded-r-lg group">
                        <!-- Active indicator (simulated with hover for now, can be bound to scroll spy via Alpine later if needed) -->
                        <span class="absolute left-0 top-0 bottom-0 w-0.5 bg-moto-amber scale-y-0 opacity-0 transition-all group-focus:scale-y-100 group-focus:opacity-100 group-hover:opacity-100 group-hover:scale-y-100"></span>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- Mobile Jump/Accordion Nav (Compact) -->
    <div class="lg:hidden w-full mb-8 relative z-20" x-data="{ open: false }">
        <button @click="open = !open" 
                type="button" 
                class="flex w-full items-center justify-between rounded-xl border border-white/10 bg-obsidian/80 px-4 py-3.5 text-sm font-medium text-white shadow-sm ring-1 ring-inset ring-white/5 focus:outline-none focus:ring-2 focus:ring-moto-amber" 
                aria-haspopup="true" 
                :aria-expanded="open.toString()">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-moto-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                Содержание разделов
            </span>
            <svg class="w-4 h-4 text-silver transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
        
        <div x-show="open" 
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="absolute left-0 right-0 top-full mt-2 rounded-xl border border-white/10 bg-obsidian shadow-xl shadow-black/40 overflow-hidden ring-1 ring-white/5 origin-top z-50"
             style="display: none;">
            <div class="py-1">
                @foreach($sections as $id => $label)
                    <a href="#{{ $id }}" 
                       @click="open = false; setTimeout(() => document.getElementById('{{ $id }}').scrollIntoView({behavior: 'smooth'}), 150)"
                       class="block px-4 py-3 text-sm text-silver transition-colors hover:bg-white/5 hover:text-white focus:bg-white/5 outline-none">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
