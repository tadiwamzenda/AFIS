<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-5">
            {{ $isEditing ? 'Edit client' : 'Add client' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Client name --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client name <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('name') border-red-400 @enderror"
                    placeholder="e.g. Allied Timbers Zimbabwe">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Navixy instance --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy instance <span class="text-red-500">*</span>
                </label>
                <select wire:model="navixy_instance"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="1">Instance 1 (Premium)</option>
                    <option value="2">Instance 2 (Basic)</option>
                </select>
            </div>

            {{-- Navixy account ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy account ID <span class="text-red-500">*</span>
                </label>
                <input wire:model="navixy_account_id" type="number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('navixy_account_id') border-red-400 @enderror"
                    placeholder="e.g. 10055393">
                @error('navixy_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Navixy security group ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy security group ID
                    <span class="text-xs font-normal text-gray-400">(from subuser/list)</span>
                </label>
                <input wire:model="navixy_security_group_id" type="number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. 29762">
                <p class="mt-1 text-xs text-gray-400">Links this client to their Navixy sub-users for login.</p>
            </div>

            {{-- Navixy group prefix --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy group prefix
                    <span class="text-xs font-normal text-gray-400">(for auto-mapping tracker groups)</span>
                </label>
                <input wire:model="navixy_group_prefix" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. ATZ for Allied Timbers, REF for REA">
                <p class="mt-1 text-xs text-gray-400">Tracker groups starting with this prefix auto-link to this client.</p>
            </div>

            {{-- Navixy API key (independent accounts) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy API key
                    <span class="text-xs font-normal text-gray-400">(independent account only)</span>
                </label>
                <input wire:model="navixy_api_key" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"
                    placeholder="e.g. d4708da4fb0ec6e7d792044057977f47">
                <p class="mt-1 text-xs text-gray-400">
                    Only set this for clients with their own independent Navixy master account.
                    Leave blank for sub-users under Bantu Track master account.
                    Generate from: Navixy → Account Settings → API Keys.
                </p>
            </div>

            {{-- Secondary Navixy instance (multi-instance clients e.g. ZESA ENTERPRISES) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Secondary Navixy instance
                    <span class="text-xs font-normal text-gray-400">(only for clients on both instances)</span>
                </label>
                <select wire:model="navixy_instance_secondary"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="0">None</option>
                    <option value="1">Instance 1 (Premium)</option>
                    <option value="2">Instance 2 (Basic)</option>
                </select>
                <p class="mt-1 text-xs text-gray-400">e.g. ZESA ENTERPRISES has devices on both instances.</p>
            </div>

            {{-- Contact person --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact person</label>
                <input wire:model="contact_person" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Full name">
            </div>

            {{-- Contact email --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact email</label>
                <input wire:model="contact_email" type="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('contact_email') border-red-400 @enderror"
                    placeholder="email@client.com">
                @error('contact_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Contact phone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact phone</label>
                <input wire:model="contact_phone" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="+263 77 000 0000">
            </div>

            {{-- Active status --}}
            <div class="flex items-center gap-3 pt-5">
                <input wire:model="is_active" type="checkbox" id="is_active"
                    class="w-4 h-4 rounded border-gray-300 text-brand-500">
                <label for="is_active" class="text-sm font-medium text-gray-700">Active client</label>
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
                <span wire:loading.remove>{{ $isEditing ? 'Update client' : 'Add client' }}</span>
                <span wire:loading>Saving...</span>
            </button>
            <a href="{{ route('admin.admm.clients.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </div>
</div>