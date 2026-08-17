<div class="space-y-4">

    @if($error)
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-5">Generate report</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            @if($isAdmin)
            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                <select wire:model.live="clientId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Select client...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

           {{-- Sub-group selection --}}
            @if($clientId && ($groups->isNotEmpty() || $parentGroups->isNotEmpty()))
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Sub-groups
                    @if($largeFleet)
                    <span class="text-xs text-gray-400 font-normal ml-1">(optional — leave blank for all)</span>
                    @else
                    <span class="text-xs text-gray-400 font-normal ml-1">(optional — leave blank for all)</span>
                    @endif
                </label>

                @if($largeFleet)
                {{-- Parent region checkboxes --}}
                <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto p-3 bg-gray-50">
                    <div class="grid grid-cols-1 gap-1">
                        @foreach($parentGroups as $parent => $groupIds)
                        @php $allSelected = collect($groupIds)->every(fn($id) => in_array($id, $selectedGroups)); @endphp
                        <label class="flex items-center gap-2 cursor-pointer px-2 py-1.5 rounded hover:bg-white border border-transparent {{ $allSelected ? 'border-brand-200 bg-white' : '' }}">
                            <input type="checkbox"
                                wire:click="toggleParent('{{ $parent }}', {{ json_encode($groupIds) }})"
                                @checked($allSelected)
                                class="w-3.5 h-3.5 rounded border-gray-300 text-brand-500">
                            <span class="text-xs text-gray-700 font-medium">{{ $parent }}</span>
                            <span class="text-xs text-gray-400 ml-auto">{{ count($groupIds) }} sub-groups</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    {{ empty($selectedGroups) ? 'All regions — consolidated report' : count(array_unique($selectedGroups)) . ' sub-group(s) selected' }}
                </p>
                @else
                {{-- Simple checkboxes for small fleets --}}
                <div class="flex flex-wrap gap-2">
                    @foreach($groups as $group)
                    <label class="flex items-center gap-1.5 cursor-pointer px-2 py-1 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <input type="checkbox"
                            wire:click="toggleGroup({{ $group->navixy_group_id }})"
                            @checked(in_array($group->navixy_group_id, $selectedGroups))
                            class="w-3.5 h-3.5 rounded border-gray-300 text-brand-500">
                        <span class="text-xs text-gray-700">{{ $group->title }}</span>
                    </label>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Period type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                <select wire:model.live="period"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="daily">Daily</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>

            {{-- Month picker --}}
            @if($period === 'monthly')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <input wire:model.live="month" type="month"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            @endif

            {{-- Custom date range --}}
            @if($period === 'custom')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From date</label>
                <input wire:model="fromDate" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To date</label>
                <input wire:model="toDate" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            @endif

        </div>

        {{-- Date range display --}}
        <div class="mt-3 text-xs text-gray-400">
            Report period: <strong class="text-gray-600">{{ $fromDate }}</strong> to <strong class="text-gray-600">{{ $toDate }}</strong>
        </div>

        {{-- Action buttons --}}
        <div class="flex gap-3 mt-5 pt-4 border-t border-gray-100 flex-wrap">
            <button wire:click="generateStandardReport"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Standard Fleet Report (PDF)
            </button>
            <button wire:click="generateAiReport"
                class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                AI Fleet Report (PDF)
            </button>
        </div>

    </div>

    {{-- Generated reports --}}
    @if($recentReports->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Generated reports</h3>
            <span class="text-xs text-gray-400">{{ $recentReports->count() }} reports</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($recentReports as $report)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $report->report_type === 'ai' ? 'bg-purple-100 text-purple-700' : 'bg-brand-100 text-brand-700' }}">
                            {{ $report->report_type === 'ai' ? 'AI Report' : 'Standard Report' }}
                        </span>
                        <p class="text-sm font-medium text-gray-800">{{ $report->client?->name }}</p>
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ $report->from_date->format('d M Y') }} — {{ $report->to_date->format('d M Y') }}
                        · {{ $report->generatedBy?->name ?? 'System' }}
                        · {{ $report->created_at->diffForHumans() }}
                        · {{ number_format($report->file_size / 1024, 1) }} KB
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ $isAdmin ? route('admin.afis.reports.download', $report->id) : route('client.reports.download', $report->id) }}"
                        class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium px-3 py-1.5 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    <button wire:click="deleteReport({{ $report->id }})"
                        wire:confirm="Delete this report? This cannot be undone."
                        class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>