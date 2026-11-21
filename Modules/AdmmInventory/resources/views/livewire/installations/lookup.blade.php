@php
    $fmt   = fn ($c, $f = 'd M Y H:i') => $c ? $c->timezone('Africa/Harare')->format($f) : '—';
    $arrow = fn ($col) => $sortBy === $col ? ($sortDir === 'asc' ? '▲' : '▼') : '↕';
    $sortTh = fn ($col, $label) => '<th class="text-left px-3 py-2"><button type="button" wire:click="sort(\'' . $col . '\')" class="uppercase inline-flex items-center gap-1 hover:text-gray-800 ' . ($sortBy === $col ? 'text-gray-900 font-semibold' : '') . '">' . $label . ' <span class="' . ($sortBy === $col ? 'text-blue-600' : 'text-gray-300') . '">' . $arrow($col) . '</span></button></th>';
@endphp
<div class="space-y-4">

    @if($message)<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ $message }}</div>@endif
    @if($error)<div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $error }}</div>@endif

    <div class="flex flex-wrap items-end gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search IMEI, registration, fleet no, client, ICCID, phone…" class="flex-1 min-w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <select wire:model.live="clientFilter" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All clients</option>
            @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        @include('admminventory::partials.period-filter', ['label' => 'Installation date'])
        <button type="button" wire:click="exportCsv" class="px-3 py-2 text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50">Export CSV</button>
    </div>

    <datalist id="lk-make">@foreach($makes as $m)<option value="{{ $m }}">@endforeach</datalist>
    <datalist id="lk-loc">@foreach($locations as $l)<option value="{{ $l }}">@endforeach</datalist>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 text-gray-500 uppercase">
                <tr>
                    <th class="text-left px-3 py-2">Registration</th>
                    <th class="text-left px-3 py-2">Fleet</th>
                    {!! $sortTh('client', 'Client') !!}
                    <th class="text-left px-3 py-2">Make / model</th>
                    {!! $sortTh('imei', 'IMEI') !!}
                    {!! $sortTh('sim', 'SIM') !!}
                    {!! $sortTh('installed_at', 'Installed') !!}
                    {!! $sortTh('installer', 'Installer') !!}
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($rows as $r)
                <tr wire:key="row-{{ $r->id }}" class="{{ $activeId === $r->id ? 'bg-blue-50' : '' }}">
                    <td class="px-3 py-2 font-medium">{{ $r->vehicle_registration ?: '—' }}</td>
                    <td class="px-3 py-2">{{ $r->fleet_number ?: '—' }}</td>
                    <td class="px-3 py-2">{{ $r->client?->name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $r->vehicle_make ?: '—' }}</td>
                    <td class="px-3 py-2 font-mono">{{ $r->imei }}</td>
                    <td class="px-3 py-2">{{ $r->simCard?->msisdn ?: $r->simCard?->iccid ?: '—' }}</td>
                    <td class="px-3 py-2">{{ $r->installed_at?->format('d M Y') ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $r->assignedTechnician?->name ?? ($r->technician ?: '—') }}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap space-x-2">
                        <button wire:click="open({{ $r->id }}, 'edit')" class="text-blue-600 hover:underline">Edit</button>
                        <button wire:click="open({{ $r->id }}, 'installer')" class="text-blue-600 hover:underline">Installer</button>
                        <button wire:click="open({{ $r->id }}, 'history')" class="text-blue-600 hover:underline">History</button>
                        <button wire:click="open({{ $r->id }}, 'uncouple')" class="text-red-600 hover:underline">Uncouple</button>
                    </td>
                </tr>

                @if($activeId === $r->id)
                <tr wire:key="panel-{{ $r->id }}">
                    <td colspan="9" class="bg-gray-50 px-4 py-4">

                        @if($panel === 'edit')
                            <div class="grid sm:grid-cols-3 gap-3 max-w-4xl">
                                <label class="text-xs text-gray-600">Date<input type="date" wire:model="editDate" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></label>
                                <label class="text-xs text-gray-600">Client
                                    <select wire:model="editClient" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="text-xs text-gray-600">Registration<input type="text" wire:model="editReg" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase"></label>
                                <label class="text-xs text-gray-600">Fleet number<input type="text" wire:model="editFleet" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></label>
                                <label class="text-xs text-gray-600">Make &amp; model<input type="text" wire:model="editMake" list="lk-make" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></label>
                                <label class="text-xs text-gray-600">Location<input type="text" wire:model="editLocation" list="lk-loc" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></label>
                            </div>
                            <div class="mt-3 space-x-2">
                                <button wire:click="saveEdit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Save changes</button>
                                <button wire:click="closePanel" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancel</button>
                            </div>

                        @elseif($panel === 'installer')
                            <div class="flex items-end gap-3">
                                <label class="text-xs text-gray-600">New installer
                                    <select wire:model="newInstaller" class="mt-1 w-64 px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="">— choose —</option>
                                        @foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                                    </select>
                                </label>
                                <button wire:click="saveInstaller" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Update installer</button>
                                <button wire:click="closePanel" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancel</button>
                            </div>

                        @elseif($panel === 'uncouple')
                            <p class="text-sm text-gray-700 mb-3">Remove <b>{{ $r->imei }}</b> from <b>{{ $r->vehicle_registration }}</b>. The device and SIM are unlinked and the vehicle is released.</p>
                            <div class="grid sm:grid-cols-3 gap-3 max-w-3xl">
                                <label class="text-xs text-gray-600">Device is
                                    <select wire:model="deviceOutcome" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="faulty">Faulty</option><option value="stock">Back to office stock</option>
                                    </select>
                                </label>
                                <label class="text-xs text-gray-600">SIM is
                                    <select wire:model="simOutcome" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="stock">Back to stock</option><option value="faulty">Faulty</option>
                                    </select>
                                </label>
                                <label class="text-xs text-gray-600">Reason
                                    <input type="text" wire:model="reason" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </label>
                            </div>
                            <div class="mt-3 space-x-2">
                                <button wire:click="uncouple" wire:confirm="Uncouple this device from the vehicle?" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Uncouple</button>
                                <button wire:click="closePanel" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancel</button>
                            </div>

                        @elseif($panel === 'history')
                            <p class="text-sm font-semibold text-gray-800 mb-2">History of IMEI {{ $r->imei }}</p>
                            <table class="w-full text-xs">
                                <thead class="text-gray-500 uppercase"><tr>
                                    <th class="text-left py-1 pr-3">When</th><th class="text-left pr-3">Event</th><th class="text-left pr-3">Vehicle</th>
                                    <th class="text-left pr-3">Client</th><th class="text-left pr-3">Technician</th><th class="text-left pr-3">By</th><th class="text-left">Notes</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                @forelse($history as $e)
                                    <tr>
                                        <td class="py-1 pr-3 whitespace-nowrap">{{ $fmt($e->created_at) }}</td>
                                        <td class="pr-3">{{ str_replace('_', ' ', $e->event_type) }}</td>
                                        <td class="pr-3">{{ $e->vehicle_registration ?: '—' }}</td>
                                        <td class="pr-3">{{ $clientNames[$e->client_id] ?? '—' }}</td>
                                        <td class="pr-3">{{ $e->technician?->name ?? '—' }}</td>
                                        <td class="pr-3">{{ $userNames[$e->performed_by] ?? '—' }}</td>
                                        <td>{{ $e->notes }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-2 text-gray-500">No events recorded.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                            <button wire:click="closePanel" class="mt-3 px-4 py-2 text-sm border border-gray-300 rounded-lg">Close</button>
                        @endif
                    </td>
                </tr>
                @endif
            @empty
                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No installed devices match.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links('admminventory::pagination.centered') }}
</div>
