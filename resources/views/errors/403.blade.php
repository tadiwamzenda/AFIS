@extends('errors.layout')
@section('title', 'Access Denied')
@section('content')
    <div class="code">403</div>
    <div class="heading">Access denied</div>
    <div class="message">You don't have permission to view this page. If you believe this is a mistake, contact Bantu Track support.</div>
    <a href="/" class="btn">Go to Dashboard</a>
@endsection