@extends('core::layouts.admin')
@section('title', $client->name . ' — Intelligence Reports')
@section('page-title', $client->name . ' — Intelligence Reports')
@section('header-actions')
    <a href="{{ route('admin.afis.intelligence.dashboard', $client->id) }}"
        class="text-sm text-gray-500 hover:text-gray-700">← Dashboard</a>
@endsection
@section('content')
    <livewire:afis-intelligence-archive :client-id="$client->id" />
@endsection