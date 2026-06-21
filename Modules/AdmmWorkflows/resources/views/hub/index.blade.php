@extends('core::layouts.admin')
@section('title', 'Workflows')
@section('page-title', 'Workflows')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

    <a href="{{ route('admin.admm.workflows.sim-swap') }}"
        class="bg-white rounded-xl border border-gray-200 p-6 hover:border-brand-400 hover:shadow-sm transition-all group">
        <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center mb-4 group-hover:bg-brand-100 transition-colors">
            <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800 mb-1">SIM Swap</h3>
        <p class="text-xs text-gray-500">Replace the SIM card in a GPS device. Records old and new SIM in the audit log.</p>
    </a>

    <a href="{{ route('admin.admm.workflows.device-install') }}"
        class="bg-white rounded-xl border border-gray-200 p-6 hover:border-brand-400 hover:shadow-sm transition-all group">
        <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center mb-4 group-hover:bg-green-100 transition-colors">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Device Install</h3>
        <p class="text-xs text-gray-500">Assign a GPS device from internal stock to a client vehicle.</p>
    </a>

    <a href="{{ route('admin.admm.workflows.device-remove') }}"
        class="bg-white rounded-xl border border-gray-200 p-6 hover:border-red-400 hover:shadow-sm transition-all group">
        <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center mb-4 group-hover:bg-red-100 transition-colors">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Device Remove</h3>
        <p class="text-xs text-gray-500">Uninstall a GPS device from a vehicle and return it to internal stock.</p>
    </a>

</div>

{{-- Recent workflow activity --}}
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800">Recent workflow activity</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($recentActivity as $log)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                        <span class="text-gray-400 mx-1">·</span>
                        <span class="font-mono text-xs text-gray-500">{{ $log->event }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ json_encode($log->data) }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-gray-400 text-center">No workflow activity yet.</p>
        @endforelse
    </div>
</div>
@endsection