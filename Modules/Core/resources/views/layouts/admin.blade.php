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

    <header class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">
                    @yield('page-title', 'Dashboard')
                </h1>
                 <a href="{{ route('admin.admm.workflows.hub') }}"
                    class="text-sm text-blue-600 hover:text-blue-800">
                    Workflows
                </a>
            </div>

            <div>
                @yield('header-actions')
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>