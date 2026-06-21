<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
    h1 { font-size: 14px; color: #085041; margin-bottom: 4px; }
    .meta { font-size: 9px; color: #888; margin-bottom: 12px; }
    .group-header { background: #085041; color: white; padding: 6px 8px; font-weight: bold; font-size: 10px; margin-top: 12px; }
    .device-row { background: #f0f0f0; padding: 4px 8px; font-weight: bold; font-size: 9px; margin-top: 4px; }
    .sim-row { padding: 4px 8px 4px 16px; background: #f9f9f9; font-size: 9px; border-bottom: 1px solid #eee; }
    .accessory-row { padding: 4px 8px 4px 16px; background: #fafafa; font-size: 9px; border-bottom: 1px solid #eee; }
    .label { font-weight: bold; color: #666; }
</style>
</head>
<body>
    <h1>Bantu Track — Assignment Chain</h1>
    <p class="meta">Generated {{ now()->format('d M Y H:i') }} · {{ $groups->count() }} clients</p>

    @foreach($groups as $clientName => $clientData)
    <div class="group-header">{{ $clientName ?: 'Unassigned' }} ({{ $clientData['devices']->count() }} devices)</div>
    
    @foreach($clientData['devices'] as $device)
    <div class="device-row">
        {{ $device->serial_number }} — {{ $device->model ?? '—' }} 
        ({{ $device->status_label }}) | Vehicle: {{ $device->vehicle?->name ?? '—' }}
    </div>
    
    @if($device->sim)
    <div class="sim-row">
        <span class="label">SIM:</span> {{ $device->sim->iccid }} 
        {{ $device->sim->msisdn ?? '—' }} 
        {{ $device->sim->network_provider }} 
        ({{ $device->sim->status_label }})
    </div>
    @else
    <div class="sim-row">
        <span class="label">SIM:</span> No SIM card assigned
    </div>
    @endif
    
    @if($device->accessories && $device->accessories->count())
    <div class="accessory-row">
        <span class="label">Accessories:</span>
        @foreach($device->accessories as $accessory)
        {{ $accessory->type_name ?? '—' }}: {{ $accessory->serial_number }}
        @if(!$loop->last), @endif
        @endforeach
    </div>
    @endif
    @endforeach
    @endforeach
</body>
</html>