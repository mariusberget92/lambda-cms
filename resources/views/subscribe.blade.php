<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Subscribe — {{ \App\Models\Setting::get('site.name', config('app.name')) }}</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f4f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 40px; max-width: 420px; width: 100%; }
    h1 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 8px; }
    .subtitle { font-size: 0.875rem; color: #6b7280; margin-bottom: 24px; }
    label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 6px; }
    input[type="email"], input[type="text"] { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; outline: none; transition: border-color 0.15s; }
    input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .field { margin-bottom: 16px; }
    button { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background 0.15s; }
    button:hover { background: #1d4ed8; }
    .alert { padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; margin-bottom: 20px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Subscribe</h1>
    <p class="subtitle">Get the latest updates delivered to your inbox.</p>

    @if(session('subscribe_status'))
      <div class="alert alert-success">{{ session('subscribe_status') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('subscribe') }}">
      @csrf
      <div class="field">
        <label for="name">Name (optional)</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Your name">
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
      </div>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</body>
</html>
