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
                        @if($selected->aiReport)
                            · Generated {{ $selected->aiReport->created_at->diffForHumans() }}
                        @endif
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
                    @if($selected->report_path)
                        <a href="{{ route('admin.afis.incidents.download', $selected->id) }}"
                            class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                            ⬇ Download .docx
                        </a>
                    @endif
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

        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Incident reports</h3>
                <span class="text-xs text-gray-400">{{ $incidents->total() }} reports</span>
            </div>
            <div class="divide-y divide-gray-50">
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
                            @if($incident->aiReport)
                                · Generated {{ $incident->aiReport->created_at->diffForHumans() }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-600 truncate">{{ Str::limit($incident->description, 100) }}</p>
                    </div>
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $incident->status === 'completed' ? 'bg-green-100 text-green-700' :
                               ($incident->status === 'analysing' ? 'bg-blue-100 text-blue-700' :
                               ($incident->status === 'failed'    ? 'bg-red-100 text-red-700' :
                                                                   'bg-gray-100 text-gray-600')) }}">
                            {{ ucfirst($incident->status) }}
                        </span>
                        @if($incident->report_path)
                        <a href="{{ route('admin.afis.incidents.download', $incident->id) }}"
                            onclick="event.stopPropagation()"
                            class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download
                        </a>
                        @endif
                        <button wire:click="deleteIncident({{ $incident->id }})"
                            wire:confirm="Delete this incident? This cannot be undone."
                            onclick="event.stopPropagation()"
                            class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <p class="px-5 py-10 text-sm text-gray-400 text-center">No incidents logged yet.</p>
            @endforelse
            </div>
        </div>

        @if($incidents->hasPages())
            <div>{{ $incidents->links() }}</div>
        @endif
    </div>
    @endif

</div>