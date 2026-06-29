<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
        class="relative p-2 text-gray-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false"
        class="absolute right-0 top-10 w-80 bg-white rounded-xl border border-gray-200 shadow-lg z-50"
        x-transition>
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800">Notifications</p>
            @if($unreadCount > 0)
                <span class="text-xs text-gray-400">{{ $unreadCount }} unread</span>
            @endif
        </div>
        <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
            @forelse($latestFive as $notification)
            <div class="px-4 py-3 hover:bg-gray-50 {{ $notification->isUnread() ? 'bg-blue-50/50' : '' }}">
                <div class="flex items-start gap-2">
                    <span class="text-sm flex-shrink-0 mt-0.5">{{ $notification->severity_icon }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-800 truncate">{{ $notification->title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $notification->message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="px-4 py-6 text-xs text-gray-400 text-center">No notifications</p>
            @endforelse
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            <a href="{{ route('admin.notifications.index') }}"
                class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                View all notifications →
            </a>
        </div>
    </div>
</div>