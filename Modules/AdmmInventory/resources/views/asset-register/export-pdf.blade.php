<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Asset Register — {{ $generated }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 6.5px; color: #333; padding: 10mm; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { padding: 0; }
        .no-print { display: none !important; }
    }

    .header { border-bottom: 2px solid #085041; padding-bottom: 6px; margin-bottom: 8px;
               display: flex; justify-content: space-between; align-items: center; }
    .brand { font-size: 13px; font-weight: bold; color: #085041; }
    .sub { font-size: 6px; color: #666; margin-top: 2px; }
    .contact { font-size: 6px; color: #666; text-align: right; }
    .filters { font-size: 6.5px; color: #666; margin-bottom: 6px; }

    table { width: 100%; border-collapse: collapse; font-size: 6px; }
    th { background: #1a5c4a; color: white; padding: 3px 4px; text-align: left; }
    td { padding: 2px 4px; border-bottom: 1px solid #eee; }
    tr:nth-child(even) td { background: #f9fafb; }
    .green { color: #15803d; font-weight: bold; }
    .blue  { color: #1d4ed8; font-weight: bold; }
    .red   { color: #dc2626; font-weight: bold; }

    .footer { border-top: 1px solid #ddd; padding-top: 4px; margin-top: 8px;
               font-size: 6px; color: #999; text-align: center; }

    /* Print button */
    .print-bar { background: #085041; color: white; padding: 10px 20px;
                  display: flex; align-items: center; justify-content: space-between;
                  margin-bottom: 15px; border-radius: 6px; }
    .print-bar button { background: white; color: #085041; border: none;
                         padding: 6px 16px; border-radius: 4px; font-size: 12px;
                         font-weight: bold; cursor: pointer; }
    .print-bar button:hover { background: #f0fdf4; }
</style>
</head>
<body>

<div class="print-bar no-print">
    <span style="font-size:13px; font-weight:bold;">Asset Register — {{ $totalCount }} records</span>
    <div style="display:flex; gap:10px;">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <button onclick="window.history.back()" style="background:#fef2f2; color:#dc2626;">✕ Close</button>
    </div>
</div>

<div class="header">
    <div>
        <div class="brand">BANTU TRACK — Asset Register</div>
        <div class="sub">{{ $totalCount }} records · Generated {{ $generated }}</div>
    </div>
    <div class="contact">Bantu Track Pvt Ltd · Harare, Zimbabwe</div>
</div>

@if(!empty($filters))
<div class="filters"><strong>Filters:</strong> {{ implode(' · ', $filters) }}</div>
@endif

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Install Date</th>
            <th>Client</th>
            <th>Vehicle Reg</th>
            <th>Fleet No</th>
            <th>Vehicle Make</th>
            <th>IMEI</th>
            <th>Device Name</th>
            <th>Device Type</th>
            <th>Config</th>
            <th>State</th>
            <th>SIM Serial</th>
            <th>SIM Phone</th>
            <th>SIM Type</th>
            <th>ISP</th>
            <th>Location</th>
            <th>Technician</th>
            <th>Comment</th>
            <th>Contact Name</th>
            <th>Contact Phone</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $r->installation_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $r->client ?? '—' }}</td>
            <td>{{ $r->vehicle_reg_no ?? '—' }}</td>
            <td>{{ $r->vehicle_fleet_no ?? '—' }}</td>
            <td>{{ $r->vehicle_make ?? '—' }}</td>
            <td>{{ $r->gps_device_imei ?? '—' }}</td>
            <td>{{ $r->gps_device_name ?? '—' }}</td>
            <td>{{ $r->gps_device_type ?? '—' }}</td>
            <td>{{ $r->configuration ?? '—' }}</td>
            <td class="{{ $r->gps_device_state === 'ACTIVE' ? 'green' : ($r->gps_device_state === 'MALFUNCTION' ? 'red' : '') }}">
                {{ $r->gps_device_state ?? '—' }}
            </td>
            <td>{{ $r->sim_card_serial_no ?? '—' }}</td>
            <td>{{ $r->sim_card_phone_no ?? '—' }}</td>
            <td>{{ $r->sim_card_type ?? '—' }}</td>
            <td>{{ $r->sim_card_isp ?? '—' }}</td>
            <td class="{{ $r->location === 'CLIENT' ? 'green' : ($r->location === 'LOST' ? 'red' : 'blue') }}">
                {{ $r->location ?? '—' }}
            </td>
            <td>{{ $r->technician ?? '—' }}</td>
            <td>{{ Str::limit($r->comment ?? '', 15) }}</td>
            <td>{{ $r->client_name ?? '—' }}</td>
            <td>{{ $r->client_contact ?? '—' }}</td>
            <td>{{ $r->client_email ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Bantu Track Asset Register · {{ $totalCount }} records · {{ $generated }}
    @if(!empty($filters)) · Filters: {{ implode(', ', $filters) }} @endif
</div>

</body>
</html>