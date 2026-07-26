@extends('core::layouts.admin')
@section('title', $client->name . ' — Fleet')
@section('page-title', $client->name)
@section('header-actions')
    <a href="{{ route('admin.afis.fleet') }}" class="text-sm text-gray-500 hover:text-gray-700">← All fleets</a>
@endsection
@section('content')
<div x-data="{ tab: 'fleet' }" class="space-y-4">

    {{-- Tab buttons --}}
    <div class="flex justify-center gap-3">
        <button @click="tab = 'fleet'"
            :class="tab === 'fleet'
                ? 'bg-brand-500 text-white shadow-sm'
                : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'"
            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Fleet Dashboard
        </button>
        <button @click="tab = 'state'"
            :class="tab === 'state'
                ? 'bg-brand-500 text-white shadow-sm'
                : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'"
            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Online / Offline States
        </button>
    </div>

    {{-- Fleet Dashboard --}}
    <div x-show="tab === 'fleet'" x-cloak>
        <livewire:afis-client-fleet-dashboard :client-id="$client->id" wire:key="fleet-{{ $client->id }}" />
    </div>

    {{-- State Dashboard --}}
    <div x-show="tab === 'state'" x-cloak>
        <livewire:afis-fleet-state-dashboard :client-id="$client->id" wire:key="state-{{ $client->id }}" />
    </div>

</div>
@endsection