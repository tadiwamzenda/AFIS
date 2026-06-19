<div>
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search name, email, contact..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
        <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All clients</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Contact</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Navixy account</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">SIM cards</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Devices</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
    @forelse($clients as $client)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
                <p class="font-medium text-gray-800">{{ $client->name }}</p>
                @if($client->notes)
                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $client->notes }}</p>
                @endif
            </td>

            <td class="px-4 py-3">
                <p class="text-gray-700">{{ $client->contact_person ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ $client->contact_email ?? '' }}</p>
            </td>

            <td class="px-4 py-3 font-mono text-xs text-gray-600">
                {{ $client->navixy_account_id }}
            </td>

            <td class="px-4 py-3 text-gray-600">
                {{ $client->sim_cards_count }}
            </td>

            <td class="px-4 py-3 text-gray-600">
                {{ $client->gps_devices_count }}
            </td>

            <td class="px-4 py-3">
                @if($client->is_active)
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                @else
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Inactive</span>
                @endif
            </td>

            <td class="px-4 py-3">
                <a href="{{ route('admin.admm.clients.edit', $client) }}"
                    class="text-brand-600 hover:text-brand-700 text-xs font-medium">
                    Edit
                </a>
            </td>
        </tr>

    @empty

        <tr>
            <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                No clients found.
            </td>
        </tr>

    @endforelse
</tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($clients->hasPages())
        <div class="mt-4">{{ $clients->links() }}</div>
    @endif
</div>