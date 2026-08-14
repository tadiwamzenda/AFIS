<div class="space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['Clients',    $totals['clients'],  'bg-indigo-500/15', 'text-indigo-400', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0'],
            ['Trackers',   $totals['trackers'], 'bg-emerald-500/15','text-emerald-400','M9 17v-2a4 4 0 118 0v2M5 17h14a2 2 0 002-2v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2a2 2 0 002 2z'],
            ['Trips',      $totals['trips'],    'bg-sky-500/15',    'text-sky-400',    'M13 7l5 5m0 0l-5 5m5-5H6'],
            ['Events',     $totals['events'],   'bg-amber-500/15',  'text-amber-400',  'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ['AI Reports', $totals['reports'],  'bg-purple-500/15', 'text-purple-400', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ] as [$label, $count, $chipBg, $chipText, $icon])
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg {{ $chipBg }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $chipText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">{{ $label }}</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($count) }}</p>
        </div>
        @endforeach
    </div>

    {{-- Client fleet cards --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <h3 class="text-sm font-semibold text-gray-800 flex-1">All client fleets</h3>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search clients..."
                class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-48">
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($clients as $client)
            <div class="px-5 py-4 flex items-center gap-4">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">{{ $client->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $client->tracker_count }} trackers ·
                        {{ $client->active_trackers }} active
                        @if($client->last_report)
                            · Last report: {{ $client->last_report->created_at->diffForHumans() }}
                        @else
                            · No reports yet
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.afis.client-fleet', $client->id) }}"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        View fleet
                    </a>
                    <a href="{{ route('admin.afis.reports', $client->id) }}"
                        class="text-xs text-gray-500 hover:text-gray-700 font-medium px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Reports
                    </a>
                </div>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">No active clients found.</p>
            @endforelse
        </div>
    </div>

</div>