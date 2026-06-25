@extends('core::layouts.admin')
@section('title', $client->name . ' — Fleet')
@section('page-title', $client->name)
@section('header-actions')
    <a href="{{ route('admin.afis.fleet') }}" class="text-sm text-gray-500 hover:text-gray-700">← All fleets</a>
@endsection
@section('content')
    <livewire:afis-client-fleet-dashboard :client-id="$client->id" />
@endsection