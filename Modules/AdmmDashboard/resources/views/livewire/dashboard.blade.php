<div class="space-y-6">

    {{-- ── Summary cards ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Active clients</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalClients }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">SIM cards</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalSimCards }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-green-600">{{ $simClientAssigned }} client</span>
                <span class="text-xs text-amber-600">{{ $simInternalStock }} internal</span>
                <span class="text-xs text-gray-400">{{ $simUnallocated }} unallocated</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">GPS devices</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalGpsDevices }}</p>
            <div class="mt-2 flex gap-2 flex-wrap">
                <span class="text-xs text-green-600">{{ $devicesInstalled }} installed</span>
                <span class="text-xs text-blue-600">{{ $devicesInStock }} in stock</span>
                <span class="text-xs text-yellow-600">{{ $devicesRepair }} repair</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Accessories</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalAccessories }}</p>
            @if(array_sum($lostAssets) > 0)
                <p class="mt-2 text-xs text-red-500">
                    {{ array_sum($lostAssets) }} lost / missing
                </p>
            @endif
        </div>

    </div>

    {{-- ── Alerts row ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- SIM renewals --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">SIM bundle renewals due</h3>
                <span class="text-xs text-gray-400">Next 30 days</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($simRenewalsDue as $sim)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 font-mono">{{ $sim->iccid }}</p>
                            <p class="text-xs text-gray-400">{{ $sim->client?->name ?? 'Internal' }} · {{ $sim->network_provider }}</p>
                        </div>
                        <span class="text-xs font-medium {{ $sim->bundle_renewal_date->isPast() ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $sim->bundle_renewal_date->format('d M Y') }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-400 text-center">No renewals due in the next 30 days.</p>
                @endforelse
            </div>
        </div>

        {{-- Warranty expiring --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Device warranties expiring</h3>
                <span class="text-xs text-gray-400">Next 30 days</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($warrantyExpiring as $device)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 font-mono">{{ $device->serial_number }}</p>
                            <p class="text-xs text-gray-400">{{ $device->model }} · {{ $device->client?->name ?? 'Internal' }}</p>
                        </div>
                        <span class="text-xs font-medium {{ $device->warranty_expiry_date->isPast() ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $device->warranty_expiry_date->format('d M Y') }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-400 text-center">No warranties expiring in the next 30 days.</p>
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