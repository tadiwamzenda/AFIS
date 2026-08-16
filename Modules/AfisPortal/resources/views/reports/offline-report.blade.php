<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Offline Report — {{ $client->name }}</title>
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

@unless($isPdf ?? false)
<div class="print-bar no-print">
    <span style="font-size:13px;font-weight:bold;">Offline Incidents Report — {{ $client->name }} — {{ $totalIncidents }} incidents</span>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <button onclick="window.history.back()" style="background:#fef2f2;color:#dc2626;">✕ Close</button>
    </div>
</div>
@endunless

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
    <h1>{{ $client->name }} — Vehicle Offline Incidents Report</h1>
    <div class="sub">
        {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · Generated {{ \Carbon\Carbon::now('Africa/Harare')->format('d M Y H:i') }}
    </div>
</div>

@if($stateFilter !== 'all' || $durationFilter !== '' || ($commentFilter ?? '') !== '' || $search !== '')
<div class="filter-badges">
    @if($stateFilter !== 'all')<span class="filter-badge">State: {{ ucfirst($stateFilter) }}</span>@endif
    @if($durationFilter !== '')<span class="filter-badge">Duration: {{ ucfirst($durationFilter) }}</span>@endif
    @if(($commentFilter ?? '') !== '')<span class="filter-badge">Comment: {{ $commentFilter }}</span>@endif
    @if($search !== '')<span class="filter-badge">Search: "{{ $search }}"</span>@endif
</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="val">{{ $totalIncidents }}</div><div class="lbl">Total Incidents</div></div>
    <div class="stat-card"><div class="val">{{ $stillOffline }}</div><div class="lbl">Still Offline</div></div>
    <div class="stat-card"><div class="val">{{ $avgDuration }}</div><div class="lbl">Avg Duration</div></div>
    <div class="stat-card"><div class="val">{{ $longestOffline }}</div><div class="lbl">Longest Duration</div></div>
</div>

<div class="section-title">FLEET SUMMARY</div>
<table class="data aspect-table">
    <tr><td>Fleet Size (vehicles)</td><td>{{ $fleetSize }}</td></tr>
    <tr><td>Total Offline Incidents ({{ $client->name }})</td><td>{{ $totalIncidents }}</td></tr>
    <tr><td>Currently Still Offline</td><td>{{ $stillOffline }}</td></tr>
    <tr><td>Average Offline Duration</td><td>{{ $avgDuration }}</td></tr>
    <tr><td>Longest Offline Duration</td><td>{{ $longestOffline }}</td></tr>
</table>

@if($groupBreakdown->isNotEmpty())
<div class="section-title">SUB-GROUP BREAKDOWN</div>
<table class="data">
    <thead>
        <tr><th>GROUP</th><th>TOTAL INCIDENTS</th><th>STILL OFFLINE</th><th>AVG DURATION</th></tr>
    </thead>
    <tbody>
        @foreach($groupBreakdown as $g)
        <tr>
            <td style="font-weight:bold;">{{ $g['group'] }}</td>
            <td>{{ $g['total_incidents'] }}</td>
            <td>{{ $g['still_offline'] }}</td>
            <td>{{ $g['avg_duration'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="incidents-block">
<div class="section-title">OFFLINE INCIDENTS — SORTED BY DURATION (LONGEST FIRST)</div>

@if($incidents->isEmpty())
<p style="text-align:center;color:#999;padding:20px;font-size:9px;">No offline incidents match the current filters for this period.</p>
@else
<table class="data">
    <thead>
        <tr>
            <th>VEHICLE REG</th><th>GROUP / LOCATION</th><th>WENT OFFLINE</th><th>CAME ONLINE</th>
            <th>OFFLINE DURATION</th><th>SEVERITY</th><th>COMMENT</th><th>RESOLUTION</th>
        </tr>
    </thead>
    <tbody>
        @foreach($incidents as $inc)
        <tr>
            <td style="font-weight:bold;">{{ $inc['label'] }}</td>
            <td>{{ $inc['group'] }}</td>
            <td>{{ $inc['went_offline'] }}</td>
            <td class="{{ $inc['came_online'] === 'Still offline' ? 'still-off' : '' }}">{{ $inc['came_online'] }}</td>
            <td style="font-weight:bold;font-family:monospace;">{{ $inc['duration'] }}</td>
            <td><span class="sev-{{ $inc['severity'] }}">{{ strtoupper($inc['severity']) }}</span></td>
            <td>{{ $inc['comment'] ?? '—' }}</td>
            <td class="{{ !$inc['resolution'] ? 'muted' : '' }}">{{ $inc['resolution'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</div>

<div class="footer">
    Bantu Track · Vehicle Offline Incidents Report · {{ $client->name }} · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · {{ $totalIncidents }} incidents
</div>

</body>
</html>