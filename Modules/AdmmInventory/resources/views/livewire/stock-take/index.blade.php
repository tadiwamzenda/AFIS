@php
    $fmt = fn ($c, $f = 'd M Y H:i') => $c ? $c->timezone('Africa/Harare')->format($f) : '—';
    $verifiedCell = function ($v) use ($staleDays, $fmt) {
        if (!$v) return '<span class="text-amber-600">Never</span>';
        $cls = $v->lt(now()->subDays($staleDays)) ? 'text-red-600' : 'text-green-700';
        return '<span class="' . $cls . '">' . e($fmt($v, 'd M Y')) . '</span>';
    };
@endphp
<div class="space-y-4">

    @if($message)
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
    @endif
    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $error }}</div>
    @endif

    {{-- ═══════════ TECHNICIAN STOCK ═══════════ --}}
    @if($section === 'technicians')
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm">
                <span class="text-gray-500">Technicians holding stock</span> <b>{{ $groups->count() }}</b>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm">
                <span class="text-gray-500">Units not yet installed</span> <b>{{ $totalUnits }}</b>
            </div>
            <select wire:model.live="technicianFilter" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All technicians</option>
                @foreach($technicians as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
            <button wire:click="exportTechnicianCsv" class="ml-auto text-sm font-medium px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Export CSV for the stock take
            </button>
        </div>

        @forelse($groups as $g)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:key="grp-{{ $g['technician_id'] ?? 0 }}">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-800">{{ $g['name'] }}</p>
                    <span class="text-xs text-gray-500">{{ $g['units']->count() }} unit{{ $g['units']->count() === 1 ? '' : 's' }}</span>
                </div>
                <table class="w-full text-xs">
                    <thead class="text-gray-500 uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">IMEI</th>
                            <th class="text-left px-3 py-2">Device type</th>
                            <th class="text-left px-3 py-2">ICCID</th>
                            <th class="text-left px-3 py-2">Phone</th>
                            <th class="text-left px-3 py-2">Paired on</th>
                            <th class="text-left px-3 py-2">Last verified</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($g['units'] as $u)
                        <tr wire:key="unit-{{ $u['device']->id }}">
                            <td class="px-4 py-2 font-mono">{{ $u['device']->imei }}</td>
                            <td class="px-3 py-2">{{ $u['device']->device_type ?: '—' }}</td>
                            <td class="px-3 py-2 font-mono">{{ $u['sim']?->iccid ?? 'no SIM' }}</td>
                            <td class="px-3 py-2 font-mono">{{ $u['sim']?->msisdn ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $fmt($u['paired_at']) }}</td>
                            <td class="px-3 py-2">{!! $verifiedCell($u['device']->last_verified_at) !!}</td>
                            <td class="px-4 py-2 text-right">
                                <button wire:click="verifyDevice({{ $u['device']->id }})" class="text-brand-600 hover:underline font-medium">Verify</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-sm text-gray-400">
                No uninstalled stock is with technicians right now.
            </div>
        @endforelse
    @endif

    {{-- ═══════════ UNPAIRED SIMS ═══════════ --}}
    @if($section === 'sims')
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm"><span class="text-gray-500">Unpaired active SIMs</span> <b>{{ $total }}</b></div>
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm"><span class="text-gray-500">Not verified in {{ $staleDays }} days</span> <b class="text-amber-600">{{ $staleTotal }}</b></div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ICCID or phone number"
                   class="w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="onlyStale"> Only not recently verified
            </label>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">ICCID</th>
                        <th class="text-left px-3 py-2">Phone</th>
                        <th class="text-left px-3 py-2">Network</th>
                        <th class="text-left px-3 py-2">Status</th>
                        <th class="text-left px-3 py-2">Last verified</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $s)
                    <tr wire:key="sim-{{ $s->id }}">
                        <td class="px-4 py-2 font-mono">{{ $s->iccid }}</td>
                        <td class="px-3 py-2 font-mono">{{ $s->msisdn ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $s->network_provider }}</td>
                        <td class="px-3 py-2">{{ $s->status_label }}</td>
                        <td class="px-3 py-2">{!! $verifiedCell($s->last_verified_at) !!}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="verifySim({{ $s->id }})" class="text-brand-600 hover:underline font-medium">Verify</button>
                            <button wire:click="flagSimFaulty({{ $s->id }})" wire:confirm="Move this SIM card to Faulty?" class="text-amber-600 hover:underline ml-3">Faulty</button>
                            <button wire:click="flagSimLost({{ $s->id }})" wire:confirm="Flag this SIM card as LOST?" class="text-red-600 hover:underline ml-3">Lost</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No unpaired SIM cards match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->count() >= $limit)
            <p class="text-xs text-gray-400">Showing the first {{ $limit }}. Search or filter to narrow the list.</p>
        @endif
    @endif

    {{-- ═══════════ AVAILABLE DEVICES ═══════════ --}}
    @if($section === 'devices')
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm"><span class="text-gray-500">Available devices in office stock</span> <b>{{ $total }}</b></div>
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm"><span class="text-gray-500">Not verified in {{ $staleDays }} days</span> <b class="text-amber-600">{{ $staleTotal }}</b></div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search IMEI, type or model"
                   class="w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="onlyStale"> Only not recently verified
            </label>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">IMEI</th>
                        <th class="text-left px-3 py-2">Device type</th>
                        <th class="text-left px-3 py-2">Model</th>
                        <th class="text-left px-3 py-2">Last verified</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $d)
                    <tr wire:key="dev-{{ $d->id }}">
                        <td class="px-4 py-2 font-mono">{{ $d->imei }}</td>
                        <td class="px-3 py-2">{{ $d->device_type ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $d->model ?: '—' }}</td>
                        <td class="px-3 py-2">{!! $verifiedCell($d->last_verified_at) !!}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="verifyDevice({{ $d->id }})" class="text-brand-600 hover:underline font-medium">Verify</button>
                            <button wire:click="flagDeviceFaulty({{ $d->id }})" wire:confirm="Move this device to Faulty?" class="text-amber-600 hover:underline ml-3">Faulty</button>
                            <button wire:click="flagDeviceLost({{ $d->id }})" wire:confirm="Flag this device as LOST / STOLEN?" class="text-red-600 hover:underline ml-3">Lost</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No available devices match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->count() >= $limit)
            <p class="text-xs text-gray-400">Showing the first {{ $limit }}. Search or filter to narrow the list.</p>
        @endif
    @endif

    {{-- ═══════════ FAULTY ═══════════ --}}
    @if($section === 'faulty')
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between">
                    <p class="text-sm font-semibold text-gray-800">Faulty devices</p>
                    <span class="text-xs text-gray-500">{{ $devices->count() }}</span>
                </div>
                <table class="w-full text-xs">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($devices as $d)
                        <tr wire:key="fd-{{ $d->id }}">
                            <td class="px-4 py-2">
                                <p class="font-mono text-gray-900">{{ $d->imei }}</p>
                                <p class="text-gray-400">{{ $d->device_type ?: '—' }}{{ $d->simCard ? ' · SIM ' . $d->simCard->iccid : '' }}</p>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button wire:click="returnDevice({{ $d->id }})" wire:confirm="Return this device to office stock?" class="text-brand-600 hover:underline font-medium">Return to stock</button>
                                <button wire:click="writeOffDevice({{ $d->id }})" wire:confirm="Write this device off? It becomes Decommissioned." class="text-red-600 hover:underline ml-3">Write off</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="px-4 py-8 text-center text-gray-400">No faulty devices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between">
                    <p class="text-sm font-semibold text-gray-800">Faulty SIM cards</p>
                    <span class="text-xs text-gray-500">{{ $sims->count() }}</span>
                </div>
                <table class="w-full text-xs">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sims as $s)
                        <tr wire:key="fs-{{ $s->id }}">
                            <td class="px-4 py-2">
                                <p class="font-mono text-gray-900">{{ $s->iccid }}</p>
                                <p class="text-gray-400">{{ $s->msisdn ?: 'No phone number' }} · {{ $s->network_provider }}</p>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button wire:click="returnSim({{ $s->id }})" wire:confirm="Return this SIM card to stock?" class="text-brand-600 hover:underline font-medium">Return to stock</button>
                                <button wire:click="writeOffSim({{ $s->id }})" wire:confirm="Write this SIM card off? It becomes Deactivated." class="text-red-600 hover:underline ml-3">Write off</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="px-4 py-8 text-center text-gray-400">No faulty SIM cards.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-xs text-gray-400">Hardware removed from a vehicle because of a fault lands here when the Installations screens are added.</p>
    @endif
</div>
