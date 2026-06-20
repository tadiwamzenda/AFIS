<div>
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Search serial number..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <select wire:model.live="typeFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All types</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All statuses</option>
            <option value="installed_client">Installed — Client</option>
            <option value="in_office_stock">In Office Stock</option>
            <option value="faulty">Faulty</option>
            <option value="decommissioned">Decommissioned</option>
            <option value="lost">Lost</option>
        </select>
        <select wire:model.live="contextFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All locations</option>
            <option value="client_assigned">Client Assigned</option>
            <option value="internal_stock">Internal Stock</option>
            <option value="unallocated">Unallocated</option>
        </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Serial number</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Location</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Device</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($accessories as $accessory)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $accessory->accessoryType->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $accessory->serial_number ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = [
                                    'installed_client' => 'bg-green-100 text-green-700',
                                    'in_office_stock'  => 'bg-blue-100 text-blue-700',
                                    'faulty'           => 'bg-yellow-100 text-yellow-700',
                                    'decommissioned'   => 'bg-red-100 text-red-700',
                                    'lost'             => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$accessory->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $accessory->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($accessory->location_context === 'client_assigned')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-brand-50 text-brand-700">Client</span>
                            @elseif($accessory->location_context === 'internal_stock')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Internal</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Unallocated</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $accessory->client?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $accessory->gpsDevice?->serial_number ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.admm.accessories.edit', $accessory) }}"
                                class="text-brand-600 hover:text-brand-700 text-xs font-medium">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">No accessories found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($accessories->hasPages())
        <div class="mt-4">{{ $accessories->links() }}</div>
    @endif
</div>