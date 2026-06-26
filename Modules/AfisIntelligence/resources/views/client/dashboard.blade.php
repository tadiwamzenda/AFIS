@extends('core::layouts.client')
@section('title', 'Fleet Intelligence')
@section('page-title', 'Fleet Intelligence')
@section('content')
    <livewire:afis-intelligence-dashboard :client-id="$client->id" />
@endsection