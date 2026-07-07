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
    .col-yellow  { background: #fef08a; }
    .col-amber   { background: #fed7aa; }
    .col-red     { background: #fecaca; }
    .col-green-1 { background: #bbf7d0; }
    .col-green-2 { background: #dcfce7; }

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

<div class="page-break"></div>

{{-- Page 2: After Hours Breakdown --}}
<div class="section-title">AFTER HOURS DRIVING (18:00 — 05:59)</div>

@php
$afterHrsVehicles = array_filter($vehicles, fn($v) => $v['after_hrs_km'] > 0);
$hourCols = [
    ['key' => '18:00-18:59', 'label' => '18:00-19:00', 'color' => 'col-yellow'],
    ['key' => '19:00-19:59', 'label' => '19:00-20:00', 'color' => 'col-yellow'],
    ['key' => '20:00-20:59', 'label' => '20:00-21:00', 'color' => 'col-amber'],
    ['key' => '21:00-21:59', 'label' => '21:00-22:00', 'color' => 'col-amber'],
    ['key' => '22:00-22:59', 'label' => '22:00-23:00', 'color' => 'col-red'],
    ['key' => '23:00-23:59', 'label' => '23:00-00:00', 'color' => 'col-red'],
    ['key' => '0:00-0:59',   'label' => '00:00-01:00', 'color' => 'col-red'],
    ['key' => '1:00-1:59',   'label' => '01:00-02:00', 'color' => 'col-red'],
    ['key' => '2:00-2:59',   'label' => '02:00-03:00', 'color' => 'col-red'],
    ['key' => '3:00-3:59',   'label' => '03:00-04:00', 'color' => 'col-red'],
    ['key' => '4:00-4:59',   'label' => '04:00-05:00', 'color' => 'col-amber'],
    ['key' => '5:00-5:59',   'label' => '05:00-06:00', 'color' => 'col-yellow'],
];
@endphp

@if(!empty($afterHrsVehicles))
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            <th>LOCATION</th>
            @foreach($hourCols as $col)
            <th style="width:50px; background:#085041; color:white;">{{ substr($col['label'], 0, 5) }}<br>{{ substr($col['label'], 6) }}</th>
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
            <td class="{{ $col['color'] }}" style="{{ $km > 0 ? 'font-weight:bold;' : 'color:#aaa;' }}">{{ $km }}</td>
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
<div class="section-title">SPEEDING INCIDENTS — VEHICLES EXCEEDING {{ $speed_limit }} KM/H</div>

@if(!empty($speeding_detail))
<table>
    <thead>
        <tr>
            <th class="text-left">REG NO.</th>
            <th>LOCATION</th>
            <th>TOP SPEED</th>
            <th class="text-left">LOCATION (ADDRESS)</th>
            <th>TIME</th>
            <th>FREQUENCY OF SPEEDING</th>
        </tr>
    </thead>
    <tbody>
        @foreach($speeding_detail as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            <td>{{ $v['group'] }}</td>
            <td class="danger bold">{{ $v['top_speed'] }}</td>
            <td class="text-left" style="font-size:7px;">{{ $v['address'] }}</td>
            <td>{{ $v['time'] }}</td>
            <td class="danger bold">{{ $v['frequency'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center; color:#666; padding:10px; font-size:8px;">
    ✓ No speeding incidents recorded. All vehicles below {{ $speed_limit }} km/h.
</p>
@endif

{{-- Weekend/Holiday --}}
<div class="section-title">WEEKENDS AND HOLIDAYS</div>

@php $weekendVehicles = array_filter($vehicles, fn($v) => ($v['weekend_total'] ?? 0) > 0); @endphp

@if(!empty($weekendVehicles) && !empty($weekend_dates))
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE ID.</th>
            <th class="text-left">LOCATION</th>
            @foreach($weekend_dates as $date)
            <th style="background:#085041; color:white;">{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}</th>
            @endforeach
            <th>TOTAL WEEKEND MILEAGE</th>
            <th>TOTAL OVERALL MILEAGE</th>
            <th>WEEKEND %</th>
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
            <td class="{{ $greenClass }}" style="{{ $km > 0 ? 'font-weight:bold;' : 'color:#aaa;' }}">{{ $km }}</td>
            @endforeach
            <td class="bold highlight">{{ number_format($v['weekend_total'], 2) }}</td>
            <td class="bold">{{ number_format($v['mileage'], 2) }}</td>
            <td class="{{ $v['mileage'] > 0 && ($v['weekend_total']/$v['mileage']) > 0.3 ? 'danger' : '' }}">
                {{ $v['mileage'] > 0 ? number_format(($v['weekend_total']/$v['mileage'])*100, 1) : '0' }}%
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center; color:#666; padding:10px; font-size:8px;">No weekend or holiday driving recorded.</p>
@endif

@if(!empty($fuel_flat))
<div class="page-break"></div>

{{-- Page 4: Fuel Summary --}}
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
@endif

<div class="report-footer">
    Vehicle Tracking System Report · {{ $client->name }} · Generated {{ $generated_at->format('d M Y H:i') }} · Powered by Bantu Track AFIS
</div>

</body>
</html>