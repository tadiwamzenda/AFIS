<div class="max-w-2xl">

    <div class="flex items-center gap-2 mb-6">
        @foreach(['Select device', 'Removal details', 'Confirm', 'Done'] as $i => $label)
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
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Select device to remove from vehicle</h3>
        @if($devices->isEmpty())
            <p class="text-sm text-gray-400">No installed devices found.</p>
        @else
        <select wire:model="deviceId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">Select installed device...</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}">
                    {{ $device->serial_number }} — {{ $device->vehicle_registration ?? 'unknown vehicle' }}
                    {{ $device->client ? '· ' . $device->client->name : '' }}
                </option>
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
        <h3 class="text-sm font-semibold text-gray-800">Removal details</h3>

        <div class="bg-gray-50 rounded-lg px-4 py-3 text-sm">
            <p class="font-medium text-gray-800">{{ $selectedDevice?->serial_number }} — {{ $selectedDevice?->model }}</p>
            <p class="text-gray-500 text-xs mt-0.5">
                {{ $selectedDevice?->client?->name }} ·
                {{ $selectedDevice?->vehicle_registration }}
                @if($selectedDevice?->simCard)
                    · SIM: {{ $selectedDevice->simCard->msisdn }}
                @endif
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for removal <span class="text-red-500">*</span></label>
            <textarea wire:model="reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. Client contract ended, device fault..."></textarea>
        </div>

        @if($selectedDevice?->simCard)
        <div class="border border-gray-200 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 mb-2">SIM card handling</p>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="detachSim" value="0" class="text-brand-500">
                    <span class="text-sm text-gray-700">Keep SIM attached to device (move to internal stock together)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="detachSim" value="1" class="text-brand-500">
                    <span class="text-sm text-gray-700">Detach SIM — return separately to unassigned stock</span>
                </label>
            </div>
        </div>
        @endif

        <div class="flex gap-3">
            <button wire:click="$set('step', 1)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="confirm" class="bg-red-500 hover:bg-red-600 text-white font-medium px-5 py-2 rounded-lg text-sm">Next →</button>
        </div>
    </div>
    @endif

    @if($step === 3)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-800">Confirm removal</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Device</span>
                <span class="font-medium">{{ $selectedDevice?->serial_number }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">From vehicle</span>
                <span class="font-medium">{{ $selectedDevice?->vehicle_registration }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Client</span>
                <span class="font-medium">{{ $selectedDevice?->client?->name }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">SIM handling</span>
                <span class="font-medium">{{ $detachSim ? 'Detach to unassigned' : 'Keep with device' }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500">Reason</span>
                <span class="font-medium">{{ $reason }}</span>
            </div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-xs text-red-700">
            This will move the device back to internal stock and record in the audit log.
        </div>
        <div class="flex gap-3">
            <button wire:click="$set('step', 2)" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">← Back</button>
            <button wire:click="execute" class="bg-red-500 hover:bg-red-600 text-white font-medium px-5 py-2 rounded-lg text-sm">Confirm removal</button>
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
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Device removed successfully</h3>
        <p class="text-xs text-gray-500 mb-4">Device moved to internal stock. Recorded in audit log.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.admm.workflows.hub') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">← Back to workflows</a>
            <a href="{{ route('admin.admm.gps-devices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">View devices</a>
        </div>
    </div>
    @endif

</div>