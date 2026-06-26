<div class="space-y-6">

    @if($message)
        <div class="rounded-lg bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-700">
            {{ $message }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Vehicles</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_vehicles'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['active_vehicles'] }} active</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Trips (30 days)</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_trips']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($stats['total_km']) }} km</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Max speed (30 days)</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['max_speed'] ?? 0 }} <span class="text-lg text-gray-400">km/h</span></p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($stats['total_events']) }} events</p>
        </div>
    </div>

    {{-- AI Report actions --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Generate AI intelligence reports</h3>
        <div class="flex gap-3 flex-wrap">
            <button wire:click="generateFleetReport" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Fleet Intelligence Report
            </button>
            <button wire:click="generatePredictiveReport" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Predictive Intelligence
            </button>
            <a href="{{ route('admin.afis.reports', $client->id) }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                View all reports →
            </a>
            {{-- Incidents button --}}
            <a href="{{ request()->routeIs('admin.*') ? route('admin.afis.incidents.log', $client->id) : route('client.incidents.log') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Log Incident
            </a>
        </div>
    </div>

    {{-- Vehicle list --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Vehicles</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($trackers as $tracker)
            <div class="px-5 py-4 flex items-center gap-4">
                <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $tracker->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $tracker->label }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $tracker->trip_count }} trips ·
                        {{ $tracker->total_km }} km ·
                        Max {{ $tracker->max_speed ?? 0 }} km/h ·
                        {{ $tracker->event_count }} events
                        @if($tracker->last_active_at)
                            · Last seen {{ $tracker->last_active_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($tracker->last_report)
                        <span class="text-xs text-green-600 font-medium">Report available</span>
                    @endif
                    <a href="{{ route('admin.afis.vehicle', $tracker->id) }}"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        Inspect
                    </a>
                </div>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">
                No trackers found for this client. Make sure GPS devices have Navixy tracker IDs set and run a pipeline sync.
            </p>
            @endforelse
        </div>
    </div>

    {{-- Recent reports --}}
    @if($recentReports->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Recent AI reports</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($recentReports as $report)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</p>
                    <p class="text-xs text-gray-400">{{ $report->engine_used }} · {{ $report->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs text-green-600 font-medium">Completed</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>