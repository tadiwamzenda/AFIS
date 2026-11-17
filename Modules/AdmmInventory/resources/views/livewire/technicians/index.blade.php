<div class="max-w-3xl space-y-4">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-2">{{ session('success') }}</div>
    @endif

    <form wire:submit="add" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
            <input type="text" wire:model="name" placeholder="Technician name"
                   class="w-56 px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Phone (optional)</label>
            <input type="text" wire:model="phone" placeholder="0771234567"
                   class="w-40 px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
            @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg">
            Add technician
        </button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Phone</th>
                    <th class="text-center px-4 py-2">Devices held</th>
                    <th class="text-center px-4 py-2">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($technicians as $t)
                <tr wire:key="tech-{{ $t->id }}" class="{{ $t->is_active ? '' : 'text-gray-400' }}">
                    @if($editingId === $t->id)
                        <td class="px-4 py-2">
                            <input type="text" wire:model="editName" class="w-48 px-2 py-1 border border-gray-300 rounded text-sm">
                            @error('editName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" wire:model="editPhone" class="w-32 px-2 py-1 border border-gray-300 rounded text-sm">
                        </td>
                        <td class="px-4 py-2 text-center">{{ $t->devices_count }}</td>
                        <td class="px-4 py-2 text-center">—</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="saveEdit" class="text-brand-600 hover:underline text-xs font-medium">Save</button>
                            <button wire:click="cancelEdit" class="text-gray-500 hover:underline text-xs ml-2">Cancel</button>
                        </td>
                    @else
                        <td class="px-4 py-2 font-medium">{{ $t->name }}</td>
                        <td class="px-4 py-2">{{ $t->phone ?: '—' }}</td>
                        <td class="px-4 py-2 text-center">{{ $t->devices_count }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $t->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $t->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="startEdit({{ $t->id }})" class="text-brand-600 hover:underline text-xs font-medium">Edit</button>
                            <button wire:click="toggleActive({{ $t->id }})" class="text-gray-500 hover:underline text-xs ml-2">
                                {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No technicians yet. Add the first one above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        Deactivated technicians no longer appear in the assignment dropdown but stay on historical records.
    </p>
</div>
