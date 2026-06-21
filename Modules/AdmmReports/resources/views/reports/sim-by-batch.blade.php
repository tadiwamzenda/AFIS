@extends('core::layouts.admin')
@section('title', 'SIM Cards by Batch')
@section('page-title', 'SIM Cards by Batch')

@section('header-actions')
    <a href="{{ route('admin.admm.reports.sim-by-batch', ['format' => 'excel']) }}"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition-colors">
        Export Excel
    </a>
    <a href="{{ route('admin.admm.reports.sim-by-batch', ['format' => 'pdf']) }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Export PDF
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $groups->count() }} batches</p>
        <a href="{{ route('admin.admm.reports.index') }}" class="text-xs text-gray-400 hover:text-gray-600">← All reports</a>
    </div>
    <div class="overflow-x-auto">
        @foreach($groups as $batch => $rows)
        <div class="border-b border-gray-200 last:border-b-0">
            <div class="px-4 py-3 bg-gray-50 flex items-center justify-between">
                <h3 class="font-medium text-gray-700">{{ $batch ?: 'Unassigned' }}</h3>
                <span class="text-xs text-gray-500">{{ $rows->count() }} cards</span>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-2 font-medium text-gray-600">ICCID</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">MSISDN</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Provider</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Status</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Location</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Client</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $sim)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $sim->iccid }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $sim->msisdn ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $sim->network_provider }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $sim->bundle_type ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $sim->status_label }}</span></td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $sim->location_context)) }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $sim->client?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">No SIM cards in this batch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</div>
@endsection