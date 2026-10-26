<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="canonical" href="<?php echo e(url()->current()); ?>">

    <!-- Favicon & App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Structured Data (Example: JSON-LD Schema.org) -->
    <script type="application/ld+json">
        
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Nova",
            "description": "Nova is a voice AI assistant for Mac and Windows: talk to search the web, write code, design UIs for Figma and control your computer — in English, Urdu and Hindi.",
            "applicationCategory": "ProductivityApplication",
            "operatingSystem": "macOS, Windows",
            "offers": {
                "@type": "Offer",
                "price": "12",
                "priceCurrency": "USD"
            }
        }
        
    </script>

    <!-- Scripts -->
    <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/pages/{$page['component']}.vue"]); ?>
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>

    
    <?php if(config('cashier.client_side_token')): ?>
        <?php echo view("cashier::js", ["nonce" => []["nonce"] ?? ""]); ?>
    <?php endif; ?>
</head>

<body class="font-sans antialiased">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
</body>

</html>
<?php /**PATH /Users/4star/Projects/nova-web/resources/views/app.blade.php ENDPATH**/ ?>