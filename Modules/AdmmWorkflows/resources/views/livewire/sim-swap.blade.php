<div class="max-w-2xl">

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-6">
        @foreach(['Select device', 'Choose new SIM', 'Confirm', 'Done'] as $i => $label)
            <div class="flex items-center gap-2 {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-px {{ $step > $i ? 'bg-brand-500' : 'bg-gray-200' }}"></div>@endif
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium
                        {{ $step > $i + 1 ? 'bg-brand-500 text-white' : ($step === $i + 1 ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                        {{ $i + 1 }}
                    </div>
                    <span class="text-xs {{ $step === $i + 1 ? 'text-brand-600 font-medium' : 'text-gray-400' }} hidden sm:block">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if($error)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    {{-- Step 1: Select device --}}
    @if($step === 1)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Select the GPS device to swap SIM on</h3>
        <select wire:model="deviceId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">Select device...</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}">
                    {{ $device->serial_number }} — {{ $device->model }}
                    {{ $device->client ? '· ' . $device->client->name : '· Internal' }}
                    {{ $device->simCard ? '· SIM: ' . $device->simCard->msisdn : '· No SIM' }}
                </option>
            @endforeach
        </select>
        <div class="mt-4">
            <button wire:click="selectDevice" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                Next →
            </button>
        </div>
    </div>
    @endif

    {{-- Step 2: Choose new SIM --}}
    @if($step === 2)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Choose replacement SIM card</h3>

        {{-- Current SIM info --}}
        <div class="bg-gray-50 rounded-lg px-4 py-3">
            <p class="text-xs text-gray-500 mb-1">Current SIM on {{ $selectedDevice?->serial_number }}</p>
            @if($selectedDevice?->simCard)
                <p class="text-sm font-medium text-gray-800">{{ $selectedDevice->simCard->msisdn }}</p>
                <p class="text-xs text-gray-500">{{ $selectedDevice->simCard->network_provider }} · {{ $selectedDevice->simCard->status_label }}</p>
            @else
                <p class="text-sm text-gray-500">No SIM currently installed</p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New SIM card <span class="text-red-500">*</span></label>
            <select wire:model="newSimId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Select SIM card...</option>
                @foreach($availableSims as $sim)
                    <option value="{{ $sim->id }}">{{ $sim->msisdn }} — {{ $sim->network_provider }} {{ $sim->batch_code ? '· ' . $sim->batch_code : '' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for swap <span class="text-red-500">*</span></label>
            <textarea wire:model="reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. SIM faulty, bundle expired..."></textarea>
        </div>

        <div class="flex gap-3">
            <button wire:click="$set('step', 1)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="confirm" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">Next →</button>
        </div>
    </div>
    @endif

    {{-- Step 3: Confirm --}}
    @if($step === 3)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Confirm SIM swap</h3>

        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Device</span>
                <span class="font-medium text-gray-800">{{ $selectedDevice?->serial_number }} — {{ $selectedDevice?->model }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Removing SIM</span>
                <span class="font-medium text-red-600">{{ $selectedDevice?->simCard?->msisdn ?? 'None' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Installing SIM</span>
                <span class="font-medium text-green-600">{{ $selectedSim?->msisdn }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500">Reason</span>
                <span class="font-medium text-gray-800">{{ $reason }}</span>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-700">
            This action will be permanently recorded in the audit log and cannot be undone.
        </div>

        <div class="flex gap-3">
            <button wire:click="$set('step', 2)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="execute" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                Confirm swap
            </button>
        </div>
    </div>
    @endif

    {{-- Step 4: Done --}}
    @if($step === 4)
    <div class="bg-white rounded-xl border border-green-200 p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800 mb-1">SIM swap completed</h3>
        <p class="text-xs text-gray-500 mb-4">The swap has been recorded in the audit log.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.admm.workflows.hub') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">← Back to workflows</a>
            <a href="{{ route('admin.admm.sim-cards.index') }}" class="text-sm text-gray-500 hover:text-gray-700">View SIM cards</a>
        </div>
    </div>
    @endif

</div>