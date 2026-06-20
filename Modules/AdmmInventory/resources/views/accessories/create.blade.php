@extends('core::layouts.admin')
@section('title', 'Add Accessory')
@section('page-title', 'Add Accessory')

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-accessory-form />
        </div>
    </div>
@endsection