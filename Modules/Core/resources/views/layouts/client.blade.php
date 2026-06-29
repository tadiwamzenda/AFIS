<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fleet Portal') — Bantu Track</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased">

    {{-- Top Nav --}}
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-screen-xl mx-auto px-6">
            <div class="h-16 flex items-center justify-between">

                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-gray-900">Bantu Track</span>
                    <span class="text-xs font-semibold text-brand-500 bg-brand-50 px-2 py-0.5 rounded-full">Fleet Portal</span>
                </div>

                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('client.dashboard') }}" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">Dashboard</a>
                    <a href="{{ route('client.intelligence.dashboard') }}" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">Intelligence</a>
                    <a href="{{ route('client.incidents.log') }}" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">Incidents</a>
                    <a href="{{ route('client.incidents.archive') }}" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">Reports</a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden md:block text-right">
                        <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-400">Fleet Manager</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-brand-500 flex items-center justify-center text-white text-sm font-semibold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign out" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    <main class="max-w-screen-xl mx-auto px-6 py-6">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if(View::hasSection('page-title'))
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">@yield('page-title')</h1>
                <div class="flex items-center gap-3">@yield('header-actions')</div>
            </div>
        @endif
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>