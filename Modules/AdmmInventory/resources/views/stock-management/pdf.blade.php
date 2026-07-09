<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    @page { margin: 10mm 8mm; }
    body { font-family: Arial, sans-serif; font-size: 8px; color: #333; }
    .header { border-bottom: 2px solid #085041; padding-bottom: 8px; margin-bottom: 12px; }
    .brand { font-size: 14px; font-weight: bold; color: #085041; }
    .section-title { font-size: 10px; font-weight: bold; text-align: center; color: #333;
                     border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;
                     padding: 3px 0; margin: 10px 0 6px; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { background: #085041; color: white; padding: 4px 5px; font-size: 7px; text-align: center; }
    td { padding: 3px 5px; font-size: 7px; border-bottom: 1px solid #eee; text-align: center; }
    .text-left { text-align: left; }
    .blue { color: #1d4ed8; font-weight: bold; }
    .green { color: #15803d; font-weight: bold; }
    .red { color: #dc2626; font-weight: bold; }
    .total-row td { font-weight: bold; background: #f0f9ff; border-top: 2px solid #ccc; }
    .footer { border-top: 1px solid #ddd; padding-top: 5px; margin-top: 10px;
              font-size: 6px; color: #999; text-align: center; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">BANTU TRACK — Asset Stock Management Report</div>
    <div style="font-size:7px; color:#666; margin-top:3px;">Generated {{ now()->format('d M Y H:i') }}</div>
</div>

<div class="section-title">CURRENT ASSET COUNT</div>
<table>
    <thead>
        <tr>
            <th class="text-left" rowspan="2">CLIENT</th>
            <th colspan="4" style="background:#1d4ed8;">DEVICES</th>
            <th colspan="4" style="background:#15803d;">SIM CARDS</th>
        </tr>
        <tr>
            <th style="background:#1e40af;">Total</th>
            <th style="background:#1e40af;">With Client</th>
            <th style="background:#1e40af;">In Stock</th>
            <th style="background:#1e40af;">Lost</th>
            <th style="background:#166534;">Total</th>
            <th style="background:#166534;">With Client</th>
            <th style="background:#166534;">In Stock</th>
            <th style="background:#166534;">Lost</th>
        </tr>
    </thead>
    <tbody>
        @php $totals = array_fill_keys(['dt','dc','ds','dl','st','sc','ss','sl'], 0); @endphp
        @foreach($liveCounts as $row)
        @php
            $totals['dt'] += $row['devices_total']; $totals['dc'] += $row['devices_with_client'];
            $totals['ds'] += $row['devices_in_stock']; $totals['dl'] += $row['devices_lost'];
            $totals['st'] += $row['sims_total']; $totals['sc'] += $row['sims_with_client'];
            $totals['ss'] += $row['sims_in_stock']; $totals['sl'] += $row['sims_lost'];
        @endphp
        <tr>
            <td class="text-left">{{ $row['client'] }}</td>
            <td class="blue">{{ $row['devices_total'] }}</td>
            <td>{{ $row['devices_with_client'] }}</td>
            <td>{{ $row['devices_in_stock'] }}</td>
            <td class="{{ $row['devices_lost'] > 0 ? 'red' : '' }}">{{ $row['devices_lost'] }}</td>
            <td class="green">{{ $row['sims_total'] }}</td>
            <td>{{ $row['sims_with_client'] }}</td>
            <td>{{ $row['sims_in_stock'] }}</td>
            <td class="{{ $row['sims_lost'] > 0 ? 'red' : '' }}">{{ $row['sims_lost'] }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td class="text-left">TOTAL</td>
            <td class="blue">{{ $totals['dt'] }}</td><td class="blue">{{ $totals['dc'] }}</td>
            <td class="blue">{{ $totals['ds'] }}</td><td class="{{ $totals['dl'] > 0 ? 'red' : '' }}">{{ $totals['dl'] }}</td>
            <td class="green">{{ $totals['st'] }}</td><td class="green">{{ $totals['sc'] }}</td>
            <td class="green">{{ $totals['ss'] }}</td><td class="{{ $totals['sl'] > 0 ? 'red' : '' }}">{{ $totals['sl'] }}</td>
        </tr>
    </tbody>
</table>

@foreach(['Weekly Trend' => $weekly, 'Monthly Trend' => $monthly] as $title => $trend)
<div class="section-title">{{ strtoupper($title) }} — DEVICES WITH CLIENT</div>
<table>
    <thead>
        <tr>
            <th class="text-left">CLIENT</th>
            @foreach(array_keys($trend) as $period)
            <th>{{ $period }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($liveCounts as $row)
        <tr>
            <td class="text-left">{{ $row['client'] }}</td>
            @foreach($trend as $period => $snapshots)
            @php $snap = $snapshots->get($row['client'])?->first(); @endphp
            <td class="blue">{{ $snap?->devices_with_client ?? '—' }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
@endforeach

<div class="footer">Bantu Track Asset Stock Report · {{ now()->format('d M Y') }}</div>

</body>
</html>