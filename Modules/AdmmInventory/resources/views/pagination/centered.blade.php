{{-- Prev  [page number]  Next, centred. With a single page only the number shows. --}}
@php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $name    = $paginator->getPageName();
@endphp
<div class="flex items-center justify-center gap-4 py-3 text-sm select-none">
    @if($last > 1)
        @if($paginator->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-300 cursor-not-allowed">‹ Prev</span>
        @else
            <button type="button" wire:click="previousPage('{{ $name }}')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">‹ Prev</button>
        @endif
    @endif

    <span class="min-w-8 text-center font-semibold text-gray-800" title="Page {{ $current }} of {{ $last }}">{{ $current }}</span>

    @if($last > 1)
        @if($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $name }}')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Next ›</button>
        @else
            <span class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-300 cursor-not-allowed">Next ›</span>
        @endif
    @endif
</div>
