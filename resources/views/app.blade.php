<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'SIMPEM AWO') }}</title>

        {{-- Favicon: SVG untuk browser modern, ICO sebagai cadangan, PNG untuk layar utama iOS --}}
        <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=1">
        <link rel="icon" href="/favicon.ico?v=1" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=1">
        <meta name="theme-color" content="#08090B">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background: #F2F4F7; color: #16202E; }
        </style>

        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
