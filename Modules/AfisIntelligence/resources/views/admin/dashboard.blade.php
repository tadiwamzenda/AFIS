@extends('core::layouts.admin')
@section('title', $client->name . ' — Intelligence')
@section('page-title', $client->name . ' — Intelligence')
@section('header-actions')
    <a href="{{ route('admin.afis.intelligence.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← All clients</a>
@endsection
@section('content')
    <livewire:afis-intelligence-dashboard :client-id="$client->id" />
@endsection