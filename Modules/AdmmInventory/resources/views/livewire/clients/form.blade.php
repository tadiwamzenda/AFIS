<div>
    <form wire:submit="save" class="space-y-5">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Name --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client name <span class="text-red-500">*</span>
                </label>
                <input wire:model="name" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('name') border-red-400 @enderror"
                    placeholder="Company name">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Navixy Account ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Navixy account ID <span class="text-red-500">*</span>
                </label>
                <input wire:model="navixy_account_id" type="number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('navixy_account_id') border-red-400 @enderror"
                    placeholder="Navixy numeric account ID">
                @error('navixy_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Contact Person --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact person</label>
                <input wire:model="contact_person" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Full name">
            </div>

            {{-- Contact Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact email</label>
                <input wire:model="contact_email" type="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('contact_email') border-red-400 @enderror"
                    placeholder="email@company.com">
                @error('contact_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Contact Phone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact phone</label>
                <input wire:model="contact_phone" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="+263...">
            </div>

            {{-- Active Status --}}
            <div class="flex items-center gap-3 pt-6">
                <input wire:model="is_active" type="checkbox" id="is_active"
                    class="w-4 h-4 rounded border-gray-300 text-brand-500">
                <label for="is_active" class="text-sm font-medium text-gray-700">Active client</label>
            </div>

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
                {{ $isEditing ? 'Update client' : 'Create client' }}
            </button>
            <a href="{{ route('admin.admm.clients.index') }}"
                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                Cancel
            </a>
        </div>

    </form>
</div>