@extends('core::layouts.admin')
@section('title', 'Internal Stock')
@section('page-title', 'Internal Stock')

@section('header-actions')
    <a href="{{ route('admin.admm.reports.internal-stock', ['format' => 'excel']) }}"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition-colors">
        Export Excel
    </a>
    <a href="{{ route('admin.admm.reports.internal-stock', ['format' => 'pdf']) }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Export PDF
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <!-- SIM Cards in Stock -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-medium text-gray-700">SIM Cards in Stock</h3>
            <span class="text-xs text-gray-500">{{ $sims->count() }} cards</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-2 font-medium text-gray-600">ICCID</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">MSISDN</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Provider</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Batch</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($sims as $sim)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $sim->iccid }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $sim->msisdn ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $sim->network_provider }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $sim->batch_code ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $sim->bundle_type ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $sim->status_label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">No SIM cards in stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Devices in Stock -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-medium text-gray-700">Devices in Stock</h3>
            <span class="text-xs text-gray-500">{{ $devices->count() }} devices</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Serial Number</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Model</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Firmware</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $device->serial_number }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $device->model ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $device->firmware_version ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $device->status_label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">No devices in stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Accessories in Stock -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-medium text-gray-700">Accessories in Stock</h3>
            <span class="text-xs text-gray-500">{{ $accessories->count() }} accessories</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Serial Number</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($accessories as $accessory)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-700">{{ $accessory->type_name ?? '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $accessory->serial_number }}</td>
                        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $accessory->status_label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400">No accessories in stock.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection