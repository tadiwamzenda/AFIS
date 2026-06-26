@extends('core::layouts.admin')
@section('title', 'Incidents')
@section('page-title', 'Incidents')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($clients as $client)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ $client->name }}</h3>
        <p class="text-xs text-gray-400 mb-4">Navixy account: {{ $client->navixy_account_id }}</p>
        <div class="flex gap-2">
            <a href="{{ route('admin.afis.incidents.log', $client->id) }}"
                class="text-xs text-white bg-brand-500 hover:bg-brand-600 font-medium px-3 py-1.5 rounded-lg transition-colors">
                Log incident
            </a>
            <a href="{{ route('admin.afis.incidents.archive', $client->id) }}"
                class="text-xs text-gray-600 hover:text-gray-800 font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                View archive
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection