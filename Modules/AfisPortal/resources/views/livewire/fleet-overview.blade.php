<div class="space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['Clients',   $totals['clients']],
            ['Trackers',  $totals['trackers']],
            ['Trips',     $totals['trips']],
            ['Events',    $totals['events']],
            ['AI Reports',$totals['reports']],
        ] as [$label, $count])
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $label }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($count) }}</p>
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