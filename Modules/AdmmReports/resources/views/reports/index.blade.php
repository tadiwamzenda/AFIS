@extends('core::layouts.admin')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div class="space-y-6">

    {{-- SIM Cards --}}
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">SIM Cards</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach([
                ['SIM Card Inventory',    route('admin.admm.reports.sim-inventory')],
                ['By Network Provider',   route('admin.admm.reports.sim-by-provider')],
                ['By Batch Code',         route('admin.admm.reports.sim-by-batch')],
                ['By Status',             route('admin.admm.reports.sim-by-status')],
            ] as [$label, $url])
            <a href="{{ $url }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-brand-400 hover:shadow-sm transition-all">
                <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Excel · PDF</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- GPS Devices --}}
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">GPS Devices</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach([
                ['Device Inventory',      route('admin.admm.reports.device-inventory')],
                ['Devices by Client',     route('admin.admm.reports.devices-by-client')],
                ['Devices in Stock',      route('admin.admm.reports.devices-in-stock')],
            ] as [$label, $url])
            <a href="{{ $url }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-brand-400 hover:shadow-sm transition-all">
                <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Excel · PDF</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Accessories --}}
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Accessories</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach([
                ['Accessory Inventory',   route('admin.admm.reports.accessory-inventory')],
                ['By Type',               route('admin.admm.reports.accessories-by-type')],
            ] as [$label, $url])
            <a href="{{ $url }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-brand-400 hover:shadow-sm transition-all">
                <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Excel · PDF</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Combined --}}
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Combined</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach([
                ['Assignment Chain',      route('admin.admm.reports.assignment-chain')],
                ['Internal Stock Summary',route('admin.admm.reports.internal-stock')],
                ['Audit Trail',           route('admin.admm.reports.audit-trail')],
            ] as [$label, $url])
            <a href="{{ $url }}" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-brand-400 hover:shadow-sm transition-all">
                <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Excel · PDF</p>
            </a>
            @endforeach
        </div>
    </div>

</div>
@endsection