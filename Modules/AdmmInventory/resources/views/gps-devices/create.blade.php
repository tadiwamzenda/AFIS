@extends('core::layouts.admin')
@section('title', 'Add GPS Device')
@section('page-title', 'Add GPS Device')

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-gps-device-form />
        </div>
    </div>
@endsection