@extends('core::layouts.admin')
@section('title', $title)
@section('page-title', $title)
@section('content')
    <div class="max-w-xl bg-white rounded-xl border border-gray-200 p-8 text-center">
        <p class="text-sm font-semibold text-gray-800">{{ $title }}</p>
        <p class="text-sm text-gray-500 mt-2">This section is being built (build step {{ $step }}) and will appear here as soon as it is ready.</p>
    </div>
@endsection