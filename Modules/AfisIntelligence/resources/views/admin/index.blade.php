@extends('core::layouts.admin')
@section('title', 'Fleet States')
@section('page-title', 'Fleet States')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($clients as $client)
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:border-brand-300 hover:shadow-sm transition-all">
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-800">{{ $client->name }}</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ $client->tracker_count }} trackers</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.afis.intelligence.dashboard', $client->id) }}"
                class="text-xs text-white bg-brand-500 hover:bg-brand-600 font-medium px-3 py-1.5 rounded-lg transition-colors">
                View states
            </a>
            <a href="{{ route('admin.afis.intelligence.dashboard', ['clientId' => $client->id, 'tab' => 'reports']) }}"
                class="text-xs text-gray-600 hover:text-gray-800 font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Reports
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection