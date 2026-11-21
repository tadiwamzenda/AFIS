@extends('core::layouts.admin')
@section('title', $title)
@section('page-title', $title)
@section('content')
    <livewire:admm-stock-take :section="$section" :key="'stock-take-'.$section" />
@endsection
