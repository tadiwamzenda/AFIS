@extends('core::layouts.client')
@section('title', 'AI Reports')
@section('page-title', 'AI Reports')
@section('content')
    <livewire:afis-report-viewer :client-id="$client->id" />
@endsection