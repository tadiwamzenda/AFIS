@extends('errors.layout')
@section('title', 'Session Expired')
@section('content')
    <div class="code">419</div>
    <div class="heading">Your session has expired</div>
    <div class="message">For your security, sessions expire after a period of inactivity. Please log in again.</div>
    <a href="/login" class="btn">Log In</a>
@endsection