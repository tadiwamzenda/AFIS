@extends('core::layouts.admin')
@section('title', 'GPS Devices')
@section('page-title', 'GPS Devices')

@section('header-actions')
    <a href="{{ route('admin.admm.gps-devices.create') }}"
        class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add device
    </a>
@endsection

@section('content')
    <livewire:admm-gps-device-index />
@endsection