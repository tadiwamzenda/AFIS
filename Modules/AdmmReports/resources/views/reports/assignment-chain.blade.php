@extends('core::layouts.admin')
@section('title', 'Assignment Chain')
@section('page-title', 'Assignment Chain')

@section('header-actions')
    <a href="{{ route('admin.admm.reports.assignment-chain', ['format' => 'pdf']) }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Export PDF
    </a>
@endsection

@section('content')
<div class="space-y-4">
    @forelse($clients as $client)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">{{ $client->name }}</p>
            <p class="text-xs text-gray-400">Navixy account: {{ $client->navixy_account_id }}</p>
        </div>
        @forelse($client->gpsDevices as $device)
        <div class="px-5 py-3 border-b border-gray-50">
            <div class="flex items-start gap-4">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-700">{{ $device->serial_number }} — {{ $device->model }}</p>
                    <p class="text-xs text-gray-400">Vehicle: {{ $device->vehicle_registration ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">SIM: {{ $device->simCard?->msisdn ?? 'None' }}</p>
                    <p class="text-xs text-gray-400">{{ $device->simCard?->network_provider ?? '' }}</p>
                </div>
            </div>
            @if($device->accessories->isNotEmpty())
            <div class="mt-2 flex gap-2 flex-wrap">
                @foreach($device->accessories as $acc)
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                    {{ $acc->accessoryType?->name ?? 'Unknown' }}
                    {{ $acc->serial_number ? '· ' . $acc->serial_number : '' }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="px-5 py-3 text-sm text-gray-400">No devices assigned to this client.</div>
        @endforelse
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-10 text-center text-gray-400">No active clients found.</div>
    @endforelse
</div>
@endsection