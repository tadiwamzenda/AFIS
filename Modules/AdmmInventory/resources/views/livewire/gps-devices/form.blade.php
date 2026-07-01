<div class="max-w-3xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-5">
            {{ $device->exists ? 'Edit GPS device' : 'Add GPS device' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- IMEI --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    IMEI <span class="text-red-500">*</span>
                    <span class="text-xs font-normal text-gray-400">(15-digit device identifier)</span>
                </label>
                <input wire:model="imei" type="text" maxlength="20"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono @error('imei') border-red-400 @enderror"
                    placeholder="e.g. 862410128003122">
                @error('imei') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Device Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Device type</label>
                <select wire:model="device_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Select type...</option>
                    @foreach(\Modules\AdmmInventory\Models\GpsDevice::DEVICE_TYPES as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Model name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model name
                    <span class="text-xs font-normal text-gray-400">(optional)</span>
                </label>
                <input wire:model="model" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. kingwo_mt100">
            </div>

            {{-- Vehicle Registration --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle registration</label>
                <input wire:model="vehicle_registration" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 uppercase"
                    placeholder="e.g. AFX 0629">
            </div>

            {{-- Vehicle Make --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle make</label>
                <input wire:model="vehicle_make" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. Toyota Hilux">
            </div>

            {{-- Fleet Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fleet number</label>
                <input wire:model="fleet_number" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. T 8093">
            </div>

            {{-- Bantu Technician --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bantu technician</label>
                <input wire:model="technician" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Technician name">
            </div>

            {{-- Installation Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Installation date</label>
                <input wire:model="installed_at" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select wire:model="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @foreach(\Modules\AdmmInventory\Models\GpsDevice::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                <select wire:model.live="client_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">No client (in stock)</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- SIM Card --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SIM card</label>
                <select wire:model="sim_card_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">No SIM assigned</option>
                    @foreach($simCards as $sim)
                        <option value="{{ $sim->id }}">{{ $sim->msisdn }} — {{ $sim->isp ?? $sim->network_provider }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea wire:model="notes" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Any additional notes..."></textarea>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
            <button wire:click="save" wire:loading.attr="disabled"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                <span wire:loading.remove>{{ $device->exists ? 'Update device' : 'Add device' }}</span>
                <span wire:loading>Saving...</span>
            </button>
            <a href="{{ route('admin.admm.gps-devices.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            @if($device->exists)
                <button wire:click="delete"
                    wire:confirm="Are you sure you want to delete this device? This cannot be undone."
                    class="ml-auto text-sm text-red-600 hover:text-red-700 font-medium">
                    Delete device
                </button>
            @endif
        </div>
    </div>
</div>