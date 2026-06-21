<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Sidebar -->
    <div class="flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-gray-900 min-h-screen fixed left-0 top-0 overflow-y-auto">
            <div class="p-4">
                <h2 class="text-white text-lg font-semibold mb-6">Bantu Track</h2>
                
                <!-- Navigation -->
                <nav class="space-y-1">
                    <!-- Dashboard Link -->
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>

                    <!-- Workflows Link -->
                    <a href="{{ route('admin.admm.workflows.hub') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Workflows
                    </a>

                    <!-- Reports Link -->
                    <a href="{{ route('admin.admm.reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Reports
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="ml-64 flex-1">
            <header class="bg-white border-b shadow-sm">
                <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold">
                            @yield('page-title', 'Dashboard')
                        </h1>
                    </div>

                    <div>
                        @yield('header-actions')
                    </div>
                </div>
            </header>

            <main class="max-w-7xl mx-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>