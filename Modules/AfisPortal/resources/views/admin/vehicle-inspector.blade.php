@extends('core::layouts.admin')
@section('title', $tracker->label . ' — Inspector')
@section('page-title', $tracker->label)
@section('header-actions')
    <a href="{{ route('admin.afis.client-fleet', $tracker->client_id) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white px-3 py-1.5 border border-gray-300 rounded-lg bg-brand-500 hover:text-gray-700 transition-colors">← Fleet</a>
@endsection
@section('content')
    <livewire:afis-vehicle-inspector :tracker-id="$tracker->id" />
@endsection