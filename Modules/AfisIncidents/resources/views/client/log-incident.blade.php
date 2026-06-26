@extends('core::layouts.client')
@section('title', 'Log Incident')
@section('page-title', 'Log Incident')
@section('content')
    <livewire:afis-incident-form :client-id="$client->id" />
@endsection