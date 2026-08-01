<div class="space-y-6">

    {{-- ── Summary cards (floating glossy tiles) ──────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Active clients --}}
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1m-5 7v-4h2v4" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Active clients</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ $totalClients }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $totalTrackers }} trackers total</p>
            @if(!is_null($clientsDelta))
                <p class="mt-1 text-xs {{ $clientsDelta >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $clientsDelta >= 0 ? '↑' : '↓' }} {{ number_format(abs($clientsDelta), 1) }}%
                </p>
            @endif
        </div>

        {{-- GPS Devices --}}
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 118 0v2M5 17h14a2 2 0 002-2v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2a2 2 0 002 2z" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">GPS Devices</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ $totalDevices }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-emerald-400">{{ $devicesWithClient }} with client</span>
                <span class="text-xs text-sky-400">{{ $devicesInStock }} in stock</span>
                @if($devicesLost > 0)
                <span class="text-xs text-rose-400">{{ $devicesLost }} lost</span>
                @endif
            </div>
            @if(!is_null($devicesDelta))
                <p class="mt-1 text-xs {{ $devicesDelta >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $devicesDelta >= 0 ? '↑' : '↓' }} {{ number_format(abs($devicesDelta), 1) }}%
                </p>
            @endif
        </div>

        {{-- SIM Cards --}}
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-sky-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="6" y="3" width="12" height="18" rx="2" stroke-width="2" /><path stroke-linecap="round" stroke-width="2" d="M9 7h6M9 11h6M9 15h3" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">SIM Cards</p>
            </div>
            <p class="text-3xl font-bold text-white">{{ $totalSims }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-emerald-400">{{ $simsWithClient }} with client</span>
                <span class="text-xs text-sky-400">{{ $simsInStock }} in stock</span>
                @if($simsLost > 0)
                <span class="text-xs text-rose-400">{{ $simsLost }} lost</span>
                @endif
            </div>
            @if(!is_null($simsDelta))
                <p class="mt-1 text-xs {{ $simsDelta >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $simsDelta >= 0 ? '↑' : '↓' }} {{ number_format(abs($simsDelta), 1) }}%
                </p>
            @endif
        </div>

        {{-- Lost assets --}}
        <div class="relative rounded-2xl p-5 bg-gradient-to-br from-slate-900/95 to-slate-800/90 border border-white/5 shadow-xl shadow-slate-900/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-7 h-7 rounded-lg bg-rose-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                </span>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Lost Assets</p>
            </div>
            <p class="text-3xl font-bold {{ ($devicesLost + $simsLost) > 0 ? 'text-rose-400' : 'text-white' }}">
                {{ $devicesLost + $simsLost }}
            </p>
            <div class="mt-2 flex gap-2 flex-wrap">
                @if($devicesLost > 0)
                <span class="text-xs text-rose-400">{{ $devicesLost }} devices</span>
                @endif
                @if($simsLost > 0)
                <span class="text-xs text-rose-400">{{ $simsLost }} SIMs</span>
                @endif
                @if(($devicesLost + $simsLost) === 0)
                <span class="text-xs text-emerald-400">No lost assets ✓</span>
                @endif
            </div>
            @if($lostDelta !== 0)
                <p class="mt-1 text-xs {{ $lostDelta <= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $lostDelta > 0 ? '↑' : '↓' }} {{ abs($lostDelta) }} today
                </p>
            @endif
        </div>

    </div>

    {{-- ── Trend charts ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" wire:ignore>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                <h3 class="text-sm font-semibold text-gray-800">Device Count · 14-Day Trend</h3>
            </div>
            <canvas id="deviceTrendChart" height="140"
                data-points='@json($deviceTrend)'></canvas>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <h3 class="text-sm font-semibold text-gray-800">Daily Offline Vehicles</h3>
            </div>
            <canvas id="offlineTrendChart" height="140"
                data-points='@json($offlineTrend)'></canvas>
        </div>
    </div>

    {{-- ── Asset breakdown + Recent installations ──────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Asset breakdown by client --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Asset distribution</h3>
                <a href="{{ route('admin.admm.asset-stock.index') }}"
                    class="text-xs text-brand-600 hover:text-brand-700 font-medium">View full report →</a>
            </div>
            <div class="p-5 space-y-3">
                <div>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Devices with client</span>
                        <span class="font-medium text-gray-700">{{ $totalDevices > 0 ? round(($devicesWithClient / $totalDevices) * 100) : 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-brand-500 h-2 rounded-full"
                            style="width: {{ $totalDevices > 0 ? round(($devicesWithClient / $totalDevices) * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-0.5">
                        <span>{{ $devicesWithClient }} deployed</span>
                        <span>{{ $devicesInStock }} in stock</span>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>SIM cards with client</span>
                        <span class="font-medium text-gray-700">{{ $totalSims > 0 ? round(($simsWithClient / $totalSims) * 100) : 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full"
                            style="width: {{ $totalSims > 0 ? round(($simsWithClient / $totalSims) * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-0.5">
                        <span>{{ $simsWithClient }} deployed</span>
                        <span>{{ $simsInStock }} in stock</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent installations --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Recent installations</h3>
                <a href="{{ route('admin.admm.asset-register.index') }}"
                    class="text-xs text-brand-600 hover:text-brand-700 font-medium">View register →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentInstallations as $record)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $record->vehicle_reg_no ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $record->client ?? '—' }} · {{ $record->gps_device_name ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ $record->installation_date ? \Carbon\Carbon::parse($record->installation_date)->format('d M Y') : '—' }}
                    </span>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">No recent installations.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Recent activity ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Recent activity</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentActivity as $log)
                <div class="px-5 py-3 flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-brand-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-xs font-semibold text-brand-600">
                            {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm text-gray-700">
                                <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                                <span class="text-gray-400 mx-1">·</span>
                                <span class="font-mono text-xs text-gray-500">{{ $log->event }}</span>
                            </p>
                            <span class="text-xs text-gray-400 flex-shrink-0">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $log->module }}</p>
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">No activity yet.</p>
            @endforelse
        </div>
    </div>

    @script
<script>
    function loadChartJs(cb) {
        if (window.Chart) return cb();
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function renderCharts() {
        var deviceEl = document.getElementById('deviceTrendChart');
        var offlineEl = document.getElementById('offlineTrendChart');
        if (!deviceEl || !offlineEl) return;

        // destroy any existing chart instance on this canvas before redrawing
        // (prevents "Canvas is already in use" errors on wire:navigate re-mounts)
        var existingDevice = Chart.getChart(deviceEl);
        if (existingDevice) existingDevice.destroy();
        var existingOffline = Chart.getChart(offlineEl);
        if (existingOffline) existingOffline.destroy();

        var devicePoints = JSON.parse(deviceEl.dataset.points || '[]');
        var offlinePoints = JSON.parse(offlineEl.dataset.points || '[]');

        new Chart(deviceEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: devicePoints.map(p => p.label),
                datasets: [{
                    data: devicePoints.map(p => p.value),
                    borderColor: '#14b8a6',
                    backgroundColor: 'rgba(20, 184, 166, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f3f4f6' }, beginAtZero: false },
                },
            }
        });

        var lastIdx = offlinePoints.length - 1;
        new Chart(offlineEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: offlinePoints.map(p => p.label),
                datasets: [{
                    data: offlinePoints.map(p => p.value),
                    backgroundColor: offlinePoints.map((_, i) => i === lastIdx ? '#f59e0b' : '#94a3b8'),
                    borderRadius: 4,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f3f4f6' }, beginAtZero: true },
                },
            }
        });
    }

    loadChartJs(renderCharts);
</script>
@endscript

</div>