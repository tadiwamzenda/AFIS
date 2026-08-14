<div class="space-y-6">

    @if($message)
        <div class="rounded-lg bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-700">
            {{ $message }}
        </div>
    @endif

    {{-- Vehicle header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $tracker->label }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $tracker->client?->name }}
                    @if($tracker->vehicle_registration) · {{ $tracker->vehicle_registration }} @endif
                    @if($tracker->model_name) · {{ $tracker->model_name }} @endif
                </p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full {{ $tracker->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $tracker->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        {{ $tracker->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($tracker->last_active_at)
                        <span class="text-xs text-gray-400">Last seen {{ $tracker->last_active_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="days" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="60">Last 60 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <button wire:click="generateBehaviourReport" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Generate AI Report
                </button>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        @foreach([
            ['Trips',        $stats['total_trips'],        'bg-indigo-500/15', 'text-indigo-400', 'M13 7l5 5m0 0l-5 5m5-5H6'],
            ['Distance',     $stats['total_distance_km'] . ' km', 'bg-sky-500/15', 'text-sky-400', 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['Avg speed',    $stats['avg_speed_kmh'] . ' km/h',   'bg-emerald-500/15', 'text-emerald-400', 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['Max speed',    $stats['max_speed_kmh'] . ' km/h',   'bg-rose-500/15', 'text-rose-400', 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['Hours driven', $stats['total_hours'] . ' h',        'bg-amber-500/15', 'text-amber-400', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Events',       $stats['total_events'],              'bg-purple-500/15', 'text-purple-400', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        ] as [$label, $value, $chipBg, $chipText, $icon])
        <div class="relative rounded-2xl p-4 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-6 h-6 rounded-lg {{ $chipBg }} flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 {{ $chipText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" /></svg>
                </span>
                <p class="text-xs text-slate-400">{{ $label }}</p>
            </div>
            <p class="text-lg font-bold text-white">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-gray-200">
        @foreach(['overview' => 'Overview', 'trips' => 'Trips', 'events' => 'Events', 'reports' => 'AI Reports'] as $tab => $label)
        <button wire:click="setTab('{{ $tab }}')"
            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === $tab ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
            @if($tab === 'trips') <span class="ml-1 text-xs text-gray-400">({{ count($trips) }})</span> @endif
            @if($tab === 'events') <span class="ml-1 text-xs text-gray-400">({{ count($events) }})</span> @endif
            @if($tab === 'reports') <span class="ml-1 text-xs text-gray-400">({{ count($reports) }})</span> @endif
        </button>
        @endforeach
    </div>

    {{-- Tab content --}}
    @if($activeTab === 'overview')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Event breakdown --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Event breakdown</h3>
            @forelse($stats['event_types'] as $type => $count)
            <div class="flex items-center justify-between py-1.5">
                <span class="text-sm text-gray-600">{{ $type }}</span>
                <span class="text-sm font-medium text-gray-800">{{ $count }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">No events recorded in this period.</p>
            @endforelse
        </div>
        {{-- Latest AI report — summary only, no content preview --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Latest AI report</h3>
            @if($reports->isNotEmpty())
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                            {{ ucwords(str_replace('_', ' ', $reports->first()->report_type)) }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1.5">{{ $reports->first()->created_at->diffForHumans() }}</p>
                    </div>
                    @if($reports->first()->report_path)
                    <a href="{{ route('admin.afis.vehicle-reports.download', $reports->first()->id) }}"
                        class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    @endif
                </div>
                <button wire:click="setTab('reports')" class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-medium">View all reports →</button>
            @else
                <p class="text-sm text-gray-400">No AI reports generated yet. Click "Generate AI Report" above.</p>
            @endif
        </div>
    </div>
    @endif

    @if($activeTab === 'trips')
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Start time</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Duration</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Distance</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Avg speed</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Max speed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($trips as $trip)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $trip->start_time->format('d M Y H:i') }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $trip->duration_minutes }} min</td>
                    <td class="px-4 py-2 text-gray-600">{{ $trip->distance_km }} km</td>
                    <td class="px-4 py-2 text-gray-600">{{ $trip->avg_speed_kmh }} km/h</td>
                    <td class="px-4 py-2 {{ $trip->max_speed_kmh > 120 ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                        {{ $trip->max_speed_kmh }} km/h
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No trips recorded in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($activeTab === 'events')
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Time</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Event type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Location</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($events as $event)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $event->occurred_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-2"><span class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ $event->event_type }}</span></td>
                    <td class="px-4 py-2 text-xs text-gray-400">
                        {{ $event->lat && $event->lng ? $event->lat . ', ' . $event->lng : '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No events recorded in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($activeTab === 'reports')
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Generated reports</h3>
            <span class="text-xs text-gray-400">{{ $reports->count() }} reports</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($reports as $report)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        {{ ucwords(str_replace('_', ' ', $report->report_type)) }}
                    </span>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $report->engine_used }} · {{ $report->tokens_used }} tokens · {{ $report->created_at->format('d M Y H:i') }} · {{ $report->created_at->diffForHumans() }}
                    </p>
                </div>
                @if($report->report_path)
                <a href="{{ route('admin.afis.vehicle-reports.download', $report->id) }}"
                    class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download
                </a>
                @endif
            </div>
            @empty
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">No AI reports generated yet.</p>
                <button wire:click="generateBehaviourReport" class="mt-3 text-sm text-brand-600 hover:text-brand-700 font-medium">Generate vehicle behaviour report →</button>
            </div>
            @endforelse
        </div>
    </div>
    @endif

</div>