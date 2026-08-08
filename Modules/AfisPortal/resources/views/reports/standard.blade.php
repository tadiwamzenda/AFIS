<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { margin: 15mm 12mm; font-family: Arial, sans-serif; font-size: 8px; color: #333; }
    @page { margin: 15mm 12mm; size: A4 landscape; }

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
    table { width: 100%; border-collapse: collapse; table-layout: auto; margin-bottom: 15px; }
    th {
        background: #085041; color: white; padding: 4px 6px; font-size: 7px; font-weight: bold;
        text-align: center; white-space: normal; word-break: break-word;
        border: 1px solid #e5e7eb;
    }
    td { padding: 3px 6px; font-size: 8px; text-align: center; border: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .text-left { text-align: left; }
    .bold { font-weight: bold; }
    .highlight { background: #fff3cd !important; font-weight: bold; }
    .danger { color: #dc2626; font-weight: bold; }

    /* Exact colors sampled from approved sample images.
       !important is required: tr:nth-child(even) td has higher specificity
       than a single class and would otherwise override these on every other row. */
    .col-yellow  { background: #FFFF00 !important; }
    .col-amber   { background: #FFA500 !important; }
    .col-red     { background: #FF0000 !important; }
    .col-green-1 { background: #94D5B1 !important; }
    .col-green-2 { background: #D8F2DB !important; }

    /* Each heading+table pair tries to stay together and packs onto whatever
       page has room — small clients get everything on fewer pages, large
       tables still flow across pages normally if they're too tall to fit.
       This is a known soft spot in DomPDF; if a table ever gets stranded
       or misbehaves, drop .table-group and force page-break-before on each
       section instead as the simpler, safer fallback. */
    .table-group { page-break-inside: avoid; }
    tr { page-break-inside: avoid; }

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
            operations@bantutrack.co.zw | www.bantutrack.com
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
    </tbody>
</table>

@if(count($groups) > 1)
{{-- Sub-group breakdown — groups as COLUMNS, metrics as ROWS --}}
<div class="section-title" style="margin-top:10px;">SUB-GROUP PERFORMANCE</div>
<table>
    <thead>
        <tr>
            <th class="text-left" style="width:120px;">ASPECT</th>
            @foreach($groups as $group)
            <th>{{ strtoupper($group['name']) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-left bold">Fleet Size (vehicles)</td>
            @foreach($groups as $group)
            <td class="bold">{{ $group['count'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="text-left bold">Total Mileage</td>
            @foreach($groups as $group)
            <td>{{ number_format($group['mileage'], 0) }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="text-left bold">Weekends and Holidays</td>
            @foreach($groups as $group)
            <td class="{{ $group['weekend_km'] > 0 ? 'highlight' : '' }}">{{ number_format($group['weekend_km'], 0) }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="text-left bold">After Hours (km)</td>
            @foreach($groups as $group)
            <td class="{{ $group['after_hrs'] > 0 ? 'highlight' : '' }}">{{ number_format($group['after_hrs'], 0) }}</td>
            @endforeach
        </tr>
    </tbody>
</table>
@endif

{{-- After Hours Breakdown --}}
@php
$afterHrsVehicles = array_filter($vehicles, fn($v) => $v['after_hrs_km'] > 0);
$hourCols = [
    ['key' => '18:00-18:59', 'label' => '18:00-18:59', 'color' => 'col-yellow'],
    ['key' => '19:00-19:59', 'label' => '19:00-19:59', 'color' => 'col-yellow'],
    ['key' => '20:00-20:59', 'label' => '20:00-20:59', 'color' => 'col-amber'],
    ['key' => '21:00-21:59', 'label' => '21:00-21:59', 'color' => 'col-amber'],
    ['key' => '22:00-22:59', 'label' => '22:00-22:59', 'color' => 'col-red'],
    ['key' => '23:00-23:59', 'label' => '23:00-23:59', 'color' => 'col-red'],
    ['key' => '0:00-0:59',   'label' => '00:00-00:59', 'color' => 'col-red'],
    ['key' => '1:00-1:59',   'label' => '01:00-01:59', 'color' => 'col-red'],
    ['key' => '2:00-2:59',   'label' => '02:00-02:59', 'color' => 'col-red'],
    ['key' => '3:00-3:59',   'label' => '03:00-03:59', 'color' => 'col-red'],
    ['key' => '4:00-4:59',   'label' => '04:00-04:59', 'color' => 'col-amber'],
    ['key' => '5:00-5:59',   'label' => '05:00-05:59', 'color' => 'col-yellow'],
];
@endphp

@if(!empty($afterHrsVehicles))
<div class="table-group">
<div class="section-title">AFTER HOURS DRIVING (18:00 — 05:59)</div>
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>LOCATION</th>
            @foreach($hourCols as $col)
            <th style="width:50px;">{{ $col['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($afterHrsVehicles as $vehicle)
        <tr>
            <td class="text-left">{{ $vehicle['label'] }}</td>
            <td>{{ $vehicle['group'] }}</td>
            @foreach($hourCols as $col)
            @php $km = round($vehicle['hour_breakdown'][$col['key']] ?? 0); @endphp
            <td class="{{ $col['color'] }}" style="{{ $km > 0 ? 'font-weight:bold;' : '' }}">{{ $km }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Speeding --}}
@if(!empty($speeding_detail))
<div class="table-group">
<div class="section-title">SPEEDING INCIDENTS — VEHICLES EXCEEDING {{ $speed_limit }} KM/H</div>
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>TOP SPEED (KM/H)</th>
            <th class="text-left">LOCATION</th>
            <th>DATE</th>
            <th>FREQUENCY</th>
        </tr>
    </thead>
    <tbody>
        @foreach($speeding_detail as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            <td class="danger bold">{{ $v['top_speed'] }}</td>
            <td class="text-left" style="font-size:7px;">{{ $v['address'] }}</td>
            <td>{{ $v['time'] }}</td>
            <td class="danger bold">{{ $v['frequency'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Weekend/Holiday --}}
@php $weekendVehicles = array_filter($vehicles, fn($v) => ($v['weekend_total'] ?? 0) >= 5); @endphp

@if(!empty($weekendVehicles) && !empty($weekend_dates))
<div class="table-group">
<div class="section-title">WEEKENDS AND HOLIDAYS</div>
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE ID.</th>
            <th class="text-left">LOCATION</th>
            @foreach($weekend_dates as $date)
            <th>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</th>
            @endforeach
            <th>TOTAL WEEKEND MILEAGE</th>
            <th>TOTAL OVERALL MILEAGE</th>
            <th>% OF TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($weekendVehicles as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            <td class="text-left">{{ $v['group'] }}</td>
            @foreach($weekend_dates as $di => $date)
            @php
                $km = $v['weekend_dates'][$date] ?? 0;
                $greenClass = (intdiv($di, 2) % 2 === 0) ? 'col-green-1' : 'col-green-2';
            @endphp
            <td class="{{ $greenClass }}" style="{{ $km > 0 ? 'font-weight:bold;' : '' }}">{{ $km }}</td>
            @endforeach
            <td class="bold highlight">{{ number_format($v['weekend_total'], 2) }}</td>
            <td class="bold">{{ number_format($v['trip_mileage'], 2) }}</td>
            <td class="{{ $v['trip_mileage'] > 0 && ($v['weekend_total']/$v['trip_mileage']) > 0.3 ? 'danger' : '' }}">
                {{ $v['trip_mileage'] > 0 ? number_format(($v['weekend_total']/$v['trip_mileage'])*100, 1) : '0' }}%
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

@if(!empty($fuel_flat))
{{-- Fuel Summary --}}
<div class="table-group">
<div class="section-title">FUEL SUMMARY</div>

<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>DATE</th>
            <th>MILEAGE</th>
            <th>REFUELINGS (count)</th>
            <th>VOLUME (L)</th>
            <th>CONSUMED (L)</th>
            <th>CONSUMPTION kms/Ltr</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fuel_flat as $fe)
        <tr>
            <td class="text-left bold">{{ $fe['label'] }}</td>
            <td>{{ $fe['date'] }}</td>
            <td>{{ $fe['mileage'] ? number_format($fe['mileage'], 2) : '0.00' }}</td>
            <td>{{ $fe['refuels'] }}</td>
            <td>{{ $fe['volume'] ? number_format($fe['volume'], 2) : '—' }}</td>
            <td>{{ $fe['consumed'] ? number_format($fe['consumed'], 2) : '—' }}</td>
            <td>{{ $fe['rate'] ? number_format($fe['rate'], 4) : '' }}</td>
        </tr>
        @if(($fe['type'] ?? '') === 'drain')
        <tr style="background:#ffeaea;">
            <td class="text-left danger bold">{{ $fe['label'] }}</td>
            <td>{{ $fe['date'] }}</td>
            <td>{{ $fe['mileage'] }}</td>
            <td class="danger bold">DRAIN</td>
            <td class="danger bold">{{ $fe['volume'] ? number_format($fe['volume'], 2) : '—' }}</td>
            <td>—</td>
            <td>—</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
</div>
@endif

<div class="report-footer">
    Vehicle Tracking System Report · {{ $client->name }} · Generated {{ $generated_at->format('d M Y H:i') }} · Powered by Bantu Track AFIS
</div>

</body>
</html>