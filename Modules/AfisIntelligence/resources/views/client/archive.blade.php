@extends('core::layouts.client')
@section('title', 'Intelligence Reports')
@section('page-title', 'Intelligence Reports')
@section('content')
    <livewire:afis-intelligence-archive :client-id="$client->id" />
@endsection