<div>
    @if($selected)
    <div class="space-y-4">
        <button wire:click="clearSelected" class="text-sm text-gray-500 hover:text-gray-700">← Back to archive</button>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800">{{ ucwords(str_replace('_', ' ', $selected->report_type)) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $selected->engine_used }} ·
                    {{ $selected->tokens_used }} tokens ·
                    {{ $selected->created_at->format('d M Y H:i') }}
                </p>
            </div>
            <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ $selected->response }}</div>
        </div>
    </div>

    @else
    <div class="space-y-4">
        <div class="flex gap-3">
            <select wire:model.live="typeFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All intelligence reports</option>
                <option value="fleet_intelligence">Fleet Intelligence</option>
                <option value="predictive_intelligence">Predictive Intelligence</option>
            </select>
            <span class="text-xs text-gray-400 self-center">{{ $reports->total() }} reports</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50">
            @forelse($reports as $report)
            <div class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors"
                wire:click="selectReport({{ $report->id }})">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $report->engine_used }} ·
                        {{ $report->tokens_used }} tokens ·
                        {{ $report->created_at->format('d M Y H:i') }}
                    </p>
                </div>
                <span class="text-xs text-brand-600 font-medium">Read →</span>
            </div>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">
                No intelligence reports yet. Go to the dashboard and generate one.
            </p>
            @endforelse
        </div>

        @if($reports->hasPages())
            <div>{{ $reports->links() }}</div>
        @endif
    </div>
    @endif
</div>