<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') — Bantu Track</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 48px 40px;
            max-width: 440px;
            width: 100%;
            text-align: center;
        }
        .brand { font-size: 13px; font-weight: 700; color: #085041; letter-spacing: 0.5px; margin-bottom: 32px; }
        .code { font-size: 56px; font-weight: 800; color: #085041; line-height: 1; margin-bottom: 12px; }
        .heading { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 8px; }
        .message { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 28px; }
        .btn {
            display: inline-block;
            background: #085041;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .btn:hover { background: #063d32; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">BANTU TRACK</div>
        @yield('content')
    </div>
</body>
</html>