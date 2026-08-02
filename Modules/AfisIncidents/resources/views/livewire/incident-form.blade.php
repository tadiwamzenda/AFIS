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
                <p class="text-sm text-gray-500 mb-4">An AI analysis has been queued. The formatted report (.docx) will be available in the incident archive once the queue worker processes it.</p>
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
                    placeholder="Describe what happened in as much detail as possible."></textarea>
                <p class="mt-1 text-xs text-gray-400">Minimum 20 characters.</p>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ── Navixy PDF uploads ─────────────────────────────────────── --}}
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Navixy reports (optional, improves accuracy)</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Trip Report</label>
                        <input type="file" wire:model="tripReportPdf" accept="application/pdf"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                        <div wire:loading wire:target="tripReportPdf" class="text-xs text-gray-400 mt-1">Uploading...</div>
                        @error('tripReportPdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Speed Violation Report</label>
                        <input type="file" wire:model="speedReportPdf" accept="application/pdf"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                        <div wire:loading wire:target="speedReportPdf" class="text-xs text-gray-400 mt-1">Uploading...</div>
                        @error('speedReportPdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Report on All Events</label>
                        <input type="file" wire:model="eventsReportPdf" accept="application/pdf"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                        <div wire:loading wire:target="eventsReportPdf" class="text-xs text-gray-400 mt-1">Uploading...</div>
                        @error('eventsReportPdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ── Prepared By ─────────────────────────────────────── --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prepared by <span class="text-red-500">*</span></label>
                    <input wire:model="preparedByName" type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('preparedByName') border-red-400 @enderror">
                    @error('preparedByName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input wire:model="preparedByTitle" type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 @error('preparedByTitle') border-red-400 @enderror">
                    @error('preparedByTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-700">
                <strong>What happens next:</strong> The incident is logged and a short, formatted AI analysis (.docx) is generated automatically, using the uploaded Navixy reports where provided and AFIS GPS trip data as a fallback.
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="submit" wire:loading.attr="disabled" wire:target="submit,tripReportPdf,speedReportPdf,eventsReportPdf"
                    class="bg-brand-500 hover:bg-brand-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Log incident & generate AI report</span>
                    <span wire:loading wire:target="submit">Processing...</span>
                </button>
            </div>

        </div>
    </div>
    @endif

</div>