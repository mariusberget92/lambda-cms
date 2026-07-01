<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
    .card { background: #ffffff; border-radius: 8px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    .card p { margin: 0 0 16px; line-height: 1.6; color: #374151; font-size: 15px; }
    .card blockquote { border-left: 3px solid #d1d5db; margin: 12px 0; padding: 8px 16px; color: #6b7280; font-style: italic; }
    .card a { color: #2563eb; }
    .card h2, .card h3 { color: #111827; margin: 0 0 12px; }
    .footer { text-align: center; padding: 24px 0 0; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      @yield('content')
    </div>
    <div class="footer">
      @yield('footer')
    </div>
  </div>
</body>
</html>
