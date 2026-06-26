@extends('core::layouts.client')
@section('title', 'Incident Archive')
@section('page-title', 'Incident Archive')
@section('header-actions')
    <a href="{{ route('client.incidents.log') }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        + Log incident
    </a>
@endsection
@section('content')
    <livewire:afis-incident-archive :client-id="$client->id" />
@endsection