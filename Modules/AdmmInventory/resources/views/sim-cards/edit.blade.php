@extends('core::layouts.admin')

@section('title', 'Add SIM Card')
@section('page-title', 'Add SIM Card')

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-sim-card-form :sim-card="$simCard" />
        </div>
    </div>
@endsection