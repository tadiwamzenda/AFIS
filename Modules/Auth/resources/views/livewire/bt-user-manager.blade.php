<div class="space-y-4">

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Form --}}
    @if($showForm)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">
            {{ $editingId ? 'Edit staff member' : 'Add staff member' }}
        </h3>

        @if($formError)
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $formError }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full name <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('name') border-red-400 @enderror"
                    placeholder="Full name">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email (Navixy login) <span class="text-red-500">*</span></label>
                <input wire:model="email" type="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('email') border-red-400 @enderror"
                    placeholder="email@bantutrack.co.zw">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select wire:model="role"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="bt_admin">BT Admin</option>
                    <option value="bt_technician">BT Technician</option>
                    <option value="bt_support">BT Support</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-6">
                <input wire:model="is_active" type="checkbox" id="is_active"
                    class="w-4 h-4 rounded border-gray-300 text-brand-500">
                <label for="is_active" class="text-sm font-medium text-gray-700">Active account</label>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-5">
            <button wire:click="save"
                class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                {{ $editingId ? 'Update' : 'Add staff member' }}
            </button>
            <button wire:click="cancelForm" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    </div>
    @endif

    {{-- List --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search name or email..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            @if(!$showForm)
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add staff
            </button>
            @endif
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($users as $user)
            <div class="px-5 py-4 flex items-center gap-4">
                <div class="w-9 h-9 rounded-full bg-brand-500 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $user->role === 'bt_admin' ? 'bg-brand-100 text-brand-700' :
                           ($user->role === 'bt_technician' ? 'bg-blue-100 text-blue-700' :
                                                              'bg-gray-100 text-gray-600') }}">
                        {{ match($user->role) {
                            'bt_admin'      => 'Admin',
                            'bt_technician' => 'Technician',
                            'bt_support'    => 'Support',
                            default         => $user->role
                        } }}
                    </span>
                    <button wire:click="toggleActive({{ $user->id }})"
                        class="text-xs font-medium px-2 py-0.5 rounded-full {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </button>
                    <button wire:click="openEdit({{ $user->id }})"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        Edit
                    </button>
                </div>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">No staff members found.</p>
            @endforelse
        </div>

        @if($users->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $users->links() }}</div>
        @endif
    </div>

</div>