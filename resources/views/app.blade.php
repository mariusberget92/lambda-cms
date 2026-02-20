<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Lambda CMS') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @routes
</head>
<body class="antialiased">
    @inertia
</body>
</html>
