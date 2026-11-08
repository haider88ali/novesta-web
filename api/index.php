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
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../artisan').' migrate --force --no-interaction 2>&1', $output, $status);
    if ($status === 0) {
        touch($migrated);
    } else {
        error_log('Migrations failed: '.implode("\n", $output));
    }
}

require __DIR__.'/../public/index.php';
