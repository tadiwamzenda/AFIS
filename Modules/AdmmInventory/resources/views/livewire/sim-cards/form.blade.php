<div>
    <form wire:submit="save" class="space-y-5">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- ICCID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ICCID <span class="text-red-500">*</span></label>
                <input wire:model="iccid" type="text" maxlength="22"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('iccid') border-red-400 @enderror"
                    placeholder="89254...">
                @error('iccid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- MSISDN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">MSISDN (phone number)</label>
                <input wire:model="msisdn" type="text" maxlength="20"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="+263...">
                @error('msisdn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Network Provider --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Network provider <span class="text-red-500">*</span></label>
                <input wire:model="network_provider" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('network_provider') border-red-400 @enderror"
                    placeholder="Econet, Netone, Telecel...">
                @error('network_provider') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            
            {{-- Batch Code --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Batch code</label>
                <input wire:model="batch_code" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. BATCH-2026-001">
            </div>
            
            {{-- Bundle Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bundle type</label>
                <input wire:model="bundle_type" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Data only, Voice+Data...">
            </div>

            {{-- Bundle Renewal Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bundle renewal date</label>
                <input wire:model="bundle_renewal_date" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                @error('bundle_renewal_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select wire:model="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="unassigned">Unassigned</option>
                    <option value="active_client">Active — Client</option>
                    <option value="active_internal">Active — Internal</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                    <option value="deactivated">Deactivated</option>
                    <option value="lost">Lost</option>
                </select>
            </div>

            {{-- Location Context --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                <select wire:model.live="location_context" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="unallocated">Unallocated</option>
                    <option value="internal_stock">Internal Stock</option>
                    <option value="client_assigned">Client Assigned</option>
                </select>
            </div>

            {{-- Client (only shown when client_assigned) --}}
            @if($location_context === 'client_assigned')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                <select wire:model="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('client_id') border-red-400 @enderror">
                    <option value="">Select client...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
                @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif

        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea wire:model="notes" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                placeholder="Any additional notes..."></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                {{ $isEditing ? 'Update SIM card' : 'Create SIM card' }}
            </button>
            <a href="{{ route('admin.admm.sim-cards.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                Cancel
            </a>
        </div>

    </form>
</div>