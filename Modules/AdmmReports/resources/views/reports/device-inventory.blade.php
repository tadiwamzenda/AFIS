@extends('core::layouts.admin')
@section('title', 'Device Inventory')
@section('page-title', 'Device Inventory')

@section('header-actions')
    <a href="{{ route('admin.admm.reports.device-inventory', ['format' => 'excel']) }}"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition-colors">
        Export Excel
    </a>
    <a href="{{ route('admin.admm.reports.device-inventory', ['format' => 'pdf']) }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Export PDF
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $rows->count() }} records</p>
        <a href="{{ route('admin.admm.reports.index') }}" class="text-xs text-gray-400 hover:text-gray-600">← All reports</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Serial Number</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Model</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Firmware</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Location</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Vehicle</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">SIM Card</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $device)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $device->serial_number }}</td>
                    <td class="px-4 py-2 text-gray-700">{{ $device->model ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $device->firmware_version ?? '—' }}</td>
                    <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $device->status_label }}</span></td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $device->location_context)) }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $device->client?->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $device->vehicle?->name ?? '—' }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $device->sim?->iccid ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection