<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>AkɔntLite - Professional Invoicing Made Simple</title>

            <!-- <title>{{ config('app.name', 'Laravel') }}</title> -->

        <!--====== SEO Meta Tags ======-->
        <meta name="description" content="AkontLite: Professional Invoicing Made Simple. Boost accuracy, save time & grow your business. Try now!">
        <meta name="keywords" content="invoicing, cloud accounting software, financial management SaaS, automated bookkeeping, small business accounting, real-time financial reports, expense tracking software, invoice management tool, best accounting software, online accounting platform, accounting for startups, tax-ready reports, cash flow management, accounting automation, business finance tools, easy accounting software">

        <!--====== Favicon Icon ======-->
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/png">
        
        <!--====== CSS Files ======-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <style>
            .hover-shadow:hover {
                transform: scale(1.02);
                transition: all 0.2s ease-in-out;
                box-shadow: 0 0.75rem 1.25rem rgba(0, 0, 0, 0.08);
            }

            .icon-box {
                width: 48px;
                height: 48px;
                background-color: rgba(45, 196, 182, 0.1);
                color: #2dc4b6;
            }

            .icon-box svg {
                width: 20px;
                height: 20px;
            }
            /* For primary buttons */
            .btn-primary {
                transition: all 0.3s ease;
            }

            .btn-primary:hover {
                transform: scale(1.05);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            /* For all cards */
            .card {
            transition: all 0.3s ease;
            }

            .card:hover {
            transform: scale(1.03);
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.01);
            }

            :root {
                --bs-primary: #2dc4b6;
                --bs-primary-rgb: 45, 196, 182;
                --bs-primary-faded: rgba(45, 196, 182, 0.1);

                --bs-secondary: #ff8f00;
                --bs-secondary-rgb: 255, 143, 0;
                --bs-secondary-faded: rgba(255, 143, 0, 0.1);
            }

            body {
                background-color: #f5f5f5;
            }

            .btn-primary {
                background-color: var(--bs-primary) !important;
                border-color: var(--bs-primary) !important;
                color: #fff !important;
            }

            .btn-primary:hover {
                background-color: #26b0a4 !important;
                border-color: #26b0a4 !important;
            }

            .btn-outline-primary {
                background-color: var(--bs-primary-faded) !important;
                border-color: var(--bs-primary) !important;
                color: var(--bs-primary) !important;
            }

            .btn-outline-primary:hover {
                background-color: rgba(45, 196, 182, 0.2) !important;
                border-color: var(--bs-primary) !important;
                color: var(--bs-primary) !important;
            }

            .text-primary {
                color: var(--bs-primary) !important;
            }

            .text-menu:hover {
                color: var(--bs-primary) !important;
                border-color: #26b0a4 !important;
            }

            .bg-primary {
                background-color: var(--bs-primary) !important;
            }

            .bg-primary-faded {
                background-color: var(--bs-primary-faded) !important;
            }

            .btn-secondary {
                background-color: var(--bs-secondary) !important;
                border-color: var(--bs-secondary) !important;
                color: #fff !important;
            }

            .btn-secondary:hover {
                background-color: #e67e00 !important;
                border-color: #e67e00 !important;
            }

            .btn-outline-secondary {
                background-color: var(--bs-secondary-faded) !important;
                border-color: var(--bs-secondary) !important;
                color: var(--bs-secondary) !important;
            }

            .btn-outline-secondary:hover {
                background-color: rgba(255, 143, 0, 0.2) !important;
                border-color: var(--bs-secondary) !important;
                color: var(--bs-secondary) !important;
            }

            .text-secondary {
                color: var(--bs-secondary) !important;
            }

            .bg-secondary {
                background-color: var(--bs-secondary) !important;
            }

            .bg-secondary-faded {
                background-color: var(--bs-secondary-faded) !important;
            }

            .btn-outline-danger {
                color: #dc3545;
                border: 1px solid #dc3545;
                background-color: transparent;
            }

            .btn-outline-danger:hover {
                background-color: var(--bs-primary) !important;
                border-color: var(--bs-primary) !important;
                color: #fff !important;
            }

            .shadow-sm {
                box-shadow: 0 .125rem .25rem rgba(255, 143, 0, 0.15) !important;
            }

            .shadow {
                box-shadow: 0 .5rem 1rem rgba(255, 143, 0, 0.15) !important;
            }

            .shadow-lg {
                box-shadow: 0 1rem 3rem rgba(255, 143, 0, 0.2) !important;
            }


        </style>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('js/app.js') }}" defer></script>

    </head>
    <!-- <body class="font-sans antialiased"> -->
    <body class="font-sans antialiased" data-auth="{{ auth()->check() ? 'true' : 'false' }}">
        <div class="min-h-screen d-flex flex-column justify-between bg-gray-100">

            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
            
        </div>
    </body>
</html>
