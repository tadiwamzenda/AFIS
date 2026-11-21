@php
    // $links = [[route name, label, active-route pattern], ...]
    $isOpen = collect($links)->contains(fn ($l) => request()->routeIs($l[2]));
@endphp
<div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors border-l-2 {{ $isOpen ? 'text-white border-brand-500' : 'text-gray-300 hover:bg-gray-800 hover:text-white border-transparent' }}">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
        <span class="flex-1 text-left">{{ $title }}</span>
        <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <div x-show="open" @unless($isOpen) style="display:none" @endunless
         class="ml-6 mt-1 mb-1 space-y-0.5 border-l border-gray-700 pl-2">
        @foreach($links as [$route, $label, $pattern])
            @php $active = request()->routeIs($pattern); @endphp
            <a href="{{ route($route) }}"
               class="block px-3 py-1.5 rounded-lg text-sm transition-colors {{ $active ? 'bg-brand-600/20 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>