<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Komikini') }}</title>
        <meta name="description" content="Platform baca komik manga, manhwa, dan manhua bahasa Indonesia tercepat, terlengkap, dan bebas gangguan.">
        <meta property="og:site_name" content="{{ config('app.name', 'Komikini') }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary">
        @if(app()->environment('staging') || config('app.env') === 'staging')
            <meta name="robots" content="noindex, nofollow">
        @endif

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="shortcut icon" href="/favicon.ico">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-[#1A1A1A] text-[#F8F8F8] min-h-screen">
        @inertia
    </body>
</html>
