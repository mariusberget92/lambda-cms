<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unsubscribed — {{ \App\Models\Setting::get('site.name', config('app.name')) }}</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f4f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 40px; max-width: 420px; width: 100%; text-align: center; }
    h1 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 12px; }
    p { font-size: 0.875rem; color: #6b7280; line-height: 1.6; }
    a { color: #2563eb; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Unsubscribed</h1>
    <p>You have been successfully unsubscribed and will no longer receive emails from us.</p>
    <p style="margin-top: 20px;"><a href="{{ url('/') }}">Return to website</a></p>
  </div>
</body>
</html>
