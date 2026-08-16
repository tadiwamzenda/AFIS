@extends('core::layouts.admin')
@section('title', $client->name . ' — Reports')
@section('page-title', $client->name . ' — AI Reports')
@section('header-actions')
    <a href="{{ route('admin.afis.client-fleet', $client->id) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white px-3 py-1.5 border border-gray-300 rounded-lg bg-brand-500 hover:text-gray-700 transition-colors">← Fleet</a>
@endsection
@section('content')
    <livewire:afis-report-viewer :client-id="$client->id" />
@endsection