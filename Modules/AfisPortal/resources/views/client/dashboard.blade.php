@extends('core::layouts.client')
@section('title', 'Fleet Dashboard')
@section('page-title', 'Fleet Dashboard')
@section('content')
    <livewire:afis-client-fleet-dashboard :client-id="$client->id" />
@endsection