<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    @page { margin:15mm 12mm; size:A4 landscape; }
    body { font-family:Arial,sans-serif; font-size:8px; color:#333; }

    .report-header { border-bottom:2px solid #085041; padding-bottom:6px; margin-bottom:8px; display:table; width:100%; }
    .header-left { display:table-cell; vertical-align:middle; }
    .header-right { display:table-cell; text-align:right; vertical-align:middle; font-size:6px; color:#666; }
    .brand { font-size:13px; font-weight:bold; color:#085041; }
    .sub { font-size:6px; color:#666; margin-top:2px; }

    .cover-title { text-align:center; padding:15px 0; }
    .cover-title h1 { font-size:16px; font-weight:bold; color:#c0392b; text-transform:uppercase; margin-bottom:6px; }
    .cover-title h2 { font-size:12px; font-weight:bold; color:#c0392b; margin-bottom:10px; }
    .cover-title .period { font-size:11px; font-weight:bold; color:#c0392b; }

    .section-title { font-size:10px; font-weight:bold; text-align:center; color:#333;
                     border-top:1px solid #ccc; border-bottom:1px solid #ccc;
                     padding:3px 0; margin:10px 0 6px; text-transform:uppercase; }

    .region-header { font-size:11px; font-weight:bold; color:#085041; background:#f0fdf4;
                     border-left:3px solid #085041; padding:5px 8px; margin:8px 0 4px; }

    table { width:100%; border-collapse:collapse; margin-bottom:8px; }
    th { background:#085041; color:white; padding:3px 4px; font-size:7px; text-align:left; }
    td { padding:2px 4px; font-size:7px; border-bottom:1px solid #eee; }
    tr:nth-child(even) td { background:#f9fafb; }
    .text-left { text-align:left; }
    .bold { font-weight:bold; }
    .danger { color:#dc2626; font-weight:bold; }
    .highlight { background:#fef08a !important; }
    .col-yellow { background:#fef08a; }
    .col-amber { background:#fed7aa; }
    .col-red { background:#fecaca; }
    .col-green-1 { background:#bbf7d0; }
    .col-green-2 { background:#dcfce7; }
    .zero { color:#aaa; }

    .page-break { page-break-after:always; }
    .avoid-break { page-break-inside:avoid; }

    .footer { border-top:1px solid #ddd; padding-top:4px; margin-top:8px;
               font-size:6px; color:#999; text-align:center; }

    .summary-grid { display:table; width:100%; margin-bottom:10px; }
    .summary-cell { display:table-cell; text-align:center; padding:8px; background:#f9fafb; border:1px solid #e5e7eb; }
    .summary-cell .val { font-size:14px; font-weight:bold; color:#085041; }
    .summary-cell .lbl { font-size:6px; color:#6b7280; text-transform:uppercase; margin-top:2px; }
</style>
</head>
<body>

{{-- ═══════ PAGE 1: COVER + PERFORMANCE METRICS ════════ --}}
<div class="report-header">
    <div class="header-left">
        <div class="brand">BANTU TRACK</div>
        <div class="sub">Inspired Technologies</div>
    </div>
    <div class="header-right">
        Bantu Track Pvt Ltd · Harare, Zimbabwe<br>
        +263 778 002 318 · operations@bantutrack.com
    </div>
</div>

<div class="cover-title">
    <h1>{{ $client->name }}</h1>
    <h2>CONSOLIDATED VEHICLE TRACKING REPORT</h2>
    <div class="period">
        From: {{ $from->format('d M, Y') }} &nbsp;&nbsp; To: {{ $to->format('d M, Y') }}
    </div>
</div>

{{-- Summary metrics --}}
<div class="section-title">FLEET PERFORMANCE METRICS</div>

<div class="summary-grid">
    <div class="summary-cell">
        <div class="val">{{ $summary['fleet_size'] }}</div>
        <div class="lbl">Fleet Size</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($summary['total_mileage'], 0) }}</div>
        <div class="lbl">Total Mileage (km)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($summary['weekend_km'], 0) }}</div>
        <div class="lbl">Weekend/Holiday (km)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ number_format($summary['after_hrs_km'], 0) }}</div>
        <div class="lbl">After Hours (km)</div>
    </div>
    <div class="summary-cell">
        <div class="val">{{ count($summary['speeding']) }}</div>
        <div class="lbl">Speeding Vehicles</div>
    </div>
</div>

{{-- Parent region breakdown --}}
@if(count($regions) > 1)
<div class="section-title">REGIONAL BREAKDOWN</div>
<table>
    <thead>
        <tr>
            <th class="text-left">REGION</th>
            <th>VEHICLES</th>
            <th>TOTAL MILEAGE (km)</th>
            <th>WEEKEND/HOLIDAY (km)</th>
            <th>AFTER HOURS (km)</th>
            <th>SPEEDING VEHICLES</th>
        </tr>
    </thead>
    <tbody>
        @foreach($regions as $section)
        <tr>
            <td class="text-left bold">{{ $section['region'] }}</td>
            <td class="bold">{{ $section['fleet_size'] }}</td>
            <td>{{ number_format($section['total_mileage'], 0) }}</td>
            <td class="{{ $section['weekend_km'] > 0 ? 'highlight' : '' }}">{{ number_format($section['weekend_km'], 0) }}</td>
            <td class="{{ $section['after_hrs_km'] > 0 ? 'highlight' : '' }}">{{ number_format($section['after_hrs_km'], 0) }}</td>
            <td class="{{ count($section['speeding']) > 0 ? 'danger' : 'zero' }}">{{ count($section['speeding']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $client->name }} · Consolidated Fleet Report · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · Generated {{ $generated_at->format('d M Y H:i') }} · Bantu Track AFIS
</div>

<div class="page-break"></div>

{{-- ═══════ PER-REGION SECTIONS ════════ --}}
@foreach($regions as $section)
@php
$d = $section;
$region = $section['region'];
$hourCols = [
    ['key'=>'18:00-18:59','label'=>'18:00-19:00','color'=>'col-yellow'],
    ['key'=>'19:00-19:59','label'=>'19:00-20:00','color'=>'col-yellow'],
    ['key'=>'20:00-20:59','label'=>'20:00-21:00','color'=>'col-amber'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     ],
    ['key'=>'21:00-21:59','label'=>'21:00-22:00','color'=>'col-amber'],
    ['key'=>'22:00-22:59','label'=>'22:00-23:00','color'=>'col-red'],
    ['key'=>'23:00-23:59','label'=>'23:00-00:00','color'=>'col-red'],
    ['key'=>'0:00-0:59',  'label'=>'00:00-01:00','color'=>'col-red'],
    ['key'=>'1:00-1:59',  'label'=>'01:00-02:00','color'=>'col-red'],
    ['key'=>'2:00-2:59',  'label'=>'02:00-03:00','color'=>'col-red'],
    ['key'=>'3:00-3:59',  'label'=>'03:00-04:00','color'=>'col-red'],
    ['key'=>'4:00-4:59',  'label'=>'04:00-05:00','color'=>'col-amber'],
    ['key'=>'5:00-5:59',  'label'=>'05:00-06:00','color'=>'col-yellow'],
];
$afterHrsVehicles = array_filter($d['vehicles'], fn($v) => $v['after_hrs_km'] > 0);
$weekendVehicles  = array_filter($d['vehicles'], fn($v) => ($v['weekend_total'] ?? 0) > 0);
$speedingVehicles = $d['speeding_detail'] ?? [];
$fuelVehicles     = $d['fuel_flat'] ?? [];
@endphp

{{-- Region header --}}
<div class="report-header">
    <div class="header-left">
        <div class="brand" style="font-size:11px;">BANTU TRACK</div>
    </div>
    <div class="header-right">{{ $client->name }} · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
</div>

<div class="region-header">{{ $region }} — {{ $d['fleet_size'] }} vehicles · {{ number_format($d['total_mileage'], 0) }} km total</div>

{{-- After hours --}}
@if(!empty($afterHrsVehicles))
<div class="section-title" style="margin-top:6px;">AFTER HOURS DRIVING</div>
<div class="avoid-break">
<table>
    <thead>
        <tr>
            <th class="text-left" style="width:80px;">VEHICLE REG</th>
            @foreach($hourCols as $col)
            <th style="background:#085041;">{{ substr($col['label'],0,5) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($afterHrsVehicles as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            @foreach($hourCols as $col)
            @php $km = round($v['hour_breakdown'][$col['key']] ?? 0); @endphp
            <td class="{{ $col['color'] }}" style="{{ $km > 0 ? 'font-weight:bold;' : 'color:#aaa;' }}">{{ $km }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Speeding --}}
@if(!empty($speedingVehicles))
<div class="section-title">SPEEDING INCIDENTS</div>
<div class="avoid-break">
<table>
    <thead>
        <tr>
            <th class="text-left">REG NO.</th>
            <th>TOP SPEED (km/h)</th>
            <th>DATE</th>
            <th>FREQUENCY</th>
        </tr>
    </thead>
    <tbody>
        @foreach($speedingVehicles as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            <td class="danger">{{ number_format($v['top_speed'], 0) }}</td>
            <td>{{ $v['time'] }}</td>
            <td class="danger">{{ $v['frequency'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Weekend --}}
@if(!empty($weekendVehicles) && !empty($d['weekend_dates']))
<div class="section-title">WEEKEND AND HOLIDAY DRIVING</div>
<div class="avoid-break">
<table>
    <thead>
        <tr>
            <th class="text-left">VEHICLE REG</th>
            @foreach($d['weekend_dates'] as $di => $date)
            @php $gc = (intdiv($di,2)%2===0)?'col-green-1':'col-green-2'; @endphp
            <th class="{{ $gc }}" style="color:#333;">{{ \Carbon\Carbon::parse($date)->format('d M') }}</th>
            @endforeach
            <th>TOTAL km</th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>
        @foreach($weekendVehicles as $v)
        <tr>
            <td class="text-left bold">{{ $v['label'] }}</td>
            @foreach($d['weekend_dates'] as $di => $date)
            @php $km = $v['weekend_dates'][$date] ?? 0; $gc=(intdiv($di,2)%2===0)?'col-green-1':'col-green-2'; @endphp
            <td class="{{ $gc }}" style="{{ $km>0?'font-weight:bold;':'color:#aaa;' }}">{{ $km }}</td>
            @endforeach
            <td class="bold highlight">{{ number_format($v['weekend_total'],0) }}</td>
            <td>{{ $v['mileage']>0?number_format(($v['weekend_total']/$v['mileage'])*100,1):0 }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Fuel --}}
@if(!empty($fuelVehicles))
<div class="section-title">FUEL SUMMARY</div>
<div class="avoid-break">
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
        @foreach($fuelVehicles as $fe)
        <tr class="{{ ($fe['type']??'') === 'drain' ? 'background:#ffeaea;' : '' }}">
            <td class="text-left bold {{ ($fe['type']??'') === 'drain' ? 'danger' : '' }}">{{ $fe['label'] }}</td>
            <td>{{ $fe['date'] }}</td>
            <td>{{ $fe['mileage'] ? number_format($fe['mileage'],2) : '—' }}</td>
            <td>{{ $fe['refuels'] }}</td>
            <td>{{ $fe['volume'] ? number_format($fe['volume'],2) : '—' }}</td>
            <td>{{ $fe['consumed'] ? number_format($fe['consumed'],2) : '—' }}</td>
            <td>{{ $fe['rate'] ? number_format($fe['rate'],4) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

<div class="footer">
    {{ $client->name }} · {{ $region }} · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · Bantu Track AFIS
</div>

@if(!$loop->last)
<div class="page-break"></div>
@endif

@endforeach

</body>
</html>