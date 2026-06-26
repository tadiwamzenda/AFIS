<div class="max-w-2xl">

    @if($submitted)
    <div class="bg-white rounded-xl border border-green-200 p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Incident logged successfully</h3>
                <p class="text-sm text-gray-500 mb-4">An AI analysis has been queued. The report will be available in the incident archive within 60 seconds once the queue worker processes it.</p>
                <div class="flex gap-3">
                    <a href="{{ route('admin.afis.incidents.archive', ['clientId' => $clientId]) }}"
                        class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                        View incident archive →
                    </a>
                    <button wire:click="$set('submitted', false)" class="text-sm text-gray-500 hover:text-gray-700">
                        Log another incident
                    </button>
                </div>
            </div>
        </div>
    </div>

    @else

    @if($error)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-5">Log new incident</h3>

        <div class="space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle <span class="text-red-500">*</span></label>
                <select wire:model="trackerId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('trackerId') border-red-400 @enderror">
                    <option value="">Select vehicle...</option>
                    @foreach($trackers as $tracker)
                        <option value="{{ $tracker->id }}">{{ $tracker->label }}</option>
                    @endforeach
                </select>
                @error('trackerId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incident date <span class="text-red-500">*</span></label>
                    <input wire:model="incidentDate" type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('incidentDate') border-red-400 @enderror">
                    @error('incidentDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incident time <span class="text-red-500">*</span></label>
                    <input wire:model="incidentTime" type="time"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('incidentTime') border-red-400 @enderror">
                    @error('incidentTime') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Incident description <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="description" rows="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('description') border-red-400 @enderror"
                    placeholder="Describe what happened in as much detail as possible. Include road conditions, weather, speed, other vehicles involved, injuries, damage, and any other relevant context. The more detail you provide, the more accurate the AI analysis will be."></textarea>
                <p class="mt-1 text-xs text-gray-400">Minimum 20 characters. More detail = better AI analysis.</p>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-700">
                <strong>What happens next:</strong> The incident is logged and an AI analysis is automatically generated using GPS tracking data from the 24 hours surrounding the incident. The full 10-section report will be available in the archive within 60 seconds.
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="submit" wire:loading.attr="disabled"
                    class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Log incident & generate AI report</span>
                    <span wire:loading wire:target="submit">Processing...</span>
                </button>
            </div>

        </div>
    </div>
    @endif

</div>