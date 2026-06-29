<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>InvoiceLite - Professional Invoicing</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles -->
    @auth
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- For guests, use fallback CSS --}}
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endauth
</head>
<body class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20">
    {{ $slot }}
</body>
</html>