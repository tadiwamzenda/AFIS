<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
    h1 { font-size: 14px; color: #085041; margin-bottom: 4px; }
    .meta { font-size: 9px; color: #888; margin-bottom: 12px; }
    .filters { font-size: 9px; color: #555; margin-bottom: 12px; padding: 6px 8px; background: #f9f9f9; border: 1px solid #eee; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #085041; color: white; text-align: left; padding: 4px 6px; font-size: 8px; }
    td { padding: 4px 6px; border-bottom: 1px solid #f0f0f0; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .event-created { color: #2d7d46; }
    .event-updated { color: #1a6ea8; }
    .event-deleted { color: #b33c3c; }
</style>
</head>
<body>
    <h1>Bantu Track — Audit Trail</h1>
    <p class="meta">Generated {{ now()->format('d M Y H:i') }} · {{ $rows->count() }} records</p>

    @if(request('from_date') || request('to_date') || request('user') || request('module'))
    <div class="filters">
        <strong>Filters:</strong>
        @if(request('from_date')) From: {{ request('from_date') }} @endif
        @if(request('to_date')) To: {{ request('to_date') }} @endif
        @if(request('user')) User: {{ request('user') }} @endif
        @if(request('module')) Module: {{ request('module') }} @endif
    </div>
    @endif

    <table>
        <thead>
            <tr><th>Date</th><th>User</th><th>Module</th><th>Event</th><th>Entity</th><th>Data</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $audit)
            <tr>
                <td>{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $audit->user?->name ?? 'System' }}</td>
                <td>{{ $audit->module ?? '—' }}</td>
                <td class="event-{{ $audit->event }}">{{ $audit->event }}</td>
                <td>{{ $audit->entity_type ?? '—' }} #{{ $audit->entity_id ?? '—' }}</td>
                <td style="font-size:8px; max-width:150px; word-break:break-all;">{{ $audit->data ? json_encode($audit->data) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#999; padding:12px;">No audit records found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>