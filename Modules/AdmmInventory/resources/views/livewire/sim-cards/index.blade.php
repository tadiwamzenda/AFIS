
<div>
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search ICCID, MSISDN, provider..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
        <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All statuses</option>
            <option value="active_client">Active — Client</option>
            <option value="active_internal">Active — Internal</option>
            <option value="unassigned">Unassigned</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="deactivated">Deactivated</option>
            <option value="lost">Lost</option>
        </select>
        <select wire:model.live="contextFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All locations</option>
            <option value="client_assigned">Client Assigned</option>
            <option value="internal_stock">Internal Stock</option>
            <option value="unallocated">Unallocated</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">ICCID</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">MSISDN</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Provider</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Location</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Renewal</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($simCards as $sim)
                    
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $sim->iccid }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $sim->msisdn ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $sim->network_provider }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'active_client'   => 'bg-green-100 text-green-700',
                                        'active_internal' => 'bg-blue-100 text-blue-700',
                                        'unassigned'      => 'bg-gray-100 text-gray-600',
                                        'inactive'        => 'bg-yellow-100 text-yellow-700',
                                        'suspended'       => 'bg-orange-100 text-orange-700',
                                        'deactivated'     => 'bg-red-100 text-red-700',
                                        'lost'            => 'bg-red-100 text-red-700',
                                    ];
                                    $color = $statusColors[$sim->status] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                    {{ $sim->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($sim->location_context === 'client_assigned')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-brand-50 text-brand-700">Client</span>
                                @elseif($sim->location_context === 'internal_stock')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Internal</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Unallocated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $sim->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($sim->bundle_renewal_date)
                                    <span class="{{ $sim->bundle_renewal_date->isPast() ? 'text-red-600 font-medium' : '' }}">
                                        {{ $sim->bundle_renewal_date->format('d M Y') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.admm.sim-cards.edit', $sim) }}" class="text-brand-600 hover:text-brand-700 text-xs font-medium">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400 text-sm">
                                No SIM cards found.
                            </td>
                        </tr>
                     
                @endforelse      
               
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($simCards->hasPages())
        <div class="mt-4">
            {{ $simCards->links() }}
        </div>
    @endif
</div>