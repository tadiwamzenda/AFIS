<div class="space-y-5">

    {{-- Header actions --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button wire:click="switchTrend('weekly')"
                class="text-sm px-4 py-2 rounded-lg font-medium transition-colors
                {{ $trendView === 'weekly' ? 'bg-brand-500 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                Weekly
            </button>
            <button wire:click="switchTrend('monthly')"
                class="text-sm px-4 py-2 rounded-lg font-medium transition-colors
                {{ $trendView === 'monthly' ? 'bg-brand-500 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                Monthly
            </button>
        </div>
        <a href="{{ route('admin.admm.asset-stock.export-pdf') }}" target="_blank"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download PDF
        </a>
    </div>

    {{-- Live counts --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-800">Current Asset Count</h3>
            <p class="text-xs text-gray-400 mt-0.5">Live data from Asset Register · {{ now()->format('d M Y H:i') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Client</th>
                        <th colspan="4" class="text-center px-4 py-3 font-medium text-blue-600 border-l border-gray-100">DEVICES</th>
                        <th colspan="4" class="text-center px-4 py-3 font-medium text-green-600 border-l border-gray-100">SIM CARDS</th>
                    </tr>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs">
                        <th class="text-left px-4 py-2 text-gray-500"></th>
                        <th class="px-4 py-2 text-blue-600 border-l border-gray-100">Total</th>
                        <th class="px-4 py-2 text-blue-600">With Client</th>
                        <th class="px-4 py-2 text-blue-600">In Stock</th>
                        <th class="px-4 py-2 text-blue-600">Lost</th>
                        <th class="px-4 py-2 text-green-600 border-l border-gray-100">Total</th>
                        <th class="px-4 py-2 text-green-600">With Client</th>
                        <th class="px-4 py-2 text-green-600">In Stock</th>
                        <th class="px-4 py-2 text-green-600">Lost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $totalDevices = 0; $totalDevsClient = 0; $totalDevsStock = 0; $totalDevsLost = 0;
                        $totalSims = 0; $totalSimsClient = 0; $totalSimsStock = 0; $totalSimsLost = 0;
                    @endphp
                    @foreach($liveCounts as $row)
                    @php
                        $totalDevices += $row['devices_total']; $totalDevsClient += $row['devices_with_client'];
                        $totalDevsStock += $row['devices_in_stock']; $totalDevsLost += $row['devices_lost'];
                        $totalSims += $row['sims_total']; $totalSimsClient += $row['sims_with_client'];
                        $totalSimsStock += $row['sims_in_stock']; $totalSimsLost += $row['sims_lost'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $row['client'] }}</td>
                        <td class="px-4 py-3 text-center font-bold text-blue-600 border-l border-gray-100">{{ $row['devices_total'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $row['devices_with_client'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $row['devices_in_stock'] }}</td>
                        <td class="px-4 py-3 text-center {{ $row['devices_lost'] > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">{{ $row['devices_lost'] }}</td>
                        <td class="px-4 py-3 text-center font-bold text-green-600 border-l border-gray-100">{{ $row['sims_total'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $row['sims_with_client'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $row['sims_in_stock'] }}</td>
                        <td class="px-4 py-3 text-center {{ $row['sims_lost'] > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">{{ $row['sims_lost'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-bold">
                        <td class="px-4 py-3 text-gray-800">TOTAL</td>
                        <td class="px-4 py-3 text-center text-blue-700 border-l border-gray-100">{{ $totalDevices }}</td>
                        <td class="px-4 py-3 text-center text-blue-700">{{ $totalDevsClient }}</td>
                        <td class="px-4 py-3 text-center text-blue-700">{{ $totalDevsStock }}</td>
                        <td class="px-4 py-3 text-center {{ $totalDevsLost > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $totalDevsLost }}</td>
                        <td class="px-4 py-3 text-center text-green-700 border-l border-gray-100">{{ $totalSims }}</td>
                        <td class="px-4 py-3 text-center text-green-700">{{ $totalSimsClient }}</td>
                        <td class="px-4 py-3 text-center text-green-700">{{ $totalSimsStock }}</td>
                        <td class="px-4 py-3 text-center {{ $totalSimsLost > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $totalSimsLost }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Trend table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-800">
                {{ $trendView === 'weekly' ? 'Weekly Trend' : 'Monthly Trend' }}
            </h3>
            <p class="text-xs text-gray-400 mt-0.5">Devices with client per period</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-600 whitespace-nowrap">Client</th>
                        @foreach(array_keys($trend) as $period)
                        <th class="px-3 py-3 font-medium text-gray-600 whitespace-nowrap text-center">{{ $period }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($clients as $client)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium text-gray-700 whitespace-nowrap">{{ $client }}</td>
                        @foreach($trend as $period => $snapshots)
                        @php
                            $snap = $snapshots->get($client)?->first();
                            $devs = $snap?->devices_with_client ?? '—';
                            $sims = $snap?->sims_with_client ?? '—';
                        @endphp
                        <td class="px-3 py-2 text-center">
                            <div class="text-blue-600 font-medium">{{ $devs }}</div>
                            <div class="text-green-600">{{ $sims }}</div>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="px-4 py-2 text-xs text-gray-400">
                <span class="text-blue-600 font-medium">Blue</span> = Devices with client &nbsp;·&nbsp;
                <span class="text-green-600 font-medium">Green</span> = SIM cards with client
            </p>
        </div>
    </div>

</div>