@extends('core::layouts.client')
@section('title', $tracker->label)
@section('page-title', $tracker->label)
@section('content')
    <livewire:afis-vehicle-inspector :tracker-id="$tracker->id" />
@endsection