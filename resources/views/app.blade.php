<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Lambda CMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @routes
@php
    $accentColor = \App\Models\Setting::get('site.accent_color');
    $hoverMap = [
        '#5e81ac' => '#4a6d92',
        '#a3be8c' => '#8aaa70',
        '#ebcb8b' => '#d4b06a',
        '#d08770' => '#bb6f58',
        '#bf616a' => '#a84d56',
        '#b48ead' => '#9d7596',
    ];
    $accentHover = $hoverMap[$accentColor] ?? null;
@endphp
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
</body>
</html>
