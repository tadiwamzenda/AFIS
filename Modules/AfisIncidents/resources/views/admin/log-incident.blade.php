@extends('core::layouts.admin')
@section('title', 'Log Incident')
@section('page-title', 'Log Incident — ' . $client->name)
@section('header-actions')
    <a href="{{ route('admin.afis.incidents.archive', $client->id) }}"
        class="text-sm text-gray-500 hover:text-gray-700">View archive →</a>
@endsection
@section('content')
    <livewire:afis-incident-form :client-id="$client->id" />
@endsection