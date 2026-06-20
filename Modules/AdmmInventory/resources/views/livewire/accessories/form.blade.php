<div>
    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Accessory type <span class="text-red-500">*</span></label>
                <select wire:model="accessory_type_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('accessory_type_id') border-red-400 @enderror">
                    <option value="">Select type...</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('accessory_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial number</label>
                <input wire:model="serial_number" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Optional serial number">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select wire:model="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="in_office_stock">In Office Stock</option>
                    <option value="installed_client">Installed — Client</option>
                    <option value="faulty">Faulty</option>
                    <option value="decommissioned">Decommissioned</option>
                    <option value="lost">Lost</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                <select wire:model.live="location_context" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="internal_stock">Internal Stock</option>
                    <option value="client_assigned">Client Assigned</option>
                    <option value="unallocated">Unallocated</option>
                </select>
            </div>

            @if($location_context === 'client_assigned')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                <select wire:model="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Select client...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Attached to GPS device</label>
                <select wire:model="gps_device_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Not attached to device</option>
                    @foreach($gpsDevices as $device)
                        <option value="{{ $device->id }}">{{ $device->serial_number }} — {{ $device->model }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase date</label>
                <input wire:model="purchase_date" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea wire:model="notes" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                placeholder="Any additional notes..."></textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                {{ $isEditing ? 'Update accessory' : 'Create accessory' }}
            </button>
            <a href="{{ route('admin.admm.accessories.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Cancel</a>
        </div>
    </form>
</div>