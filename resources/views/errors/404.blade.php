@extends('errors.layout')
@section('title', 'Page Not Found')
@section('content')
    <div class="code">404</div>
    <div class="heading">Page not found</div>
    <div class="message">The page you're looking for doesn't exist or may have been moved.</div>
    <a href="/" class="btn">Go to Dashboard</a>
@endsection