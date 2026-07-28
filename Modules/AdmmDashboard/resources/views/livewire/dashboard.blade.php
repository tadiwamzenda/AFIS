<div class="space-y-6">

    {{-- ── Summary cards ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Active clients --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Active clients</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalClients }}</p>
            <p class="mt-2 text-xs text-gray-400">{{ $totalTrackers }} trackers total</p>
        </div>

        {{-- GPS Devices --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">GPS Devices</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalDevices }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-green-600">{{ $devicesWithClient }} with client</span>
                <span class="text-xs text-blue-600">{{ $devicesInStock }} in stock</span>
                @if($devicesLost > 0)
                <span class="text-xs text-red-500">{{ $devicesLost }} lost</span>
                @endif
            </div>
        </div>

        {{-- SIM Cards --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">SIM Cards</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalSims }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-green-600">{{ $simsWithClient }} with client</span>
                <span class="text-xs text-blue-600">{{ $simsInStock }} in stock</span>
                @if($simsLost > 0)
                <span class="text-xs text-red-500">{{ $simsLost }} lost</span>
                @endif
            </div>
        </div>

        {{-- Lost assets --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Lost Assets</p>
            <p class="text-3xl font-bold {{ ($devicesLost + $simsLost) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ $devicesLost + $simsLost }}
            </p>
            <div class="mt-2 flex gap-2 flex-wrap">
                @if($devicesLost > 0)
                <span class="text-xs text-red-500">{{ $devicesLost }} devices</span>
                @endif
                @if($simsLost > 0)
                <span class="text-xs text-red-500">{{ $simsLost }} SIMs</span>
                @endif
                @if(($devicesLost + $simsLost) === 0)
                <span class="text-xs text-green-600">No lost assets ✓</span>
                @endif
            </div>
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
                {{-- Devices bar --}}
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

                {{-- SIMs bar --}}
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

</div>