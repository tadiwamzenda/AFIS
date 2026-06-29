<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #085041; color: white; padding: 24px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0 0; font-size: 12px; opacity: 0.8; }
        .body { padding: 24px; }
        .severity { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; margin-bottom: 16px; }
        .severity-info { background: #EFF6FF; color: #1D4ED8; }
        .severity-warning { background: #FFFBEB; color: #D97706; }
        .severity-critical { background: #FEF2F2; color: #DC2626; }
        .message { font-size: 14px; line-height: 1.6; color: #4B5563; }
        .footer { background: #F9FAFB; padding: 16px 24px; border-top: 1px solid #E5E7EB; font-size: 12px; color: #9CA3AF; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bantu Track — AFIS Alert</h1>
            <p>Automated notification from {{ $notification->module }}</p>
        </div>
        <div class="body">
            <span class="severity severity-{{ $notification->severity }}">
                {{ ucfirst($notification->severity) }}
            </span>
            <h2 style="margin: 0 0 12px; font-size: 16px; color: #111827;">{{ $notification->title }}</h2>
            <p class="message">{{ $notification->message }}</p>
            @if($notification->data)
            <div style="margin-top: 16px; background: #F9FAFB; border-radius: 6px; padding: 12px; font-size: 12px; color: #6B7280;">
                <strong>Details:</strong><br>
                @foreach($notification->data as $key => $value)
                    {{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value }}<br>
                @endforeach
            </div>
            @endif
        </div>
        <div class="footer">
            Sent {{ now()->format('d M Y H:i') }} · Bantu Track Fleet Management System
        </div>
    </div>
</body>
</html>