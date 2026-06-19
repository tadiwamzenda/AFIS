@extends('core::layouts.admin')
@section('title', 'Add Client')
@section('page-title', 'Add Client')

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <livewire:admm-client-form />
        </div>
    </div>
@endsection