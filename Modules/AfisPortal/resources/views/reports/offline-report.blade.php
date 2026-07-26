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
    .header { border-bottom:2px solid #085041; padding-bottom:8px; margin-bottom:12px;
               display:flex; justify-content:space-between; align-items:center; }
    .brand { font-size:14px; font-weight:bold; color:#085041; }
    .sub { font-size:7px; color:#666; margin-top:2px; }
    .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; }
    .summary-card { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:10px; text-align:center; }
    .summary-card .val { font-size:20px; font-weight:bold; color:#085041; }
    .summary-card .lbl { font-size:7px; color:#888; margin-top:2px; text-transform:uppercase; }
    .section-title { font-size:10px; font-weight:bold; text-align:center; color:#333;
                      border-top:1px solid #ccc; border-bottom:1px solid #ccc;
                      padding:3px 0; margin:10px 0 6px; text-transform:uppercase; }
    table { width:100%; border-collapse:collapse; }
    th { background:#085041; color:white; padding:4px 6px; font-size:8px; text-align:left; }
    td { padding:3px 6px; font-size:8px; border-bottom:1px solid #eee; }
    tr:nth-child(even) td { background:#f9fafb; }
    .sev-low    { background:#d4edda; color:#155724; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-medium { background:#fff3cd; color:#856404; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .sev-high   { background:#f8d7da; color:#721c24; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:7px; }
    .still-off  { color:#dc2626; font-weight:bold; }
    .footer { border-top:1px solid #ddd; padding-top:5px; margin-top:10px;
               font-size:6.5px; color:#999; text-align:center; }
</style>
</head>
<body>

<div class="print-bar no-print">
    <span style="font-size:13px;font-weight:bold;">Offline Incidents Report — {{ $client->name }} — {{ $totalIncidents }} incidents</span>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <button onclick="window.history.back()" style="background:#fef2f2;color:#dc2626;">✕ Close</button>
    </div>
</div>

<div class="header">
    <div>
        <div class="brand">BANTU TRACK — Vehicle Offline Incidents Report</div>
        <div class="sub">{{ $client->name }} &nbsp;·&nbsp; {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y H:i') }}</div>
    </div>
    <div style="font-size:7px;color:#666;text-align:right;">Bantu Track Pvt Ltd · Harare, Zimbabwe</div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <div class="val">{{ $totalIncidents }}</div>
        <div class="lbl">Total offline incidents</div>
    </div>
    <div class="summary-card">
        <div class="val" style="color:#dc2626;">{{ $stillOffline }}</div>
        <div class="lbl">Currently still offline</div>
    </div>
    <div class="summary-card">
        <div class="val">{{ $avgDuration }}</div>
        <div class="lbl">Average offline duration</div>
    </div>
    <div class="summary-card">
        <div class="val" style="color:#dc2626;">{{ $longestOffline }}</div>
        <div class="lbl">Longest offline duration</div>
    </div>
</div>

<div class="section-title">OFFLINE INCIDENTS — SORTED BY DURATION (LONGEST FIRST)</div>

@if(empty($incidents))
<p style="text-align:center;color:#999;padding:20px;font-size:9px;">No offline incidents recorded in this period.</p>
@else
<table>
    <thead>
        <tr>
            <th>VEHICLE REG</th>
            <th>GROUP / LOCATION</th>
            <th>WENT OFFLINE</th>
            <th>CAME ONLINE</th>
            <th>OFFLINE DURATION</th>
            <th>SEVERITY</th>
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
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Bantu Track · Vehicle Offline Incidents Report · {{ $client->name }} · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} · {{ $totalIncidents }} incidents
</div>

</body>
</html>