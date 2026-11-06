<?php

// Vercel entry point for the website demo (vercel.json sends every non-static request here).
// Vercel's filesystem is read-only except /tmp, so storage and the SQLite database live there.
// /tmp is per instance and is wiped on cold starts: accounts made on the demo don't last.

$storage = '/tmp/storage';
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/public'] as $dir) {
    if (! is_dir("$storage/$dir")) {
        mkdir("$storage/$dir", 0755, true);
    }
}
$_ENV['LARAVEL_STORAGE_PATH'] = $storage;

// An empty, already-migrated database ships with the repo; each instance starts from a copy
$database = '/tmp/database.sqlite';
if (! file_exists($database)) {
    copy(__DIR__.'/../database/demo/demo.sqlite', $database);
}

require __DIR__.'/../public/index.php';
