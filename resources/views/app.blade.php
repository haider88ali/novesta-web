<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon & App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Structured Data (Example: JSON-LD Schema.org) -->
    <script type="application/ld+json">
        @verbatim
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Nova",
            "description": "Nova is a voice AI assistant for Mac and Windows: talk to search the web, write code, design UIs for Figma and control your computer — in 18 languages, including English, Spanish, Arabic, Urdu, Hindi and Chinese.",
            "applicationCategory": "ProductivityApplication",
            "operatingSystem": "macOS, Windows",
            "offers": {
                "@type": "Offer",
                "price": "12",
                "priceCurrency": "USD"
            }
        }
        @endverbatim
    </script>

    <!-- Urdu text on the landing page -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600&display=swap">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead

    {{-- Paddle.js for the checkout overlay (only once Paddle keys are configured) --}}
    @if (config('cashier.client_side_token'))
        @paddleJS
    @endif
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
