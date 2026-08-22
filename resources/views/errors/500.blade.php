@extends('errors.layout')
@section('title', 'Something Went Wrong')
@section('content')
    <div class="code">500</div>
    <div class="heading">Something went wrong</div>
    <div class="message">An unexpected error occurred on our end. Our team has been notified — please try again shortly.</div>
    <a href="/" class="btn">Go to Dashboard</a>
@endsection