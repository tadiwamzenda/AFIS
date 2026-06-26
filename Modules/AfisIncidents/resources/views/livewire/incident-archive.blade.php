<div>
    @if($selected)
    {{-- Full incident report view --}}
    <div class="space-y-4">
        <button wire:click="clearSelected" class="text-sm text-gray-500 hover:text-gray-700">← Back to archive</button>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">{{ $selected->vehicle_label }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $selected->incident_date->format('d M Y H:i') }} ·
                        Logged by {{ $selected->loggedBy?->name ?? 'Unknown' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($selected->severity)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $selected->severity_color }}">
                            {{ ucfirst($selected->severity) }}
                        </span>
                    @endif
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $selected->status === 'completed' ? 'bg-green-100 text-green-700' :
                           ($selected->status === 'analysing' ? 'bg-blue-100 text-blue-700' :
                           ($selected->status === 'failed'    ? 'bg-red-100 text-red-700' :
                                                               'bg-gray-100 text-gray-600')) }}">
                        {{ ucfirst($selected->status) }}
                    </span>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg px-4 py-3 mb-4">
                <p class="text-xs font-medium text-gray-500 mb-1">Incident description</p>
                <p class="text-sm text-gray-700">{{ $selected->description }}</p>
            </div>

            @if($selected->status === 'analysing')
                <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-4 text-sm text-blue-700">
                    <p class="font-medium mb-1">AI analysis in progress</p>
                    <p class="text-xs">The AI report is being generated. Refresh this page in 30-60 seconds to see the full report.</p>
                </div>
            @elseif($selected->status === 'failed')
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    AI analysis failed. Please try regenerating the report or contact support.
                </div>
            @elseif($selected->aiReport)
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-3">
                        AI Analysis · {{ $selected->aiReport->engine_used }} ·
                        {{ $selected->aiReport->tokens_used }} tokens ·
                        {{ $selected->aiReport->created_at->format('d M Y H:i') }}
                    </p>
                    <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ $selected->aiReport->response }}</div>
                </div>
            @else
                <p class="text-sm text-gray-400">No AI report available yet.</p>
            @endif
        </div>
    </div>

    @else
    {{-- Archive list --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search vehicle, description..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <select wire:model.live="severityFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All severities</option>
                <option value="minor">Minor</option>
                <option value="moderate">Moderate</option>
                <option value="serious">Serious</option>
                <option value="critical">Critical</option>
            </select>
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All statuses</option>
                <option value="logged">Logged</option>
                <option value="analysing">Analysing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50">
            @forelse($incidents as $incident)
            <div class="px-5 py-4 hover:bg-gray-50 cursor-pointer transition-colors"
                wire:click="selectIncident({{ $incident->id }})">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-semibold text-gray-800">{{ $incident->vehicle_label }}</p>
                            @if($incident->severity)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $incident->severity_color }}">
                                    {{ ucfirst($incident->severity) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mb-1">
                            {{ $incident->incident_date->format('d M Y H:i') }} ·
                            Logged by {{ $incident->loggedBy?->name ?? 'Unknown' }}
                        </p>
                        <p class="text-sm text-gray-600 truncate">{{ Str::limit($incident->description, 100) }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $incident->status === 'completed' ? 'bg-green-100 text-green-700' :
                               ($incident->status === 'analysing' ? 'bg-blue-100 text-blue-700' :
                               ($incident->status === 'failed'    ? 'bg-red-100 text-red-700' :
                                                                   'bg-gray-100 text-gray-600')) }}">
                            {{ ucfirst($incident->status) }}
                        </span>
                        <p class="text-xs text-brand-600 mt-1">View report →</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="px-5 py-10 text-sm text-gray-400 text-center">No incidents logged yet.</p>
            @endforelse
        </div>

        @if($incidents->hasPages())
            <div>{{ $incidents->links() }}</div>
        @endif
    </div>
    @endif

</div>