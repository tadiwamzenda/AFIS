@extends('core::layouts.admin')
@section('title', $client->name . ' — Online / Offline States')
@section('page-title', $client->name . ' — Online / Offline States')
@section('header-actions')
    <a href="{{ route('admin.afis.intelligence.index') }}"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-white px-3 py-1.5 border border-gray-300 rounded-lg bg-brand-500 hover:text-gray-700 transition-colors">
        ← All Clients
    </a>
@endsection
@section('content')
    <livewire:afis-fleet-state-dashboard :client-id="$client->id" />
@endsection