<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9px; color: #333; margin: 15mm 12mm; }
    @page { margin: 15mm 12mm; }

    /* Header */
    .report-header { padding: 15px 20px; border-bottom: 2px solid #e5e7eb; }
    .header-top { display: table; width: 100%; }
    .header-logo { display: table-cell; width: 40%; vertical-align: middle; }
    .header-logo .brand { font-size: 18px; font-weight: bold; color: #085041; }
    .header-logo .tagline { font-size: 9px; color: #6b7280; }
    .header-contact { display: table-cell; width: 60%; text-align: right; vertical-align: top; font-size: 8px; color: #6b7280; line-height: 1.5; }

    /* Title section */
    .report-title { text-align: center; padding: 20px; }
    .report-title .client-name { font-size: 16px; font-weight: bold; color: #085041; text-transform: uppercase; margin-bottom: 6px; }
    .report-title .report-type { font-size: 13px; font-weight: bold; color: #085041; margin-bottom: 10px; }
    .report-title .period { font-size: 11px; font-weight: bold; color: #085041; }

    /* Section title */
    .section-title { font-size: 11px; font-weight: bold; text-align: center; color: #333; margin: 15px 0 8px; text-transform: uppercase; }

    /* Tables */
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #085041; color: white; padding: 5px 6px; font-size: 8px; font-weight: bold; text-align: center; }
    td { padding: 4px 6px; font-size: 8px; border-bottom: 1px solid #e5e7eb; text-align: center; }
    tr:nth-child(even) td { background: #f9fafb; }
    .text-left { text-align: left; }
    .highlight { background: #fff3cd !important; font-weight: bold; }
    .danger { color: #dc2626; font-weight: bold; }
    .zero { color: #9ca3af; }

    /* Page break */
    .page-break { page-break-after: always; }

    /* Footer */
    .report-footer { border-top: 1px solid #e5e7eb; padding: 8px 20px; font-size: 7px; color: #9ca3af; text-align: center; margin-top: 15px; }

    /* Summary box */
    .summary-grid { display: table; width: 100%; margin-bottom: 15px; }
    .summary-cell { display: table-cell; width: 25%; text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb; }
    .summary-cell .val { font-size: 16px; font-weight: bold; color: #085041; }
    .summary-cell .lbl { font-size: 7px; color: #6b7280; text-transform: uppercase; margin-top: 2px; }
</style>
</head>
<body>

{{-- Page 1: Cover / Performance Metrics --}}
<div class="report-header">
    <div class="header-top">
        <div class="header-logo">
            <div class="brand">BANTU TRACK</div>
            <div class="tagline">Inspired Technologies</div>
        </div>
        <div class="header-contact">
            Bantu Track Pvt Ltd<br>
            10 Cherry Tree, Avonlea<br>
            Harare, Zimbabwe<br>
            +263 778 002 318 | 0242 702 509<br>
            operations@bantutrack.com | www.bantutrack.com
        </div>
    </div>
</div>

<div class="report-title">
    <div class="client-name">{{ $client->name }}</div>
    <div class="report-type">VEHICLE TRACKING REPORT</div>
    <div class="period">
        From: {{ $from->format('d M, Y') }}
        &nbsp;&nbsp;To: {{ $to->format('d M, Y') }}
    </div>
</div>

{{-- Performance Metrics Summary --}}
<div class="section-title">PERFORMANCE METRICS</div>

<div class="summary-grid">
    <div class="summary-cell">
        <div class="val">{{ $fleet_size }}</div>
        <div class="lbl">Fleet Size (vehicles)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($total_mileage, 2) }}</div>
        <div class="lbl">Total Mileage (km)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($weekend_km, 2) }}</div>
        <div class="lbl">Weekend &amp; Holiday (km)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($after_hrs_km, 2) }}</div>
        <div class="lbl">After Hours (km)</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="text-left">ASPECT</th>
            <th>{{ strtoupper($client->name) }}</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="text-left">Fleet Size (vehicles)</td><td>{{ $fleet_size }}</td></tr>
        <tr><td class="text-left">Total Mileage (km)</td><td>{{ number_format($total_mileage, 2) }}</td></tr>
        <tr><td class="text-left">Weekends and Holidays (km)</td><td>{{ number_format($weekend_km, 2) }}</td></tr>
        <tr><td class="text-left">After Hours (km)</td><td>{{ number_format($after_hrs_km, 2) }}</td></tr>
        @if(count($groups) > 0)
        @foreach($groups as $group)
        <tr>
            <td class="text-left" style="padding-left: 15px;">↳ {{ $group['name'] }} mileage (km)</td>
            <td>{{ number_format($group['mileage'], 2) }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>

@if(count($groups) > 1)
{{-- Sub-group breakdown --}}
<div class="section-title">SUB-GROUP BREAKDOWN</div>
<table>
    <thead>
        <tr>
            <th class="text-left">GROUP</th>
            <th>VEHICLES</th>
            <th>MILEAGE (km)</th>
            <th>WEEKEND (km)</th>
            <th>AFTER HRS (km)</th>
            <th>SPEEDING TRIPS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $group)
        <tr>
            <td class="text-left">{{ $group['name'] }}</td>
            <td>{{ $group['count'] }}</td>
            <td>{{ number_format($group['mileage'], 2) }}</td>
            <td class="{{ $group['weekend_km'] > 0 ? 'highlight' : '' }}">{{ number_format($group['weekend_km'], 2) }}</td>
            <td class="{{ $group['after_hrs'] > 0 ? 'highlight' : '' }}">{{ number_format($group['after_hrs'], 2) }}</td>
            <td class="{{ $group['speeding'] > 0 ? 'danger' : 'zero' }}">{{ $group['speeding'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="page-break"></div>

{{-- Page 2: After Hours Breakdown --}}
<div class="section-title">AFTER HOURS DRIVING (18:00 — 05:59)</div>

@php
    $afterHrsVehicles = array_filter($vehicles, fn($v) => $v['after_hrs_km'] > 0);
    $hourSlots = ['18:00-18:59','19:00-19:59','20:00-20:59','21:00-21:59','22:00-22:59','23:00-23:59','0:00-0:59','1:00-1:59','2:00-2:59','3:00-3:59','4:00-4:59','5:00-5:59'];
@endphp

@if(!empty($afterHrsVehicles))
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>LOCATION</th>
            @foreach($hourSlots as $slot)
            <th>{{ substr($slot, 0, 5) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($afterHrsVehicles as $vehicle)
        <tr>
            <td class="text-left">{{ $vehicle['label'] }}</td>
            <td>{{ $vehicle['group'] }}</td>
            @foreach($hourSlots as $slot)
            @php $km = $vehicle['hour_breakdown'][$slot] ?? 0; @endphp
            <td class="{{ $km > 0 ? 'highlight' : 'zero' }}">
                {{ $km > 0 ? round($km) : '0' }}
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center;color:#9ca3af;padding:15px;font-size:8px;">No after hours driving recorded in this period.</p>
@endif

<div class="page-break"></div>

{{-- Page 3: Speeding --}}
<div class="section-title">SPEEDING INCIDENTS (VEHICLES EXCEEDING {{ $speed_limit }} KM/H)</div>

@if(!empty($speeding))
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>GROUP</th>
            <th>TOP SPEED (km/h)</th>
            <th>SPEEDING TRIPS</th>
            <th>TOTAL MILEAGE (km)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($speeding as $v)
        <tr>
            <td class="text-left">{{ $v['label'] }}</td>
            <td>{{ $v['group'] }}</td>
            <td class="danger">{{ number_format($v['max_speed'], 0) }}</td>
            <td class="danger">{{ $v['speeding_trips'] }}</td>
            <td>{{ number_format($v['mileage'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center;color:#6b7280;padding:15px;font-size:8px;">No speeding incidents recorded. All vehicles below {{ $speed_limit }} km/h.</p>
@endif

{{-- Weekend/Holiday driving --}}
@if(!empty($weekend))
<div class="section-title">WEEKEND AND HOLIDAY DRIVING</div>
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>GROUP</th>
            <th>WEEKEND/HOLIDAY KM</th>
            <th>TOTAL MILEAGE (km)</th>
            <th>WEEKEND %</th>
        </tr>
    </thead>
    <tbody>
        @foreach($weekend as $v)
        <tr>
            <td class="text-left">{{ $v['label'] }}</td>
            <td>{{ $v['group'] }}</td>
            <td class="highlight">{{ number_format($v['weekend_km'], 2) }}</td>
            <td>{{ number_format($v['mileage'], 2) }}</td>
            <td>{{ $v['mileage'] > 0 ? number_format(($v['weekend_km']/$v['mileage'])*100, 1) : '0' }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($fuel))
<div class="page-break"></div>

{{-- Page 4: Fuel Summary --}}
<div class="section-title">FUEL SUMMARY</div>
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>DATE</th>
            <th>MILEAGE (km)</th>
            <th>REFUELINGS</th>
            <th>VOLUME (L)</th>
            <th>CONSUMED (L)</th>
            <th>CONSUMPTION km/Ltr</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fuel as $vehicle)
            @foreach($vehicle['fuel_events'] as $fe)
            <tr>
                <td class="text-left">{{ $vehicle['label'] }}</td>
                <td>{{ $fe['date'] }}</td>
                <td>{{ $fe['mileage'] ? number_format($fe['mileage'], 2) : '—' }}</td>
                <td>{{ $fe['refuels'] }}</td>
                <td>{{ $fe['volume'] ? number_format($fe['volume'], 2) : '—' }}</td>
                <td>{{ $fe['consumed'] ? number_format($fe['consumed'], 2) : '—' }}</td>
                <td>{{ $fe['rate'] ? number_format($fe['rate'], 4) : '—' }}</td>
            </tr>
            @endforeach
            @foreach($vehicle['fuel_events'] as $fe)
            @if($fe === end($vehicle['fuel_events']))
            @if($vehicle['drain_count'] > 0)
            <tr style="background: #fef2f2;">
                <td class="text-left danger">⚠ {{ $vehicle['label'] }} — DRAIN DETECTED</td>
                <td colspan="5" class="text-left danger">{{ $vehicle['drain_count'] }} drain event(s) · {{ number_format($vehicle['drain_litres'], 2) }}L total</td>
                <td></td>
            </tr>
            @endif
            @endif
            @endforeach
        @endforeach
    </tbody>
</table>
@endif

<div class="report-footer">
    Vehicle Tracking System Report · {{ $client->name }} · Generated {{ $generated_at->format('d M Y H:i') }} · Powered by Bantu Track AFIS
</div>

</body>
</html>