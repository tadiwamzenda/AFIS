<div class="max-w-6xl space-y-4">

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
                    <th class="text-center px-4 py-2">Devices installed</th>
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
                        <td class="px-4 py-2 text-center">{{ $t->held_count }}</td>
                        <td class="px-4 py-2 text-center">{{ $t->installed_count }}</td>
                        <td class="px-4 py-2 text-center">—</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="saveEdit" class="text-brand-600 hover:underline text-xs font-medium">Save</button>
                            <button wire:click="cancelEdit" class="text-gray-500 hover:underline text-xs ml-2">Cancel</button>
                        </td>
                    @else
                        <td class="px-4 py-2 font-medium">{{ $t->name }}</td>
                        <td class="px-4 py-2">{{ $t->phone ?: '—' }}</td>
                        <td class="px-4 py-2 text-center font-medium">{{ $t->held_count }}</td>
                        <td class="px-4 py-2 text-center font-medium">{{ $t->installed_count }}</td>
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
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No technicians yet. Add the first one above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        <b>Held</b> = paired devices currently with the technician, not yet installed.
        <b>Installed</b> = devices currently installed on a vehicle that this technician installed.
        Deactivated technicians no longer appear in the assignment dropdown but stay on historical records.
    </p>

    {{-- ═══════════ STATISTICS REPORT ═══════════ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-800">Installed &amp; held statistics</h3>
            @if($report)
                <button wire:click="exportReport" type="button" class="text-sm font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">Export CSV</button>
            @endif
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                @foreach(['today' => 'Daily (today)', 'week' => 'Weekly (this week)', 'month' => 'Monthly (this month)', 'custom' => 'Custom dates'] as $key => $label)
                    <button type="button" wire:click="setPeriod('{{ $key }}')"
                        class="px-3 py-1.5 {{ $period === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}"
                        @if($period === $key) style="background:#2563eb;color:#fff" @endif>{{ $label }}</button>
                @endforeach
            </div>

            @if($period === 'custom')
                <label class="text-xs text-gray-600">From
                    <input type="date" wire:model.live="fromDate" class="mt-1 block px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                </label>
                <label class="text-xs text-gray-600">To
                    <input type="date" wire:model.live="toDate" class="mt-1 block px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                </label>
            @endif

            <label class="text-xs text-gray-600">Technician
                <select wire:model.live="reportTechnician" class="mt-1 block px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <option value="">All technicians</option>
                    @foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </select>
            </label>

            <label class="text-xs text-gray-600">Break down by
                <select wire:model.live="groupBy" class="mt-1 block px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                </select>
            </label>
        </div>

        @if($reportError)
            <p class="text-sm text-red-600">{{ $reportError }}</p>
        @elseif($report)
            <p class="text-xs text-gray-500">
                {{ $report['from']->format('d M Y') }} to {{ $report['to']->format('d M Y') }}
                · times in Harare time · "held now" and "installed now" are today's position, the other columns count activity in the period
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach([
                    ['Devices held now',      $report['totals']['held'],           'text-gray-800'],
                    ['Devices installed now', $report['totals']['installed'],      'text-gray-800'],
                    ['Installs in period',    $report['totals']['total_installs'], 'text-green-700'],
                    ['Received in period',    $report['totals']['received'],       'text-blue-700'],
                ] as [$label, $value, $cls])
                    <div class="rounded-xl border border-gray-200 px-4 py-3">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="text-2xl font-bold {{ $cls }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase">
                        <tr>
                            <th class="text-left px-3 py-2">Technician</th>
                            <th class="text-center px-3 py-2">Held now</th>
                            <th class="text-center px-3 py-2">Installed now</th>
                            <th class="text-center px-3 py-2">Received</th>
                            <th class="text-center px-3 py-2">Returned</th>
                            <th class="text-center px-3 py-2">New installs</th>
                            <th class="text-center px-3 py-2">Repair/swaps</th>
                            <th class="text-center px-3 py-2">Total installs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($report['rows'] as $r)
                            <tr class="{{ $r['active'] ? '' : 'text-gray-400' }}">
                                <td class="px-3 py-2 font-medium">{{ $r['name'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['held'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['installed'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['received'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['returned'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['installs'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $r['repairs'] }}</td>
                                <td class="px-3 py-2 text-center font-semibold">{{ $r['total_installs'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-3 py-6 text-center text-gray-400">No activity in this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($report['rows']->isNotEmpty())
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['held'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['installed'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['received'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['returned'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['installs'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['repairs'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $report['totals']['total_installs'] }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="overflow-x-auto">
                <p class="text-xs font-semibold text-gray-700 mb-1">Breakdown by {{ $groupBy }}</p>
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase">
                        <tr>
                            <th class="text-left px-3 py-2">{{ ucfirst($groupBy) }}</th>
                            <th class="text-center px-3 py-2">Received</th>
                            <th class="text-center px-3 py-2">Returned</th>
                            <th class="text-center px-3 py-2">New installs</th>
                            <th class="text-center px-3 py-2">Repair/swaps</th>
                            <th class="text-center px-3 py-2">Total installs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($report['buckets'] as $b)
                            <tr>
                                <td class="px-3 py-2">{{ $b['label'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $b['received'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $b['returned'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $b['installs'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $b['repairs'] }}</td>
                                <td class="px-3 py-2 text-center font-semibold">{{ $b['total_installs'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">Nothing recorded in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
