<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Fleet States Report — All Clients</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 9px; color: #333; padding: 15mm 12mm; }
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { padding: 0; }
        .no-print { display: none !important; }
    }

    .print-bar { background:#085041; color:white; padding:10px 20px; display:flex;
                  align-items:center; justify-content:space-between; margin-bottom:15px; border-radius:6px; }
    .print-bar button { background:white; color:#085041; border:none; padding:6px 16px;
                         border-radius:4px; font-size:12px; font-weight:bold; cursor:pointer; }

    .letterhead-table { width:100%; border-collapse:collapse; margin-bottom: 4mm; }
    .letterhead-table td { vertical-align: middle; }
    .letterhead-logo img { height: 80px; }
    .letterhead-contact { text-align: right; font-size: 9px; color: #444; line-height: 1.6; }
    .letterhead-contact img { height: 8px; vertical-align: middle; margin-right: 3px; }
    .letterhead-rule { border-bottom: 2px solid #663701; margin-bottom: 4mm; }

    .report-title { text-align: center; padding: 2px 0 10px; }
    .report-title h1 { font-size: 13px; font-weight: bold; color: #085041; text-transform: uppercase; }
    .report-title .sub { font-size: 8px; color: #666; margin-top: 3px; }

    .filter-badges { margin-bottom:10px; text-align: center; }
    .filter-badge { display:inline-block; background:#f0fdf4; border:1px solid #bbf7d0; color:#085041;
                     font-size:7px; font-weight:bold; padding:3px 8px; border-radius:10px; margin-right:6px; }

    .stat-cards { display: table; width: 100%; margin-bottom: 12px; }
    .stat-card { display: table-cell; width: 25%; background: #085041; padding: 8px 6px; text-align: center; }
    .stat-card + .stat-card { border-left: 1px solid rgba(255,255,255,0.25); }
    .stat-card .val { font-size: 16px; font-weight: bold; color: #ffffff; }
    .stat-card .lbl { font-size: 6.5px; color: #d1fae5; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

    .section-title { font-size:10px; font-weight:bold; text-align:center; color:#333;
                      border-top:1px solid #ccc; border-bottom:1px solid #ccc;
                      padding:3px 0; margin:10px 0 6px; text-transform:uppercase; }

    /* Keeps the incidents section's heading attached to its table, and
       forces the whole block onto a fresh page if it doesn't fit under
       the Fleet Summary / Sub-Group tables above it — same technique
       already used for this in the Standard Report. */
    .incidents-block { page-break-inside: avoid; }
    table.data tr { page-break-inside: avoid; }

    table.data { width:100%; border-collapse:collapse; }
    table.data th { background:#085041; color:white; padding:4px 6px; font-size:8px; text-align:left; }
    table.data td { padding:3px 6px; font-size:8px; border-bottom:1px solid #eee; }
    table.data tr:nth-child(even) td { background:#f9fafb; }

    .aspect-table td:first-child { font-weight: bold; color: #085041; width: 40%; }

    .sev-low    { background:#d4edda; color:#155724; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-medium { background:#fff3cd; color:#856404; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-high   { background:#f8d7da; color:#721c24; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .still-off  { color:#dc2626; font-weight:bold; }
    .muted      { color:#aaa; }

    .footer { border-top:1px solid #ddd; padding-top:5px; margin-top:10px;
               font-size:6.5px; color:#999; text-align:center; }
</style>
</head>
<body>

@php
    $brandingPath = base_path('Modules/AfisPortal/resources/branding');
    $b64 = fn($file) => file_exists("{$brandingPath}/{$file}")
        ? 'data:image/png;base64,' . base64_encode(file_get_contents("{$brandingPath}/{$file}"))
        : '';
@endphp

<table class="letterhead-table">
    <tr>
        <td class="letterhead-logo"><img src="{{ $b64('logo-header.png') }}"></td>
        <td class="letterhead-contact">
            <div><img src="{{ $b64('icon-phone.png') }}">+263 242 702 509</div>
            <div><img src="{{ $b64('icon-phone.png') }}">+263 778 002 318</div>
            <div><img src="{{ $b64('icon-envelope.png') }}">operations@bantutrack.co.zw</div>
            <div><img src="{{ $b64('icon-pin.png') }}">10 Cherry Tree, Avonlea, Harare</div>
        </td>
    </tr>
</table>
<div class="letterhead-rule"></div>

<div class="report-title">
    <h1>All Clients — Fleet States Report ({{ $periodLabel }})</h1>
    <div class="sub">
        {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · Generated {{ \Carbon\Carbon::now('Africa/Harare')->format('d M Y H:i') }}
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="val">{{ number_format($totals['total']) }}</div><div class="lbl">Fleet Total</div></div>
    <div class="stat-card"><div class="val">{{ number_format($totals['online']) }}</div><div class="lbl">Online</div></div>
    <div class="stat-card"><div class="val">{{ number_format($totals['offline']) }}</div><div class="lbl">Offline (Functional &amp; Bantu Track)</div></div>
    <div class="stat-card"><div class="val">{{ number_format($totals['excluded']) }}</div><div class="lbl">Offline not counted</div></div>
</div>

<div class="section-title">CLIENT SUMMARY — INCIDENTS {{ strtoupper($from->format('d M Y')) }} TO {{ strtoupper($to->format('d M Y')) }}: {{ number_format($incidentsTotal) }}</div>
<table class="data">
    <thead>
        <tr>
            <th>CLIENT</th><th>FLEET</th><th>ONLINE</th><th>OFFLINE</th><th>NOT COUNTED</th>
            <th>INCIDENTS IN PERIOD</th><th>AVG DURATION</th><th>LONGEST DURATION</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        <tr>
            <td style="font-weight:bold;">{{ $r['client'] }}</td>
            <td>{{ $r['total'] }}</td>
            <td>{{ $r['online'] }}</td>
            <td class="{{ $r['offline'] > 0 ? 'still-off' : '' }}">{{ $r['offline'] }}</td>
            <td>{{ $r['excluded'] }}</td>
            <td>{{ $r['incidents'] }}</td>
            <td>{{ $r['avg'] }}</td>
            <td>{{ $r['longest'] }}</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td>TOTAL</td>
            <td>{{ $totals['total'] }}</td><td>{{ $totals['online'] }}</td><td>{{ $totals['offline'] }}</td>
            <td>{{ $totals['excluded'] }}</td><td>{{ $incidentsTotal }}</td><td></td><td></td>
        </tr>
    </tbody>
</table>

<div class="incidents-block">
<div class="section-title">VEHICLES OFFLINE NOW — COUNTED FIRST, THEN LONGEST OFFLINE</div>
@if($vehicles->isEmpty())
<p style="text-align:center;color:#999;padding:20px;font-size:9px;">No vehicles are offline right now.</p>
@else
<table class="data">
    <thead>
        <tr>
            <th>CLIENT</th><th>VEHICLE REG</th><th>GROUP / LOCATION</th><th>OFFLINE SINCE</th>
            <th>DURATION</th><th>COMMENT</th><th>ACTION PARTY</th><th>UPDATED</th><th>COUNTED</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vehicles as $v)
        <tr>
            <td>{{ $v['client'] }}</td>
            <td style="font-weight:bold;">{{ $v['label'] }}</td>
            <td>{{ $v['group'] }}</td>
            <td>{{ $v['since'] }}</td>
            <td style="font-weight:bold;font-family:monospace;">{{ $v['duration'] }}</td>
            <td>{{ $v['comment'] }}</td>
            <td>{{ $v['action_party'] }}</td>
            <td class="{{ empty($v['updated_at']) ? 'muted' : '' }}">{{ $v['updated_at'] ?? '—' }}</td>
            <td>{{ $v['counted'] ? 'Yes' : 'No' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</div>

<div class="footer">
    Bantu Track · Fleet States Report · All Clients · {{ $periodLabel }} {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}
</div>

</body>
</html>
