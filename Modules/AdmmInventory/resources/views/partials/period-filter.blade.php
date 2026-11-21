{{-- Period dropdown. Needs $period, $fromDate, $toDate, $periodError from the component. Optional $label. --}}
<div class="flex flex-wrap items-end gap-3">
    <label class="text-xs text-gray-600">{{ $label ?? 'Period' }}
        <select wire:model.live="period" class="mt-1 block px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="all">All time</option>
            <option value="today">Daily (today)</option>
            <option value="week">Weekly (this week)</option>
            <option value="month">Monthly (this month)</option>
            <option value="custom">Custom period</option>
        </select>
    </label>
    @if($period === 'custom')
        <label class="text-xs text-gray-600">From
            <input type="date" wire:model.live="fromDate" class="mt-1 block px-2 py-2 border border-gray-300 rounded-lg text-sm">
        </label>
        <label class="text-xs text-gray-600">To
            <input type="date" wire:model.live="toDate" class="mt-1 block px-2 py-2 border border-gray-300 rounded-lg text-sm">
        </label>
    @endif
    @if($periodError)
        <span class="text-xs text-red-600 pb-2">{{ $periodError }}</span>
    @endif
</div>
