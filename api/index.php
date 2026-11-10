<?php

// Vercel entry point for the website demo (vercel.json sends every non-static request here).
// Vercel's filesystem is read-only except /tmp, so Laravel's storage lives there.
// The database is Neon Postgres (DATABASE_URL, added by the Vercel integration).

$storage = '/tmp/storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/public'] as $dir) {
    if (! is_dir("$storage/$dir")) {
        mkdir("$storage/$dir", 0755, true);
    }
}
$_ENV['LARAVEL_STORAGE_PATH'] = $storage;
putenv("LARAVEL_STORAGE_PATH=$storage");

// Vercel can't run artisan at deploy time, so each new instance applies pending migrations once
// (a no-op after the first time; the marker keeps it to one check per instance)
$migrated = '/tmp/.migrated';
if (! file_exists($migrated)) {
    // Schema changes go over Neon's direct connection: its pooler breaks migration transactions
    $direct = getenv('DATABASE_URL_UNPOOLED');
    $env = $direct ? 'DB_URL='.escapeshellarg($direct).' ' : '';
    exec($env.escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../artisan').' migrate --force --no-interaction 2>&1', $output, $status);
    if ($status === 0) {
        touch($migrated);
    } else {
        error_log('Migrations failed: '.implode("\n", $output));
    }
}

// Laravel would take /api as the site's base folder (this script is /api/index.php) and route
// /api/v1/me as /v1/me; present it as the usual public/index.php instead
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = realpath(__DIR__.'/../public/index.php');

require __DIR__.'/../public/index.php';
