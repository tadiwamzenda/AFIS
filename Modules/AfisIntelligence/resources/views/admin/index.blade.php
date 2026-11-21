@extends('core::layouts.admin')
@section('title', 'Fleet Uptime States')
@section('page-title', 'Fleet Uptime States')

@section('content')
<div class="space-y-6">

    {{-- Fleet state panels: grand totals for all clients --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Fleet Total</p>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($states['total']) }}</p>
            <p class="text-xs text-gray-400 mt-1">All vehicles, all clients</p>
        </div>

        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Online</p>
            </div>
            <p class="text-3xl font-bold text-green-700">{{ number_format($states['online']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $states['total'] > 0 ? round(($states['online'] / $states['total']) * 100) : 0 }}% of fleet</p>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Offline</p>
            </div>
            <p class="text-3xl font-bold text-red-700">{{ number_format($states['offline']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $states['total'] > 0 ? round(($states['offline'] / $states['total']) * 100) : 0 }}% of fleet · Functional &amp; Bantu Track
                @if($states['excluded'] > 0)<span class="block">+ {{ number_format($states['excluded']) }} offline not counted (client / inactive)</span>@endif
            </p>
        </div>
    </div>

    {{-- Fleet states report generation --}}
    <form method="GET" action="{{ route('admin.afis.fleet.overview-report') }}"
        class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-48">
            <p class="text-sm font-semibold text-gray-800">Fleet states report — all clients</p>
            <p class="text-xs text-gray-400">Fleet, online and offline totals per client, incidents in the period and every vehicle offline now.</p>
        </div>
        <select name="period" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="daily">Daily (today)</option>
            <option value="weekly">Weekly (this week)</option>
            <option value="monthly" selected>Monthly (this month)</option>
            <option value="yearly">Yearly (this year)</option>
        </select>
        <button type="submit" style="background:#2563eb;color:#fff" class="text-sm font-medium px-4 py-1.5 rounded-lg">Generate report</button>
    </form>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($clients as $client)
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:border-brand-300 hover:shadow-sm transition-all">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">{{ $client->name }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $client->tracker_count }} trackers
                    @if($client->state)
                        · <span class="text-green-600 font-medium">{{ $client->state['online'] }} online</span>
                        · <span class="text-red-600 font-medium">{{ $client->state['offline'] }} offline</span>
                    @endif
                </p>
            </div>
            @if($client->last_intelligence_report)
                <span class="text-xs text-green-600 font-medium">Report available</span>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            @if(\Illuminate\Support\Facades\Route::has('admin.afis.intelligence.dashboard'))
            <a href="{{ route('admin.afis.intelligence.dashboard', $client->id) }}"
                class="text-xs text-white bg-brand-500 hover:bg-brand-600 font-medium px-3 py-1.5 rounded-lg transition-colors">
                Dashboard
            </a>
            @endif
            
            @if(\Illuminate\Support\Facades\Route::has('admin.afis.intelligence.archive'))
            <a href="{{ route('admin.afis.intelligence.archive', $client->id) }}"
                class="text-xs text-gray-600 hover:text-gray-800 font-medium px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Reports
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>
</div>
@endsection