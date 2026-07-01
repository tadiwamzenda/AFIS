@extends('core::layouts.admin')
@section('title', 'GPS Device Import')
@section('page-title', 'GPS Device Import')
@section('header-actions')
    <a href="{{ route('admin.admm.gps-devices.index') }}"
        class="text-sm text-gray-500 hover:text-gray-700">← GPS Devices</a>
@endsection
@section('content')
    <livewire:admm-gps-device-import />
@endsection