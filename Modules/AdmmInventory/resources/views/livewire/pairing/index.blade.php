<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- ═══ Pairing wizard ═══ --}}
    <div class="xl:col-span-3 space-y-4">

        @if($message)
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @endif
        @if($error)
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $error }}</div>
        @endif

        {{-- Step 1: device --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">1. Device (IMEI)</p>

            @if($device)
                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                    <div>
                        <p class="font-mono text-sm font-semibold text-gray-900">{{ $device->imei }}</p>
                        <p class="text-xs text-gray-500">{{ $device->device_type ?: '—' }}{{ $device->model && $device->model !== $device->device_type ? ' · ' . $device->model : '' }}</p>
                    </div>
                    <button wire:click="clearDevice" class="text-xs text-gray-500 hover:underline">Change</button>
                </div>
            @else
                <input type="text" wire:model.live.debounce.250ms="deviceSearch" inputmode="numeric" autofocus
                       placeholder="Type part of the IMEI, e.g. 567"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-1 focus:ring-brand-500">
                @php $dDigits = preg_replace('/\D+/', '', $deviceSearch); @endphp
                @if($deviceSearch !== '' && strlen($dDigits) < $minDigits)
                    <p class="text-xs text-gray-400 mt-2">Type at least {{ $minDigits }} digits.</p>
                @elseif($deviceSearch !== '')
                    <div class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100">
                        @forelse($deviceResults->take(10) as $d)
                            <button wire:key="dev-{{ $d->id }}" wire:click="selectDevice({{ $d->id }})"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center justify-between">
                                <span class="font-mono text-sm">{!! $this->highlight($d->imei, $dDigits) !!}</span>
                                <span class="text-xs text-gray-500">{{ $d->device_type ?: '—' }}</span>
                            </button>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-400">No unpaired device in office stock contains "{{ $dDigits }}".</p>
                        @endforelse
                    </div>
                    @if($deviceResults->count() > 10)
                        <p class="text-xs text-gray-400 mt-1">More matches exist. Type more digits to narrow the list.</p>
                    @endif
                @endif
            @endif
        </div>

        {{-- Step 2: SIM --}}
        @if($device)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">2. SIM card (ICCID or phone number)</p>

            @if($sim)
                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                    <div>
                        <p class="font-mono text-sm font-semibold text-gray-900">{{ $sim->iccid }}</p>
                        <p class="text-xs text-gray-500">{{ $sim->msisdn ?: 'No phone number' }} · {{ $sim->network_provider }}</p>
                    </div>
                    <button wire:click="clearSim" class="text-xs text-gray-500 hover:underline">Change</button>
                </div>
            @else
                <input type="text" wire:model.live.debounce.250ms="simSearch" inputmode="numeric"
                       placeholder="Type part of the ICCID or the phone number"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-1 focus:ring-brand-500">
                @php
                    $sDigits = preg_replace('/\D+/', '', $simSearch);
                    $pDigits = str_starts_with($sDigits, '0') ? substr($sDigits, 1) : $sDigits;
                @endphp
                @if($simSearch !== '' && strlen($sDigits) < $minDigits)
                    <p class="text-xs text-gray-400 mt-2">Type at least {{ $minDigits }} digits.</p>
                @elseif($simSearch !== '')
                    <div class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100">
                        @forelse($simResults->take(10) as $s)
                            <button wire:key="sim-{{ $s->id }}" wire:click="selectSim({{ $s->id }})"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center justify-between gap-4">
                                <span class="font-mono text-sm">{!! $this->highlight($s->iccid, $sDigits) !!}</span>
                                <span class="font-mono text-xs text-gray-500">{!! $this->highlight((string) $s->msisdn, $pDigits) !!} · {{ $s->network_provider }}</span>
                            </button>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-400">No free SIM card matches "{{ $sDigits }}".</p>
                        @endforelse
                    </div>
                    @if($simResults->count() > 10)
                        <p class="text-xs text-gray-400 mt-1">More matches exist. Type more digits to narrow the list.</p>
                    @endif
                @endif
            @endif
        </div>
        @endif

        {{-- Step 3: technician --}}
        @if($device && $sim)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">3. Technician</p>

            @if($tech)
                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">{{ $tech->name }}</p>
                    <button wire:click="clearTechnician" class="text-xs text-gray-500 hover:underline">Change</button>
                </div>
            @else
                <input type="text" wire:model.live.debounce.250ms="techSearch" placeholder="Search technicians by name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                <div class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-64 overflow-auto">
                    @forelse($technicians as $t)
                        <button wire:key="tech-{{ $t->id }}" wire:click="selectTechnician({{ $t->id }})"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center justify-between">
                            <span class="text-sm">{{ $t->name }}</span>
                            <span class="text-xs text-gray-400">{{ $t->held_count }} unit{{ $t->held_count === 1 ? '' : 's' }} held</span>
                        </button>
                    @empty
                        <p class="px-4 py-3 text-sm text-gray-400">No active technician found. Add one under Technicians.</p>
                    @endforelse
                </div>
            @endif
        </div>
        @endif

        {{-- Confirm --}}
        @if($device && $sim && $tech)
        <div class="bg-brand-50 border border-brand-200 rounded-xl p-5">
            <p class="text-sm text-gray-800">
                Pair device <span class="font-mono font-semibold">{{ $device->imei }}</span>
                with SIM <span class="font-mono font-semibold">{{ $sim->iccid }}</span>
                and hand it to <span class="font-semibold">{{ $tech->name }}</span>.
            </p>
            <button wire:click="confirm" wire:loading.attr="disabled"
                    class="mt-3 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-5 py-2 rounded-lg disabled:opacity-50">
                <span wire:loading.remove wire:target="confirm">Pair and assign</span>
                <span wire:loading wire:target="confirm">Saving...</span>
            </button>
            <p class="text-xs text-gray-500 mt-2">The technician stays selected afterwards, so you can pair the next unit straight away.</p>
        </div>
        @endif
    </div>

    {{-- ═══ Recent pairings ═══ --}}
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-800">With technicians, not yet installed</p>
                <p class="text-xs text-gray-400">Latest 15. Full lists per technician come with Technician Stock.</p>
            </div>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100">
                    @forelse($recent as $r)
                    <tr wire:key="recent-{{ $r->id }}">
                        <td class="px-4 py-2">
                            <p class="font-mono text-gray-900">{{ $r->imei }}</p>
                            <p class="font-mono text-gray-400">{{ $r->simCard?->iccid ?? 'no SIM' }}</p>
                        </td>
                        <td class="px-2 py-2 text-gray-600">{{ $r->assignedTechnician?->name ?? ($r->technician ?: '—') }}</td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="unpair({{ $r->id }})"
                                    wire:confirm="Undo this pairing? The device and SIM go back to office stock."
                                    class="text-red-600 hover:underline">Undo</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td class="px-4 py-8 text-center text-gray-400">Nothing paired yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
