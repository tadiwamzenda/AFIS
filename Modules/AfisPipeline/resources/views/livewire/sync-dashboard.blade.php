<div class="space-y-6">

    @if($message)
        <div class="rounded-lg bg-brand-50 border border-brand-200 px-4 py-3 text-sm text-brand-700">
            {{ $message }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Trackers synced</p>
            <p class="text-3xl font-bold text-gray-900">{{ $trackerCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Trips stored</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalTrips }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Events stored</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalEvents }}</p>
        </div>
    </div>

    {{-- Manual sync controls --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Manual sync</h3>
                <p class="text-xs text-gray-400 mt-0.5">Scheduler runs automatically every 15 minutes in production</p>
            </div>
            <button wire:click="syncAll"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync all clients
            </button>
        </div>

        <div class="space-y-2">
            @foreach($clients as $client)
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $client->name }}</p>
                        <p class="text-xs text-gray-400">{{ $client->gps_devices_count }} devices</p>
                    </div>
                    <button wire:click="syncClient({{ $client->id }})"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        Sync now
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent sync logs --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Sync history</h3>
            <span class="text-xs text-gray-400">Last 20 syncs</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentLogs as $log)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $log->client?->name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $log->created_at?->format('d M Y H:i') }}
                            @if($log->error_message)
                                · <span class="text-red-500">{{ Str::limit($log->error_message, 60) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $log->status === 'completed' ? 'bg-green-100 text-green-700' :
                               ($log->status === 'failed'    ? 'bg-red-100 text-red-700' :
                               ($log->status === 'running'   ? 'bg-blue-100 text-blue-700' :
                                                               'bg-gray-100 text-gray-600')) }}">
                            {{ ucfirst($log->status) }}
                        </span>
                        @if($log->status === 'completed')
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $log->trackers_synced }}T · {{ $log->trips_synced }}Tr · {{ $log->events_synced }}E
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">No syncs yet. Click "Sync all clients" to start.</p>
            @endforelse
        </div>
    </div>

</div>