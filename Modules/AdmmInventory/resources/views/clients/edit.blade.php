@extends('core::layouts.admin')
@section('title', 'Edit Client')
@section('page-title', 'Edit Client — ' . $client->name)

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-client-form :client="$client" />
        </div>
    </div>
@endsection