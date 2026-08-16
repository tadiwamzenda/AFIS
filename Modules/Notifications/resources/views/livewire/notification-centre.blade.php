<div class="space-y-4">

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between flex-wrap">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search vehicle..."
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-44">

            <select wire:model.live="severityFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All severities</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="severe">Severe</option>
                <option value="critical">Critical</option>
            </select>

            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="attended">Attended</option>
            </select>

            <select wire:model.live="clientFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client }}">{{ $client }}</option>
                @endforeach
            </select>

            <label class="flex items-center gap-2 cursor-pointer">
                <input wire:model.live="unreadOnly" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-brand-500">
                <span class="text-sm text-gray-600">Unread only</span>
            </label>
        </div>
        @if($unreadCount > 0)
        <button wire:click="markAllRead"
            class="text-sm text-brand-600 hover:text-brand-700 font-medium">
            Mark all read ({{ $unreadCount }})
        </button>
        @endif
    </div>

{{-- PDF export --}}
    <div class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3">
        <span class="text-sm text-gray-600 font-medium">Generate report:</span>
        <select wire:model.live="durationFilter" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
            <option value="">All durations</option>
            <option value="hourly">Hourly (1-24h)</option>
            <option value="daily">Daily (1-7d)</option>
            <option value="weekly">Weekly (7d+)</option>
        </select>
        <a href="{{ route('admin.notifications.report', ['duration' => $durationFilter, 'severity' => $severityFilter, 'status' => $statusFilter, 'client' => $clientFilter, 'search' => $search]) }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download PDF
        </a>
    </div>

    {{-- Notifications list --}}
    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50">
        @forelse($notifications as $notification)
        <div class="px-5 py-4 flex items-start gap-4 {{ $notification->isUnread() ? 'bg-blue-50/30' : '' }}">
            <div class="flex-shrink-0 mt-0.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm
                    {{ $notification->severity === 'critical' ? 'bg-red-100' :
                    ($notification->severity === 'severe' ? 'bg-orange-100' :
                    ($notification->severity === 'warning' ? 'bg-yellow-100' : 'bg-blue-100')) }}">
                    {{ $notification->severity_icon }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <p class="text-sm font-medium text-gray-800">{{ $notification->title }}</p>
                    @if($notification->isUnread())
                        <span class="w-2 h-2 bg-brand-500 rounded-full flex-shrink-0"></span>
                    @endif
                    @if($notification->status)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $notification->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                            {{ ucfirst($notification->status) }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                <div class="flex items-center gap-3 mt-1.5">
                    <span class="text-xs text-gray-400">{{ $notification->created_at->format('d M Y H:i') }}</span>
                    <span class="text-xs text-gray-400">·</span>
                    <span class="text-xs text-gray-400">{{ $notification->module }}</span>
                    @if($notification->status_changed_at)
                        <span class="text-xs text-gray-400">· Status changed {{ $notification->status_changed_at->diffForHumans() }}</span>
                    @endif
                    @if($notification->isUnread())
                        <button wire:click="markRead({{ $notification->id }})"
                            class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                            Mark read
                        </button>
                    @endif
                </div>
            </div>
            <div class="flex-shrink-0">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full border {{ $notification->severity_color }}">
                    {{ ucfirst($notification->severity) }}
                </span>
            </div>
        </div>
        @empty
        <p class="px-5 py-10 text-sm text-gray-400 text-center">No notifications found.</p>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif

</div>