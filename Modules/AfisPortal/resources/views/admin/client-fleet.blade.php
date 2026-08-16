@extends('core::layouts.admin')
@section('title', $client->name . ' — Fleet')
@section('page-title', $client->name)
@section('header-actions')
    <a href="{{ route('admin.afis.fleet') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white px-3 py-1.5 border border-gray-300 rounded-lg bg-brand-500 hover:text-gray-700 transition-colors">← All Fleets</a>
@endsection
@section('content')
<div class="space-y-4">
    <livewire:afis-client-fleet-dashboard :client-id="$client->id" wire:key="fleet-{{ $client->id }}" />
</div>
@endsection