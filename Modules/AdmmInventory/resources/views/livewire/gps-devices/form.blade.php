<div>
    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial number <span class="text-red-500">*</span></label>
                <input wire:model="serial_number" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('serial_number') border-red-400 @enderror"
                    placeholder="Device serial number">
                @error('serial_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model <span class="text-red-500">*</span></label>
                <input wire:model="model" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('model') border-red-400 @enderror"
                    placeholder="e.g. Teltonika FMB140">
                @error('model') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Firmware version</label>
                <input wire:model="firmware_version" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. 03.27.07">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase date</label>
                <input wire:model="purchase_date" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Warranty expiry date</label>
                <input wire:model="warranty_expiry_date" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select wire:model="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="in_office_stock">In Office Stock</option>
                    <option value="installed_client">Installed — Client</option>
                    <option value="under_repair">Under Repair</option>
                    <option value="awaiting_disposal">Awaiting Disposal</option>
                    <option value="decommissioned">Decommissioned</option>
                    <option value="lost_stolen">Lost / Stolen</option>
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle registration</label>
                <input wire:model="vehicle_registration" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. ABC 1234">
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SIM card</label>
                <select wire:model="sim_card_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">No SIM card</option>
                    @foreach($simCards as $sim)
                        <option value="{{ $sim->id }}">{{ $sim->iccid }} {{ $sim->msisdn ? '— ' . $sim->msisdn : '' }}</option>
                    @endforeach
                </select>
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
                {{ $isEditing ? 'Update device' : 'Create device' }}
            </button>
            <a href="{{ route('admin.admm.gps-devices.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Cancel</a>
        </div>

    </form>
</div>