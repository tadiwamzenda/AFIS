<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Bantu Track</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans antialiased">
<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">

        {{-- Logo --}}
        <div class="h-16 flex items-center px-6 border-b border-gray-700 flex-shrink-0">
            <span class="text-lg font-bold text-white">Bantu Track</span>
            <span class="ml-2 text-xs font-semibold text-brand-500 bg-brand-900 px-2 py-0.5 rounded-full">Admin</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">

            @php
                $navLink = function (string $routeName, string $label, string $iconPath, ...$matchPatterns) {
                    $patterns = $matchPatterns ?: [$routeName];
                    $isActive = collect($patterns)->contains(fn($p) => request()->routeIs($p));
                    $activeClasses   = 'bg-brand-600/20 text-white border-l-2 border-brand-500';
                    $inactiveClasses = 'text-gray-300 hover:bg-gray-800 hover:text-white border-l-2 border-transparent';
                    return [$isActive, $isActive ? $activeClasses : $inactiveClasses];
                };
            @endphp

            {{-- ADMM --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1">Asset Management</p>

                @php [$active, $cls] = $navLink('', 'Dashboard', '', 'admin.dashboard'); @endphp
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                
                @php [$active, $cls] = $navLink('', 'Clients', '', 'admin.admm.clients.*'); @endphp
                <a href="{{ route('admin.admm.clients.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Clients
                </a>

                @php [$active, $cls] = $navLink('', 'Asset Register', '', 'admin.admm.asset-register.*'); @endphp
                <a href="{{ route('admin.admm.asset-register.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Asset Register
                </a>

                @php [$active, $cls] = $navLink('', 'Accessories', '', 'admin.admm.accessories.*'); @endphp
                <a href="{{ route('admin.admm.accessories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Accessories
                </a>

                @php [$active, $cls] = $navLink('', 'Reports', '', 'admin.admm.reports.*'); @endphp
                <a href="{{ route('admin.admm.reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Reports
                </a>
            </div>

            {{-- AFIS --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1">Fleet Intelligence</p>

                @php [$active, $cls] = $navLink('', 'Fleet Overview', '', 'admin.afis.fleet', 'admin.afis.client-fleet', 'admin.afis.vehicle', 'admin.afis.reports'); @endphp
                <a href="{{ route('admin.afis.fleet') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Fleet Overview
                </a>

                @php [$active, $cls] = $navLink('', 'Intelligence', '', 'admin.afis.intelligence.*'); @endphp
                <a href="{{ route('admin.afis.intelligence.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Intelligence
                </a>

                @php [$active, $cls] = $navLink('', 'Incidents', '', 'admin.afis.incidents.*'); @endphp
                <a href="{{ route('admin.afis.incidents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Incidents
                </a>

                @php [$active, $cls] = $navLink('', 'Data Pipeline', '', 'admin.afis.pipeline'); @endphp
                <a href="{{ route('admin.afis.pipeline') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Data Pipeline
                </a>

                @php [$active, $cls] = $navLink('', 'Reports', '', 'admin.afis.report-hub'); @endphp
                <a href="{{ route('admin.afis.report-hub') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Reports
                </a>
            </div>

            {{-- System --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1">System</p>

                @php [$active, $cls] = $navLink('', 'Notifications', '', 'admin.notifications.*'); @endphp
                <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Notifications
                </a>

                @php [$active, $cls] = $navLink('', 'Staff', '', 'admin.users.*'); @endphp
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Staff
                </a>

                @php [$active, $cls] = $navLink('', 'Audit Log', '', 'admin.admm.reports.audit-trail'); @endphp
                <a href="{{ route('admin.admm.reports.audit-trail') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $cls }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Audit Log
                </a>
            </div>

        </nav>

        {{-- User --}}
        <div class="border-t border-gray-700 p-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'BT Staff')) }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Topbar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 gap-4 flex-shrink-0">
            <h1 class="text-base font-semibold text-gray-800 flex-1 min-w-0 truncate">
                @yield('page-title', 'Dashboard')
            </h1>
            <div class="flex items-center gap-3 flex-shrink-0">
                <livewire:afis-notification-bell />
                @yield('header-actions')
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>

    </div>
</div>
@livewireScripts
</body>
</html>