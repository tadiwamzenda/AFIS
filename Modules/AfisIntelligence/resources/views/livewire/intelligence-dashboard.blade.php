<div class="space-y-6">

    @if($message)
        <div class="rounded-lg bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-700">
            {{ $message }}
        </div>
    @endif

    {{-- Fleet metrics --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Vehicles</p>
            <p class="text-3xl font-bold text-gray-900">{{ $fleetMetrics['total_vehicles'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $fleetMetrics['active_vehicles'] }} active</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Trips (30 days)</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($fleetMetrics['total_trips_30d']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($fleetMetrics['total_km_30d']) }} km</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Max speed (30 days)</p>
            <p class="text-3xl font-bold text-gray-900">{{ $fleetMetrics['max_speed_30d'] }} <span class="text-lg text-gray-400">km/h</span></p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($fleetMetrics['total_events_30d']) }} events</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Distance (90 days)</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($fleetMetrics['total_km_90d']) }} <span class="text-lg text-gray-400">km</span></p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($fleetMetrics['total_trips_90d']) }} trips</p>
        </div>
    </div>

    {{-- AI report generation --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Generate intelligence reports</h3>
        <div class="flex gap-3 flex-wrap">
            <button wire:click="generateFleetIntelligence" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Fleet Intelligence (30 days)
            </button>
            <button wire:click="generatePredictive" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Predictive Intelligence (90 days)
            </button>
            <a href="{{ route('admin.afis.intelligence.archive', $client->id) }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                View all reports →
            </a>
        </div>
    </div>

    {{-- Vehicle risk ranking --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Vehicle risk ranking — last 30 days</h3>
            <p class="text-xs text-gray-400 mt-0.5">Based on speed, events, and trip frequency. Higher = more attention needed.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($vehicleRiskData as $vehicle)
            <div class="px-5 py-3 flex items-center gap-4">
                <div class="w-2 h-2 rounded-full flex-shrink-0
                    {{ $vehicle['risk_level'] === 'high' ? 'bg-red-500' :
                       ($vehicle['risk_level'] === 'medium' ? 'bg-yellow-500' : 'bg-green-500') }}">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $vehicle['label'] }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $vehicle['trip_count'] }} trips ·
                        {{ $vehicle['total_km'] }} km ·
                        Max {{ $vehicle['max_speed'] }} km/h ·
                        {{ $vehicle['event_count'] }} events
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-lg font-bold {{ $vehicle['risk_level'] === 'high' ? 'text-red-600' : ($vehicle['risk_level'] === 'medium' ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $vehicle['risk_score'] }}/10
                        </p>
                        <p class="text-xs text-gray-400">risk score</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $vehicle['risk_level'] === 'high' ? 'bg-red-100 text-red-700' :
                           ($vehicle['risk_level'] === 'medium' ? 'bg-yellow-100 text-yellow-700' :
                                                                  'bg-green-100 text-green-700') }}">
                        {{ ucfirst($vehicle['risk_level']) }}
                    </span>
                    <a href="{{ route('admin.afis.vehicle', $vehicle['id']) }}"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        Inspect
                    </a>
                </div>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">No vehicle data available. Run a pipeline sync first.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent intelligence reports --}}
    @if($recentReports->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Recent intelligence reports</h3>
            <a href="{{ route('admin.afis.intelligence.archive', $client->id) }}"
                class="text-xs text-brand-600 hover:text-brand-700 font-medium">View all →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($recentReports as $report)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</p>
                    <p class="text-xs text-gray-400">{{ $report->engine_used }} · {{ $report->tokens_used }} tokens · {{ $report->created_at->diffForHumans() }}</p>
                </div>
                <a href="{{ route('admin.afis.intelligence.archive', $client->id) }}"
                    class="text-xs text-brand-600 hover:text-brand-700 font-medium">Read →</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>