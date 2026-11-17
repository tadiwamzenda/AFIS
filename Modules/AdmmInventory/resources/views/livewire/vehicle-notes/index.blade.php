<div class="space-y-4">
    @if($message)<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ $message }}</div>@endif

    <p class="text-xs text-gray-500">Notes are added from a client's Online / Offline States page (Add note on the vehicle row). Offline vehicles with an active note can be hidden there.</p>

    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search vehicle or note text…" class="flex-1 min-w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <select wire:model.live="clientFilter" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All clients</option>
            @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <select wire:model.live="status" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="active">Active</option>
            <option value="resolved">Resolved / expired</option>
            <option value="all">All</option>
        </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 text-gray-500 uppercase">
                <tr>
                    <th class="text-left px-3 py-2">Vehicle</th>
                    <th class="text-left px-3 py-2">Client</th>
                    <th class="text-left px-3 py-2">Tag</th>
                    <th class="text-left px-3 py-2">Note</th>
                    <th class="text-left px-3 py-2">Added</th>
                    <th class="text-left px-3 py-2">Review by</th>
                    <th class="text-left px-3 py-2">Status</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($notes as $n)
                @php
                    $isActive = !$n->resolved_at && (!$n->expires_at || $n->expires_at->gte(now()->timezone('Africa/Harare')->startOfDay()));
                @endphp
                <tr wire:key="note-{{ $n->id }}">
                    <td class="px-3 py-2 font-medium">{{ $trackers[$n->tracker_id] ?? ('#' . $n->tracker_id) }}</td>
                    <td class="px-3 py-2">{{ $clients->firstWhere('id', $n->client_id)?->name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $n->tag_label }}</td>
                    <td class="px-3 py-2">{{ $n->note }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $n->created_at?->timezone('Africa/Harare')->format('d M Y H:i') }}<span class="block text-gray-400">{{ $users[$n->created_by] ?? '' }}</span></td>
                    <td class="px-3 py-2">{{ $n->expires_at?->format('d M Y') ?? '—' }}</td>
                    <td class="px-3 py-2">
                        @if($isActive)<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Active</span>
                        @elseif($n->resolved_at)<span class="px-2 py-0.5 rounded-full bg-green-100 text-green-800">Resolved</span>
                        @else<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Expired</span>@endif
                    </td>
                    <td class="px-3 py-2 text-right">
                        @if($isActive)<button wire:click="resolve({{ $n->id }})" class="text-green-700 hover:underline">Resolve</button>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No notes found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $notes->links() }}
</div>
