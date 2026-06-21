<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
    h1 { font-size: 14px; color: #085041; margin-bottom: 4px; }
    .meta { font-size: 9px; color: #888; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #085041; color: white; text-align: left; padding: 5px 8px; font-size: 9px; }
    td { padding: 4px 8px; border-bottom: 1px solid #f0f0f0; }
    tr:nth-child(even) td { background: #f9f9f9; }
</style>
</head>
<body>
    <h1>Bantu Track — SIM Card Inventory</h1>
    <p class="meta">Generated {{ now()->format('d M Y H:i') }} · {{ $rows->count() }} records</p>
    <table>
        <thead>
            <tr><th>ICCID</th><th>MSISDN</th><th>Provider</th><th>Batch</th><th>Status</th><th>Location</th><th>Client</th></tr>
        </thead>
        <tbody>
            @foreach($rows as $sim)
            <tr>
                <td>{{ $sim->iccid }}</td>
                <td>{{ $sim->msisdn ?? '—' }}</td>
                <td>{{ $sim->network_provider }}</td>
                <td>{{ $sim->batch_code ?? '—' }}</td>
                <td>{{ $sim->status_label }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $sim->location_context)) }}</td>
                <td>{{ $sim->client?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>