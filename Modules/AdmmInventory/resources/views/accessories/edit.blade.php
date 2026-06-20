@extends('core::layouts.admin')
@section('title', 'Edit Accessory')
@section('page-title', 'Edit Accessory')

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-accessory-form :accessory="$accessory" />
        </div>
    </div>
@endsection