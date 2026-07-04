<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.5; margin: 15mm 12mm; }
    @page { margin: 15mm 12mm; }

    .header { padding: 15px 20px; border-bottom: 2px solid #085041; margin-bottom: 20px; }
    .header-inner { display: table; width: 100%; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; text-align: right; vertical-align: top; font-size: 8px; color: #6b7280; }
    .brand { font-size: 16px; font-weight: bold; color: #085041; }
    .tagline { font-size: 8px; color: #6b7280; }

    .report-title { text-align: center; padding: 15px 0; margin-bottom: 15px; }
    .report-title h1 { font-size: 14px; font-weight: bold; color: #085041; text-transform: uppercase; }
    .report-title h2 { font-size: 11px; color: #085041; margin-top: 4px; }
    .report-title .period { font-size: 10px; color: #6b7280; margin-top: 4px; }

    .metrics-bar { display: table; width: 100%; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 10px; margin-bottom: 15px; }
    .metric { display: table-cell; text-align: center; }
    .metric .val { font-size: 14px; font-weight: bold; color: #085041; }
    .metric .lbl { font-size: 7px; color: #6b7280; text-transform: uppercase; }

    .ai-content { padding: 0 5px; }
    .ai-content h3 { font-size: 11px; font-weight: bold; color: #085041; border-bottom: 1px solid #d1fae5; padding-bottom: 3px; margin: 12px 0 6px; text-transform: uppercase; }
    .ai-content p { font-size: 9px; margin-bottom: 6px; color: #374151; }
    .ai-content ul { padding-left: 12px; margin-bottom: 6px; }
    .ai-content li { font-size: 9px; color: #374151; margin-bottom: 2px; }
    .ai-content table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .ai-content th { background: #085041; color: white; padding: 4px 6px; font-size: 8px; }
    .ai-content td { padding: 3px 6px; font-size: 8px; border-bottom: 1px solid #e5e7eb; }
    .ai-content tr:nth-child(even) td { background: #f9fafb; }

    .footer { border-top: 1px solid #e5e7eb; padding: 8px 0; font-size: 7px; color: #9ca3af; text-align: center; margin-top: 20px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-inner">
        <div class="header-left">
            <div class="brand">BANTU TRACK</div>
            <div class="tagline">Inspired Technologies · AI Fleet Intelligence System</div>
        </div>
        <div class="header-right">
            Bantu Track Pvt Ltd · Harare, Zimbabwe<br>
            +263 778 002 318 · operations@bantutrack.com
        </div>
    </div>
</div>

<div class="report-title">
    <h1>{{ $client->name }}</h1>
    <h2>AI FLEET INTELLIGENCE REPORT</h2>
    <div class="period">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }} · Generated {{ $generated_at->format('d M Y H:i') }}</div>
</div>

<div class="metrics-bar">
    <div class="metric"><div class="val">{{ $fleet_size }}</div><div class="lbl">Vehicles</div></div>
    <div class="metric"><div class="val">{{ number_format($total_mileage, 0) }}</div><div class="lbl">Total KM</div></div>
    <div class="metric"><div class="val">{{ number_format($weekend_km, 0) }}</div><div class="lbl">Weekend KM</div></div>
    <div class="metric"><div class="val">{{ number_format($after_hrs_km, 0) }}</div><div class="lbl">After Hours KM</div></div>
    <div class="metric"><div class="val">{{ count(array_filter($vehicles, fn($v) => $v['speeding_trips'] > 0)) }}</div><div class="lbl">Speeding Vehicles</div></div>
</div>

<div class="ai-content">
    {!! nl2br(e($ai_analysis)) !!}
</div>

<div class="footer">
    Bantu Track AFIS · AI Fleet Intelligence Report · {{ $client->name }} · {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}
</div>

</body>
</html>