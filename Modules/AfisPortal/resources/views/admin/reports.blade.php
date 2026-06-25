@extends('core::layouts.admin')
@section('title', $client->name . ' — Reports')
@section('page-title', $client->name . ' — AI Reports')
@section('header-actions')
    <a href="{{ route('admin.afis.client-fleet', $client->id) }}" class="text-sm text-gray-500 hover:text-gray-700">← Fleet</a>
@endsection
@section('content')
    <livewire:afis-report-viewer :client-id="$client->id" />
@endsection