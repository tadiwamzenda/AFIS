<div class="space-y-6">

    @if($message)
        <div class="rounded-lg bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-700">
            {{ $message }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 118 0v2M5 17h14a2 2 0 002-2v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2a2 2 0 002 2z" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Vehicles</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['total_vehicles'] }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $stats['active_vehicles'] }} active</p>
        </div>
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-sky-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Trips (30 days)</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['total_trips']) }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($stats['total_km']) }} km</p>
        </div>
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-rose-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Max speed (30 days)</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['max_speed'] ?? 0 }} <span class="text-lg text-slate-400">km/h</span></p>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($stats['total_events']) }} events</p>
        </div>
    </div>

    {{-- Vehicle list --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <h3 class="text-sm font-semibold text-gray-800 flex-1">Vehicles</h3>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search vehicle..."
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400 w-44">
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($trackers as $tracker)
            @php
                $riskDotColor   = match($tracker->risk_level) { 'high' => 'bg-red-500', 'medium' => 'bg-amber-400', default => 'bg-green-400' };
                $riskBadgeClass = match($tracker->risk_level) { 'high' => 'bg-red-100 text-red-700', 'medium' => 'bg-amber-50 border border-amber-200 text-amber-700', default => 'bg-green-100 text-green-700' };
                $riskScoreText  = match($tracker->risk_level) { 'high' => 'text-red-600', 'medium' => 'text-amber-600', default => 'text-green-600' };
            @endphp
            <div class="px-5 py-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $tracker->label }}</p>
                    @if($tracker->last_report)
                        <span class="text-xs text-green-600 font-medium">Report available</span>
                    @endif
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
                <div class="flex items-center gap-3">
                    
                    
                    <div class="text-right">
                        <p class="text-sm font-bold {{ $riskScoreText }}">{{ $tracker->risk_score }}/10</p>
                        <p class="text-[10px] text-gray-400">risk score</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $riskBadgeClass }}">
                        {{ ucfirst($tracker->risk_level) }}
                    </span>
                    
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