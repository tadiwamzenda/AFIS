<div class="max-w-2xl">

    <div class="flex items-center gap-2 mb-6">
        @foreach(['Select device', 'Client & vehicle', 'Confirm', 'Done'] as $i => $label)
            <div class="flex items-center gap-2 {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-px {{ $step > $i ? 'bg-brand-500' : 'bg-gray-200' }}"></div>@endif
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium {{ $step === $i + 1 ? 'bg-brand-500 text-white' : ($step > $i + 1 ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500') }}">{{ $i + 1 }}</div>
                    <span class="text-xs {{ $step === $i + 1 ? 'text-brand-600 font-medium' : 'text-gray-400' }} hidden sm:block">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if($error)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    @if($step === 1)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Select device to install</h3>
        @if($devices->isEmpty())
            <p class="text-sm text-gray-400">No devices available in internal stock. Add devices first.</p>
        @else
        <select wire:model="deviceId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">Select device from internal stock...</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}">{{ $device->serial_number }} — {{ $device->model }}</option>
            @endforeach
        </select>
        <div class="mt-4">
            <button wire:click="selectDevice" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm">Next →</button>
        </div>
        @endif
    </div>
    @endif

    @if($step === 2)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Client, vehicle and SIM details</h3>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
            <select wire:model="clientId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Select client...</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle registration <span class="text-red-500">*</span></label>
            <input wire:model="vehicleReg" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. ABC 1234">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SIM card <span class="text-gray-400 font-normal">(optional)</span></label>
            <select wire:model="simId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">No SIM card</option>
                @foreach($availableSims as $sim)
                    <option value="{{ $sim->id }}">{{ $sim->msisdn }} — {{ $sim->network_provider }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason / notes <span class="text-red-500">*</span></label>
            <textarea wire:model="reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. New client onboarding..."></textarea>
        </div>

        <div class="flex gap-3">
            <button wire:click="$set('step', 1)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="confirm" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm">Next →</button>
        </div>
    </div>
    @endif

    @if($step === 3)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Confirm device installation</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Device</span>
                <span class="font-medium">{{ $selectedDevice?->serial_number }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Client</span>
                <span class="font-medium">{{ $selectedClient?->name }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Vehicle</span>
                <span class="font-medium">{{ $vehicleReg }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">SIM card</span>
                <span class="font-medium">{{ $selectedSim?->msisdn ?? 'None' }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500">Reason</span>
                <span class="font-medium">{{ $reason }}</span>
            </div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-700">
            This will move the device and SIM to client_assigned status and record in the audit log.
        </div>
        <div class="flex gap-3">
            <button wire:click="$set('step', 2)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="execute" class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm">Confirm installation</button>
        </div>
    </div>
    @endif

    @if($step === 4)
    <div class="bg-white rounded-xl border border-green-200 p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Device installed successfully</h3>
        <p class="text-xs text-gray-500 mb-4">Recorded in the audit log.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.admm.workflows.hub') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">← Back to workflows</a>
            <a href="{{ route('admin.admm.gps-devices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">View devices</a>
        </div>
    </div>
    @endif

</div>