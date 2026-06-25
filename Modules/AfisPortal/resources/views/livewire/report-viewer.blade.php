<div class="space-y-4">

    {{-- Filter --}}
    <div class="flex gap-3 items-center">
        <select wire:model.live="typeFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">All report types</option>
            <option value="vehicle_behaviour">Vehicle Behaviour</option>
            <option value="fleet_intelligence">Fleet Intelligence</option>
            <option value="incident_analysis">Incident Analysis</option>
            <option value="predictive_intelligence">Predictive Intelligence</option>
        </select>
        <span class="text-xs text-gray-400">{{ $reports->total() }} reports</span>
    </div>

    @if($selectedReport)
    {{-- Full report view --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ ucwords(str_replace('_', ' ', $selectedReport->report_type)) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $selectedReport->engine_used }} · {{ $selectedReport->tokens_used }} tokens · {{ $selectedReport->created_at->format('d M Y H:i') }}</p>
            </div>
            <button wire:click="clearReport" class="text-xs text-gray-400 hover:text-gray-600">← Back to list</button>
        </div>
        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ $selectedReport->response }}</div>
    </div>
    @else
    {{-- Report list --}}
    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50">
        @forelse($reports as $report)
        <div class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
            wire:click="selectReport({{ $report->id }})">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $report->engine_used }} ·
                    {{ $report->tokens_used }} tokens ·
                    {{ $report->created_at->format('d M Y H:i') }}
                    @if($report->tracker_id)
                        · Vehicle report
                    @else
                        · Fleet report
                    @endif
                </p>
            </div>
            <span class="text-xs text-brand-600 font-medium">Read →</span>
        </div>
        @empty
        <p class="px-5 py-8 text-sm text-gray-400 text-center">No reports generated yet.</p>
        @endforelse
    </div>
    @if($reports->hasPages())
        <div class="mt-4">{{ $reports->links() }}</div>
    @endif
    @endif

</div>