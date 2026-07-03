<div>
   
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Search name, email, contact..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <select wire:model.live="statusFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All clients</option>
            <option value="active">Active only</option>
            <option value="inactive">Inactive only</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Instance</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Group Prefix</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Contact</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">GPS Devices</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $client->name }}</p>
                        <p class="text-xs text-gray-400">{{ $client->contact_email ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        Instance {{ $client->navixy_instance }}
                    </td>
                    <td class="px-4 py-3">
                        @if($client->navixy_group_prefix)
                            <span class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                                {{ $client->navixy_group_prefix }}
                            </span>
                        @else
                            <span class="text-xs text-amber-600">Not set</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $client->contact_person ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $client->gps_devices_count }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.admm.clients.edit', $client) }}"
                                class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                                Edit
                            </a>
                            <button
                                wire:click="delete({{ $client->id }})"
                                wire:confirm="Delete {{ $client->name }}? This cannot be undone."
                                class="text-xs text-red-500 hover:text-red-700 font-medium">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">
                        No clients found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
        <div class="mt-4">{{ $clients->links() }}</div>
    @endif
</div>