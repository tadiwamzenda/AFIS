<div class="space-y-4" wire:poll.60000ms>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Summary cards / tabs --}}
    <div class="grid grid-cols-4 gap-4">
        <button wire:click="setFilter('all')" wire:click.prevent="$set('view', 'states'); setFilter('all')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $view === 'states' && $filter === 'all' ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-400 hover:shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 hover:shadow-sm' }}">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total Fleet</p>
            <p class="text-3xl font-bold {{ $view === 'states' && $filter === 'all' ? 'text-brand-700' : 'text-gray-800' }}">{{ $totalCount }}</p>
            <p class="text-xs text-gray-400 mt-1">All vehicles</p>
        </button>

        <button wire:click="$set('view', 'states')" wire:click.prevent="setFilter('online')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $view === 'states' && $filter === 'online' ? 'border-green-500 bg-green-50 ring-1 ring-green-400 hover:shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 hover:shadow-sm' }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Online</p>
            </div>
            <p class="text-3xl font-bold {{ $view === 'states' && $filter === 'online' ? 'text-green-700' : 'text-gray-800' }}">{{ $onlineCount }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $totalCount > 0 ? round(($onlineCount / $totalCount) * 100) : 0 }}% of fleet</p>
        </button>

        <button wire:click="$set('view', 'states')" wire:click.prevent="setFilter('offline')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $view === 'states' && $filter === 'offline' ? 'border-red-500 bg-red-50 ring-1 ring-red-400 hover:shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 hover:shadow-sm' }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Offline</p>
            </div>
            <p class="text-3xl font-bold {{ $view === 'states' && $filter === 'offline' ? 'text-red-700' : 'text-gray-800' }}">{{ $offlineCount }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $totalCount > 0 ? round(($offlineCount / $totalCount) * 100) : 0 }}% of fleet · Functional &amp; Bantu Track
                @if($excludedCount > 0)<span class="block">+ {{ $excludedCount }} offline not counted (client / inactive)</span>@endif
            </p>
        </button>

        <button wire:click="setView('reports')"
            class="rounded-xl border p-4 text-left transition-all
            {{ $view === 'reports' ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-400 hover:shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 hover:shadow-sm' }}">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-3.5 h-3.5 {{ $view === 'reports' ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Reports</p>
            </div>
            <p class="text-3xl font-bold {{ $view === 'reports' ? 'text-purple-700' : 'text-gray-800' }}">{{ $reportsCount }}</p>
            <p class="text-xs text-gray-400 mt-1">Generated reports</p>
        </button>
    </div>

    @if($view === 'states')
    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Vehicle states</h3>
                <p class="text-xs text-gray-400 mt-0.5">Auto-refreshes every 60 seconds</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <input wire:model.live.debounce.300ms="search"
                    type="text" placeholder="Search vehicle..."
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400 w-44">

                <select wire:change="setDurationFilter($event.target.value)"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <option value="" {{ $durationFilter === '' ? 'selected' : '' }}>All durations</option>
                    <option value="hourly" {{ $durationFilter === 'hourly' ? 'selected' : '' }}>Hourly (1–24h)</option>
                    <option value="daily" {{ $durationFilter === 'daily' ? 'selected' : '' }}>Daily (1–7d)</option>
                    <option value="weekly" {{ $durationFilter === 'weekly' ? 'selected' : '' }}>7+ days</option>
                </select>

                <select wire:change="setCommentFilter($event.target.value)"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <option value="" {{ $commentFilter === '' ? 'selected' : '' }}>All comments</option>
                    @foreach($commentOptions as $opt)
                        <option value="{{ $opt }}" {{ $commentFilter === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>

                <select wire:change="setActionPartyFilter($event.target.value)"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <option value="" {{ $actionPartyFilter === '' ? 'selected' : '' }}>All action parties</option>
                    @foreach($partyOptions as $opt)
                        <option value="{{ $opt }}" {{ $actionPartyFilter === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>

                <button wire:click="saveUpdates"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 px-3 py-1.5 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Update
                </button>

                <select wire:model.live="reportPeriod" title="Report period"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <option value="daily">Report: Daily</option>
                    <option value="weekly">Report: Weekly</option>
                    <option value="monthly">Report: Monthly</option>
                    <option value="custom">Report: Custom</option>
                </select>
                @if($reportPeriod === 'custom')
                    <input type="date" wire:model.live="reportFrom" class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <input type="date" wire:model.live="reportTo" class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                @endif

                <a href="{{ route('admin.afis.fleet.offline-report.generate', ['clientId' => $clientId, 'filter' => $filter, 'duration' => $durationFilter, 'comment' => $commentFilter, 'action_party' => $actionPartyFilter, 'search' => $search, 'period' => $reportPeriod, 'from' => $reportFrom, 'to' => $reportTo]) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Offline Report
                </a>
            </div>
        </div>

        @if(empty($vehicles))
        <div class="px-5 py-10 text-center text-sm text-gray-400">
            No vehicles found{{ $filter !== 'all' ? ' for filter: ' . $filter : '' }}{{ $durationFilter ? " ({$durationFilter})" : '' }}{{ $commentFilter ? " ({$commentFilter})" : '' }}{{ $actionPartyFilter ? " ({$actionPartyFilter})" : '' }}.
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
                        <th class="px-4 py-3 text-center">Comment</th>
                        <th class="px-4 py-3 text-center">Action Party</th>
                        <th class="px-4 py-3 text-left">Updated</th>
                        <th class="px-4 py-3 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" x-data="{ sel: null }">
                    @foreach($vehicles as $v)
                    <tr wire:key="veh-{{ $v['id'] }}"
                        @click="sel = {{ $v['id'] }}"
                        :style="sel === {{ $v['id'] }} ? 'background:#fef3c7;box-shadow:inset 0 0 0 2px #fbbf24' : ''"
                        style="cursor:pointer"
                        class="hover:bg-gray-50 transition-colors {{ $v['status'] === 'offline' ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $v['label'] }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $v['group'] }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($v['status'] === 'online')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Online
                            </span>
                            @elseif($v['status'] === 'unknown')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                Unknown
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
                        <td class="px-4 py-3 text-center">
                            @if($v['status'] !== 'offline')
                                <span class="text-xs text-gray-400">n/a</span>
                            @elseif($v['incident_id'])
                                <select wire:model="comments.{{ $v['incident_id'] }}"
                                    class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    <option value="">—</option>
                                    @foreach($commentOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                </select>
                            @else
                                <select wire:model="pendingComments.{{ $v['id'] }}"
                                    class="text-xs border border-amber-300 bg-amber-50 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    <option value="">Pending…</option>
                                    @foreach($commentOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                </select>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($v['status'] !== 'offline')
                                <span class="text-xs text-gray-400">n/a</span>
                            @elseif($v['incident_id'])
                                <select wire:model="actionParties.{{ $v['incident_id'] }}"
                                    class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    <option value="">—</option>
                                    @foreach($partyOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                </select>
                            @else
                                <select wire:model="pendingParties.{{ $v['id'] }}"
                                    class="text-xs border border-amber-300 bg-amber-50 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    <option value="">Pending…</option>
                                    @foreach($partyOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                </select>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                            @if($v['updated_at'])
                                {{ $v['updated_at'] }}
                                @if($v['updated_by'])<span class="block text-gray-400">{{ $v['updated_by'] }}</span>@endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($v['status'] === 'offline' && $v['incident_id'])
                                <button type="button" wire:click="saveVehicle({{ $v['incident_id'] }})"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 px-3 py-1.5 rounded-lg transition-colors">Save</button>
                                    
                            @elseif($v['status'] === 'offline')
                                <button type="button" wire:click="savePending({{ $v['id'] }})"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 px-3 py-1.5 rounded-lg transition-colors">Save</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @else
    {{-- Reports tab --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <h3 class="text-sm font-semibold text-gray-800">Generated reports</h3>
            <input wire:model.live.debounce.300ms="reportSearch"
                type="text" placeholder="Search reports..."
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400 w-52">
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($reports as $report)
            <div class="px-5 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $report->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $report->created_at->format('d M Y H:i') }} · {{ $report->created_at->diffForHumans() }}
                        @if($report->state_filter !== 'all') · State: {{ ucfirst($report->state_filter) }} @endif
                        @if($report->duration_filter) · Duration: {{ ucfirst($report->duration_filter) }} @endif
                        @if($report->comment_filter) · Comment: {{ $report->comment_filter }} @endif
                        @if($report->action_party_filter) · Action party: {{ $report->action_party_filter }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('admin.afis.offline-reports.download', $report->id) }}"
                        class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    <button wire:click="deleteReport({{ $report->id }})"
                        wire:confirm="Delete this report? This cannot be undone."
                        class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
            @empty
            <p class="px-5 py-10 text-sm text-gray-400 text-center">No reports generated yet.</p>
            @endforelse
        </div>
    </div>
    @endif

</div>