@extends('core::layouts.admin')
@section('title', 'Edit GPS Device')
@section('page-title', 'Edit GPS Device — ' . $device->serial_number)

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-gps-device-form :device="$device" />
        </div>
    </div>
@endsection