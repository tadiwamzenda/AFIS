@extends('core::layouts.admin')
@section('title', 'Generate Reports')
@section('page-title', 'Generate Reports')
@section('content')
    <livewire:afis-report-generator :is-admin="true" />
@endsection