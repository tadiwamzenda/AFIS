@extends('core::layouts.admin')
@section('title', 'Edit GPS Device')
@section('page-title', 'Edit GPS Device — ' . $device->imei)

@section('content')
    <livewire:admm-gps-device-form :device-id="$device->id" />
@endsection