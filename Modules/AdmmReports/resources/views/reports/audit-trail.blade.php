@extends('core::layouts.admin')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('header-actions')
    <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border border-gray-300 transition-colors">
        Export Excel
    </a>
    <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Export PDF
    </a>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Module</label>
                <input name="module" value="{{ $filters['module'] ?? '' }}" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. AdmmWorkflows">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Event</label>
                <input name="event" value="{{ $filters['event'] ?? '' }}" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="e.g. sim.swapped">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From date</label>
                <input name="date_from" value="{{ $filters['date_from'] ?? '' }}" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To date</label>
                <input name="date_to" value="{{ $filters['date_to'] ?? '' }}" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Apply filters</button>
            <a href="{{ route('admin.admm.reports.audit-trail') }}" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Clear</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <p class="text-sm text-gray-500">{{ $rows->count() }} records (max 500)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">User</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Module</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Event</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Entity</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2 font-medium text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $log->module }}</td>
                        <td class="px-4 py-2"><span class="font-mono text-gray-600">{{ $log->event }}</span></td>
                        <td class="px-4 py-2 text-gray-500">{{ $log->entity_type ? $log->entity_type . ' #' . $log->entity_id : '—' }}</td>
                        <td class="px-4 py-2 text-gray-400 max-w-xs truncate">{{ json_encode($log->data) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No audit records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection