<div class="space-y-4 max-w-5xl pb-32">

    @if($message)
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
    @endif
    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $error }}</div>
    @endif

    {{-- 1. Device --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">1. Unit being installed</h3>
        <p class="text-xs text-gray-500">Only paired devices held by a technician are listed. Pair and assign a unit first if it is missing.</p>
        <div class="grid sm:grid-cols-3 gap-3">
            <select wire:model.live="techFilter" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All technicians</option>
                @foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
            <input type="text" wire:model.live.debounce.300ms="deviceSearch" placeholder="Search IMEI…" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <select wire:model.live="deviceId" class="px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">— choose a unit ({{ $devices->count() }}) —</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}">{{ $d->imei }} · {{ $d->assignedTechnician?->name ?? ($d->technician ?: 'no custodian') }}</option>
                @endforeach
            </select>
        </div>
        @if($selected)
            <div class="text-xs text-gray-600 bg-gray-50 rounded-lg px-3 py-2">
                {{ $selected->device_type }} {{ $selected->model }} · SIM {{ $selected->simCard?->iccid }} ({{ $selected->simCard?->msisdn ?: 'no number' }}, {{ $selected->simCard?->network_provider }})
            </div>
        @endif
    </div>

    {{-- 2. Vehicle --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">2. Vehicle and client</h3>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="text-xs text-gray-600">Installation date
                <input type="date" wire:model="installedAt" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </label>
            <label class="text-xs text-gray-600">Client
                <select wire:model.live="clientId" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">— choose —</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs text-gray-600">Registration number
                <input type="text" wire:model.live.debounce.400ms="registration" placeholder="e.g. AEJ 1234" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase">
            </label>
            <label class="text-xs text-gray-600">Fleet number
                <input type="text" wire:model="fleetNumber" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </label>
            <label class="text-xs text-gray-600">Make &amp; model
                <input type="text" wire:model="make" list="make-options" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <datalist id="make-options">@foreach($makes as $m)<option value="{{ $m }}">@endforeach</datalist>
            </label>
            <label class="text-xs text-gray-600">Where in the vehicle
                <input type="text" wire:model="location" list="loc-options" placeholder="e.g. Under dashboard" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <datalist id="loc-options">@foreach($locations as $l)<option value="{{ $l }}">@endforeach</datalist>
            </label>
        </div>

        @if($suggestions->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-gray-500">This client usually has:</span>
                @foreach($suggestions as $s)
                    <button type="button" wire:click="useMake(@js($s))" class="px-2 py-1 rounded-full border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">{{ $s }}</button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 3. Type + duplicate check --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">3. Type of installation</h3>
        <div class="flex gap-6 text-sm">
            <label class="flex items-center gap-2"><input type="radio" wire:model.live="type" value="new"> New installation</label>
            <label class="flex items-center gap-2"><input type="radio" wire:model.live="type" value="repair_swap"> Repair / swap of an existing unit</label>
        </div>

        @if($conflicts->isNotEmpty())
            <div class="border border-amber-300 bg-amber-50 rounded-lg p-3 text-sm space-y-2">
                <p class="font-medium text-amber-900">{{ strtoupper($registration) }} already has {{ $conflicts->count() === 1 ? 'an active device' : $conflicts->count() . ' active devices' }}:</p>
                <ul class="text-xs text-amber-900 space-y-1">
                    @foreach($conflicts as $c)
                        <li>IMEI {{ $c->imei }} · {{ $c->client?->name ?? '—' }} · installed {{ $c->installed_at?->format('d M Y') ?? '—' }} · SIM {{ $c->simCard?->iccid ?? '—' }}</li>
                    @endforeach
                </ul>

                @if($type === 'new')
                    <button type="button" wire:click="switchToRepair" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-amber-600 text-white hover:bg-amber-700">Switch to Repair / Swap</button>
                    @if($isAdmin)
                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model.live="override"> Admin override: install anyway as a separate unit</label>
                        @if($override)
                            <input type="text" wire:model="overrideReason" placeholder="Reason for the override (required)" class="w-full px-3 py-2 border border-amber-300 rounded-lg text-sm">
                        @endif
                    @else
                        <p class="text-xs text-amber-800">Only an administrator can override this check.</p>
                    @endif
                @endif
            </div>
        @elseif(trim($registration) !== '')
            <p class="text-xs text-green-700">No active device on this registration.</p>
        @endif

        @if($type === 'repair_swap')
            <div class="border border-gray-200 rounded-lg p-3 space-y-3">
                <p class="text-xs font-semibold text-gray-700">Device being replaced</p>
                @if($conflicts->isNotEmpty())
                    <select wire:model.live="replacedDeviceId" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— choose the unit removed —</option>
                        @foreach($conflicts as $c)<option value="{{ $c->id }}">{{ $c->imei }}</option>@endforeach
                    </select>
                    <div class="grid sm:grid-cols-2 gap-3 text-xs text-gray-600">
                        <label>Removed device is
                            <select wire:model="replacedDeviceOutcome" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="faulty">Faulty</option><option value="stock">Back to stock</option>
                            </select>
                        </label>
                        <label>Removed SIM is
                            <select wire:model="replacedSimOutcome" class="mt-1 w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="stock">Back to stock</option><option value="faulty">Faulty</option>
                            </select>
                        </label>
                    </div>
                @else
                    <p class="text-xs text-gray-500">No previous device is recorded on this registration. It will be logged as a repair/swap with no old unit.</p>
                @endif
            </div>
        @endif
    </div>

        {{-- 4. Installer + save --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">4. Installer</h3>
        <div class="flex flex-wrap items-center gap-3">
            <select wire:model="installerId" class="w-full sm:w-72 px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">— same as custodian —</option>
                @foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
           
        </div>
         <button wire:click="save" wire:loading.attr="disabled" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                Log installation
            </button>
    </div>
</div>
