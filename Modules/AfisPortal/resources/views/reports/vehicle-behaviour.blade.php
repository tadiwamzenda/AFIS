<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.5; margin: 34mm 12mm 20mm 12mm; }
    @page { margin: 34mm 12mm 20mm 12mm; }

    .letterhead-header { position: fixed; top: -28mm; left: -12mm; right: -12mm; height: 26mm; padding: 6mm 12mm 3mm 12mm; }
    .letterhead-table { width: 100%; border-collapse: collapse; }
    .letterhead-table td { vertical-align: middle; }
    .letterhead-logo img { height: 48px; }
    .letterhead-contact { text-align: right; font-size: 8px; color: #444; line-height: 1.6; }
    .letterhead-contact img { height: 8px; vertical-align: middle; margin-right: 3px; }
    .letterhead-rule { border-bottom: 2px solid #663701; margin-top: 4mm; }

    .letterhead-footer { position: fixed; bottom: -16mm; left: -12mm; right: -12mm; height: 14mm; text-align: center; padding-top: 4mm; }
    .letterhead-footer img { height: 12px; }
    .letterhead-footer .meta { font-size: 7px; color: #9ca3af; margin-top: 2px; }

    .watermark { position: fixed; top: 0; left: 0; right: 0; text-align: center; }
    .watermark img { width: 380px; margin-top: 401px; }

    .report-title { text-align: center; padding: 4px 0 12px; }
    .report-title h1 { font-size: 14px; font-weight: bold; color: #085041; text-transform: uppercase; }
    .report-title h2 { font-size: 11px; color: #085041; margin-top: 4px; }
    .report-title .period { font-size: 9px; color: #6b7280; margin-top: 4px; }

    .stat-cards { display: table; width: 100%; margin-bottom: 14px; }
    .stat-card { display: table-cell; width: 33.33%; background: #085041; padding: 10px 8px; text-align: center; }
    .stat-card + .stat-card { border-left: 1px solid rgba(255,255,255,0.25); }
    .stat-card .val { font-size: 18px; font-weight: bold; color: #ffffff; }
    .stat-card .lbl { font-size: 7px; color: #d1fae5; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

    .ai-content h2 { font-size: 12px; font-weight: bold; color: #085041; text-transform: uppercase; border-bottom: 1px solid #d1fae5; padding-bottom: 3px; margin: 14px 0 6px; }
    .ai-content p { font-size: 9px; margin-bottom: 6px; color: #374151; }
    .ai-content ul, .ai-content ol { padding-left: 14px; margin-bottom: 8px; }
    .ai-content li { font-size: 9px; color: #374151; margin-bottom: 3px; }
    .ai-content strong { color: #1f2937; }
</style>
</head>
<body>

@php
    $brandingPath = base_path('Modules/AfisPortal/resources/branding');
    $b64 = fn($file) => file_exists("{$brandingPath}/{$file}")
        ? 'data:image/png;base64,' . base64_encode(file_get_contents("{$brandingPath}/{$file}"))
        : '';
@endphp

<div class="watermark"><img src="{{ $b64('watermark-full-diagonal.png') }}"></div>

<div class="letterhead-header">
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
</div>

<div class="letterhead-footer">
    <img src="{{ $b64('footer.png') }}">
    <div class="meta">Prepared by: Bantu Track Fleet Intelligence Division · {{ $client?->name }} · Generated {{ $generated_at->format('d M Y H:i') }} · Confidential</div>
</div>

<div class="report-title">
    <h1>{{ $tracker->label }}</h1>
    <h2>VEHICLE BEHAVIOUR REPORT</h2>
    <div class="period">{{ $client?->name }} · Last {{ $days }} days</div>
</div>

<div class="ai-content">
    {!! $content_html !!}
</div>

</body>
</html>