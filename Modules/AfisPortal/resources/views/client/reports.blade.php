@extends('core::layouts.client')
@section('title', 'Reports')
@section('page-title', 'Reports')
@section('content')
    <livewire:afis-report-generator :is-admin="false" :client-id="$client->id" />
@endsection