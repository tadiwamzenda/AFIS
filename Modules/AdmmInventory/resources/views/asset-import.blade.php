@extends('core::layouts.admin')
@section('title', 'Import Asset Register')
@section('page-title', 'Import Asset Register')
@section('header-actions')
    <a href="{{ route('admin.admm.asset-register.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Asset Register</a>
@endsection
@section('content')
    <livewire:admm-asset-import />
@endsection