<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Notifications Report</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 9px; color: #333; padding: 15mm 12mm; }
    @page { size: A4 landscape; margin: 10mm; }

    .letterhead-table { width:100%; border-collapse:collapse; margin-bottom: 4mm; }
    .letterhead-table td { vertical-align: middle; }
    .letterhead-logo img { height: 40px; }
    .letterhead-contact { text-align: right; font-size: 7px; color: #444; line-height: 1.6; }
    .letterhead-contact img { height: 7px; vertical-align: middle; margin-right: 3px; }
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

    table.data { width:100%; border-collapse:collapse; }
    table.data th { background:#085041; color:white; padding:4px 6px; font-size:8px; text-align:left; }
    table.data td { padding:3px 6px; font-size:8px; border-bottom:1px solid #eee; }
    table.data tr:nth-child(even) td { background:#f9fafb; }

    .sev-info     { background:#dbeafe; color:#1e3a8a; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-warning  { background:#fff3cd; color:#856404; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-severe   { background:#ffe4cc; color:#9a3412; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-critical { background:#f8d7da; color:#721c24; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .status-pending  { color:#b45309; font-weight:bold; }
    .status-attended { color:#15803d; font-weight:bold; }
    .muted { color:#aaa; }

    .incidents-block { page-break-inside: avoid; }
    table.data tr { page-break-inside: avoid; }

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
    <h1>Notifications Report</h1>
    <div class="sub">
        @if($from && $to)
            {{ $from->format('d M Y H:i') }} to {{ $to->format('d M Y H:i') }}
        @else
            No matching notifications
        @endif
        · Generated {{ \Carbon\Carbon::now('Africa/Harare')->format('d M Y H:i') }}
    </div>
</div>

@if($severity !== '' || $status !== '' || ($durationFilter ?? '') !== '' || $client !== '' || $search !== '')
<div class="filter-badges">
    @if($severity !== '')<span class="filter-badge">Severity: {{ ucfirst($severity) }}</span>@endif
    @if($status !== '')<span class="filter-badge">Status: {{ ucfirst($status) }}</span>@endif
    @if(($durationFilter ?? '') !== '')<span class="filter-badge">Duration: {{ ucfirst($durationFilter) }}</span>@endif
    @if($client !== '')<span class="filter-badge">Client: {{ $client }}</span>@endif
    @if($search !== '')<span class="filter-badge">Search: "{{ $search }}"</span>@endif
</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="val">{{ $totalCount }}</div><div class="lbl">Total Notifications</div></div>
    <div class="stat-card"><div class="val">{{ $criticalCount }}</div><div class="lbl">Critical</div></div>
    <div class="stat-card"><div class="val">{{ $pendingCount }}</div><div class="lbl">Pending</div></div>
    <div class="stat-card"><div class="val">{{ $attendedCount }}</div><div class="lbl">Attended</div></div>
</div>

@if($vehicleSummary->isNotEmpty())
<div class="section-title">VEHICLE OFFLINE/ONLINE SUMMARY</div>
<table class="data">
    <thead>
        <tr><th>VEHICLE</th><th>TIMES WENT OFFLINE</th><th>TIMES CAME ONLINE</th></tr>
    </thead>
    <tbody>
        @foreach($vehicleSummary as $v)
        <tr>
            <td style="font-weight:bold;">{{ $v['label'] }}</td>
            <td>{{ $v['went_offline'] }}</td>
            <td>{{ $v['came_online'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(($vehicleSummaryOmitted ?? 0) > 0)
<p style="font-size:7px;color:#999;margin-top:4px;">...and {{ $vehicleSummaryOmitted }} more vehicle(s) not shown — refine filters to narrow the results.</p>
@endif
@endif

<div class="incidents-block">
<div class="section-title">NOTIFICATIONS — MOST RECENT FIRST</div>

@if($notifications->isEmpty())
<p style="text-align:center;color:#999;padding:20px;font-size:9px;">No notifications match the current filters for this period.</p>
@else
<table class="data">
    <thead>
        <tr><th>DATE/TIME</th><th>TITLE</th><th>MESSAGE</th><th>SEVERITY</th><th>STATUS</th><th>MODULE</th></tr>
    </thead>
    <tbody>
        @foreach($notifications as $n)
        <tr>
            <td>{{ $n->created_at->format('d M Y H:i') }}</td>
            <td style="font-weight:bold;">{{ $n->title }}</td>
            <td>{{ $n->message }}</td>
            <td><span class="sev-{{ $n->severity }}">{{ strtoupper($n->severity) }}</span></td>
            <td class="{{ $n->status ? 'status-' . $n->status : 'muted' }}">{{ $n->status ? ucfirst($n->status) : '—' }}</td>
            <td>{{ $n->module }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(($notificationsOmitted ?? 0) > 0)
<p style="font-size:7px;color:#999;margin-top:4px;">...and {{ $notificationsOmitted }} more notification(s) not shown — refine filters to narrow the results.</p>
@endif
@endif
</div>

<div class="footer">
    Bantu Track · Notifications Report · {{ $from ? $from->format('d M Y') : '—' }} to {{ $to ? $to->format('d M Y') : '—' }} · {{ $totalCount }} notifications
</div>

</body>
</html>