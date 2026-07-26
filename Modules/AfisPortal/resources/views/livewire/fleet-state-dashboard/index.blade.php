<div class="space-y-4" wire:poll.60000ms>

    {{-- Summary cards --}}
    <div class="grid grid-cols-3 gap-4">
        <button wire:click="setFilter('all')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $filter === 'all' ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-400' : 'border-gray-200 bg-white hover:border-gray-300' }}">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Fleet</p>
            <p class="text-3xl font-bold {{ $filter === 'all' ? 'text-brand-700' : 'text-gray-800' }}">{{ $totalCount }}</p>
            <p class="text-xs text-gray-400 mt-1">All vehicles</p>
        </button>

        <button wire:click="setFilter('online')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $filter === 'online' ? 'border-green-500 bg-green-50 ring-1 ring-green-400' : 'border-gray-200 bg-white hover:border-gray-300' }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Online</p>
            </div>
            <p class="text-3xl font-bold {{ $filter === 'online' ? 'text-green-700' : 'text-gray-800' }}">{{ $onlineCount }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $totalCount > 0 ? round(($onlineCount / $totalCount) * 100) : 0 }}% of fleet</p>
        </button>

        <button wire:click="setFilter('offline')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $filter === 'offline' ? 'border-red-500 bg-red-50 ring-1 ring-red-400' : 'border-gray-200 bg-white hover:border-gray-300' }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Offline</p>
            </div>
            <p class="text-3xl font-bold {{ $filter === 'offline' ? 'text-red-700' : 'text-gray-800' }}">{{ $offlineCount }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $totalCount > 0 ? round(($offlineCount / $totalCount) * 100) : 0 }}% of fleet</p>
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Vehicle states</h3>
                <p class="text-xs text-gray-400 mt-0.5">Auto-refreshes every 60 seconds</p>
            </div>
            <div class="flex items-center gap-3">
                <input wire:model.live.debounce.300ms="search"
                    type="text" placeholder="Search vehicle..."
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400 w-44">
                <a href="{{ route('admin.afis.fleet.offline-report', $this->clientId) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Offline Report
                </a>
            </div>
        </div>

        @if(empty($vehicles))
        <div class="px-5 py-10 text-center text-sm text-gray-400">
            No vehicles found{{ $filter !== 'all' ? ' for filter: ' . $filter : '' }}.
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Vehicle</th>
                        <th class="px-4 py-3 text-left">Group</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Since</th>
                        <th class="px-4 py-3 text-center">Duration in state</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($vehicles as $v)
                    <tr class="hover:bg-gray-50 transition-colors {{ $v['status'] === 'offline' ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $v['label'] }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $v['group'] }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($v['status'] === 'online')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Online
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Offline
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $v['since'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-xs font-medium {{ $v['status'] === 'offline' ? 'text-red-600' : 'text-green-600' }}">
                                {{ $v['duration'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>