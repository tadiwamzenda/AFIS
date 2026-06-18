@extends('core::layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-brand-500 mb-4">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Bantu Track</h1>
        <p class="text-sm text-gray-500 mt-1">Sign in with your Navixy credentials</p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email address
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                placeholder="you@example.com"
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password
            </label>
            <input
                type="password"
                id="password"
                name="password"
                required
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                placeholder="••••••••"
            >
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-500">
            <label for="remember" class="text-sm text-gray-600">Remember me</label>
        </div>

        <button
            type="submit"
            class="w-full bg-brand-500 hover:bg-brand-600 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors mt-2"
        >
            Sign in
        </button>
    </form>

    <p class="text-xs text-gray-400 text-center mt-6">
        Use your Navixy account credentials · Powered by Bantu Track
    </p>

</div>
@endsection