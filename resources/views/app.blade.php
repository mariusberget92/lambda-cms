@php
    $installed = file_exists(storage_path('app/installed'));
    $accentColor = $installed ? \App\Models\Setting::get('site.accent_color') : null;
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor ?? '')) {
        $accentColor = null;
    }
    $hoverMap = [
        '#3898ec' => '#2b7fcc',
        '#22c55e' => '#16a34a',
        '#f59e0b' => '#d97706',
        '#f97316' => '#ea580c',
        '#e05368' => '#c93d52',
        '#8b5cf6' => '#7c3aed',
    ];
    $accentHover = $hoverMap[$accentColor] ?? null;
    $uiDensity = $installed ? \App\Models\Setting::get('site.ui_density') : null;
    $densityClass = match($uiDensity) {
        'compact' => 'density-compact',
        'comfortable' => 'density-comfortable',
        default => '',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $densityClass }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Lambda CMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @routes
@if($accentColor)
<style>
:root {
    --primary: {{ $accentColor }};
    --primary-hover: {{ $accentHover ?? $accentColor }};
    --primary-foreground: #ffffff;
}
</style>
@endif
</head>
<body class="antialiased">
    @inertia
@php $globalCustomJs = $installed ? \App\Models\Setting::get('code.custom_js') : null; @endphp
@if($globalCustomJs)
<script>{!! $globalCustomJs !!}</script>
@endif
</body>
</html>
