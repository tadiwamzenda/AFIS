@extends('core::layouts.admin')
@section('title', $tracker->label . ' — Inspector')
@section('page-title', $tracker->label)
@section('header-actions')
    <a href="{{ route('admin.afis.client-fleet', $tracker->client_id) }}" class="text-sm text-gray-500 hover:text-gray-700">← Fleet</a>
@endsection
@section('content')
    <livewire:afis-vehicle-inspector :tracker-id="$tracker->id" />
@endsection