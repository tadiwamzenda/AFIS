@extends('core::layouts.admin')
@section('title', 'Fleet Intelligence')
@section('page-title', 'Fleet Intelligence')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($clients as $client)
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:border-brand-300 hover:shadow-sm transition-all">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">{{ $client->name }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $client->tracker_count }} trackers</p>
            </div>
            @if($client->last_intelligence_report)
                <span class="text-xs text-green-600 font-medium">Report available</span>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.afis.intelligence.dashboard', $client->id) }}"
                class="text-xs text-white bg-brand-500 hover:bg-brand-600 font-medium px-3 py-1.5 rounded-lg transition-colors">
                Dashboard
            </a>
            <a href="{{ route('admin.afis.intelligence.archive', $client->id) }}"
                class="text-xs text-gray-600 hover:text-gray-800 font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Reports
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection