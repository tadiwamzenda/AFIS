<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
    h1 { font-size: 14px; color: #085041; margin-bottom: 4px; }
    .meta { font-size: 9px; color: #888; margin-bottom: 12px; }
    .section-title { background: #085041; color: white; padding: 6px 8px; font-weight: bold; font-size: 11px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    th { background: #f0f0f0; text-align: left; padding: 4px 8px; font-size: 9px; }
    td { padding: 4px 8px; border-bottom: 1px solid #f0f0f0; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .count { font-size: 9px; color: #888; }
</style>
</head>
<body>
    <h1>Bantu Track — Internal Stock</h1>
    <p class="meta">Generated {{ now()->format('d M Y H:i') }}</p>

    <!-- SIM Cards in Stock -->
    <div class="section-title">SIM Cards in Stock ({{ $sims->count() }})</div>
    <table>
        <thead>
            <tr><th>ICCID</th><th>MSISDN</th><th>Provider</th><th>Batch</th><th>Type</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($sims as $sim)
            <tr>
                <td>{{ $sim->iccid }}</td>
                <td>{{ $sim->msisdn ?? '—' }}</td>
                <td>{{ $sim->network_provider }}</td>
                <td>{{ $sim->batch_code ?? '—' }}</td>
                <td>{{ $sim->bundle_type ?? '—' }}</td>
                <td>{{ $sim->status_label }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#999; padding:8px;">No SIM cards in stock</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Devices in Stock -->
    <div class="section-title">Devices in Stock ({{ $devices->count() }})</div>
    <table>
        <thead>
            <tr><th>Serial Number</th><th>Model</th><th>Firmware</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
            <tr>
                <td>{{ $device->serial_number }}</td>
                <td>{{ $device->model ?? '—' }}</td>
                <td>{{ $device->firmware_version ?? '—' }}</td>
                <td>{{ $device->status_label }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; color:#999; padding:8px;">No devices in stock</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Accessories in Stock -->
    <div class="section-title">Accessories in Stock ({{ $accessories->count() }})</div>
    <table>
        <thead>
            <tr><th>Type</th><th>Serial Number</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($accessories as $accessory)
            <tr>
                <td>{{ $accessory->type_name ?? '—' }}</td>
                <td>{{ $accessory->serial_number }}</td>
                <td>{{ $accessory->status_label }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center; color:#999; padding:8px;">No accessories in stock</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>