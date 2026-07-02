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
                    <a href="{{ auth()->user()->isBtStaff() ? route('admin.afis.vehicle', $tracker->id) : route('client.vehicle', $tracker->id) }}"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        Inspect
                    </a>
                </div>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">
                No vehicles found yet. Your fleet is being discovered automatically.
                If this persists after refreshing, contact Bantu Track support.
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