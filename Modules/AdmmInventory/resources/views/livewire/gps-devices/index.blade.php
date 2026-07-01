<div>
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Search serial number, model, vehicle reg..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All statuses</option>
            <option value="installed_client">Installed — Client</option>
            <option value="in_office_stock">In Office Stock</option>
            <option value="under_repair">Under Repair</option>
            <option value="awaiting_disposal">Awaiting Disposal</option>
            <option value="decommissioned">Decommissioned</option>
            <option value="lost_stolen">Lost / Stolen</option>
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
                    <th class="text-left px-4 py-3 font-medium text-gray-600">IMEI</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Vehicle</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Fleet No.</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">SIM</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Installed</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($devices as $device)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $device->imei ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs font-medium text-gray-600">{{ $device->device_type ?? $device->model ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ $device->vehicle_registration ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $device->vehicle_make ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600">{{ $device->fleet_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $device->client?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $device->simCard?->msisdn ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $device->status === 'installed'       ? 'bg-green-100 text-green-700' :
                            ($device->status === 'in_office_stock' ? 'bg-blue-100 text-blue-700' :
                            ($device->status === 'decommissioned'  ? 'bg-gray-100 text-gray-500' :
                                                                        'bg-red-100 text-red-700')) }}">
                            {{ $device->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">
                        {{ $device->installed_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.admm.gps-devices.edit', $device) }}"
                            class="text-xs text-brand-600 hover:text-brand-700 font-medium">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">
                        No GPS devices found. <a href="{{ route('admin.admm.gps-devices.create') }}" class="text-brand-600 hover:text-brand-700 font-medium">Add one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($devices->hasPages())
        <div class="mt-4">{{ $devices->links() }}</div>
    @endif
</div>