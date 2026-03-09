<?php
// SECURITY: Delete this file after use
define('APP_DIR', dirname(__DIR__) . '/kbb-order-mng-app');

echo '<pre style="font-family:monospace; background:#1e1e1e; color:#d4d4d4; padding:20px; margin:0; min-height:100vh">';
echo "=== KBB Setup Script ===\n\n";

// 1. Delete hot file (causes Vite dev server loading issue)
$hotFiles = [
    __DIR__ . '/hot',                    // public_html/hot
    APP_DIR . '/public/hot',             // kbb-order-mng-app/public/hot
];
foreach ($hotFiles as $hotFile) {
    if (file_exists($hotFile)) {
        unlink($hotFile);
        echo "✓ Deleted hot file: $hotFile\n";
    } else {
        echo "  No hot file at: $hotFile\n";
    }
}
echo "\n";

// 2. Run artisan commands
chdir(APP_DIR);
$_SERVER['argv'] = ['artisan'];

require APP_DIR . '/vendor/autoload.php';
$app = require APP_DIR . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$commands = ['migrate --force', 'view:clear', 'cache:clear', 'config:cache', 'route:cache'];
foreach ($commands as $cmd) {
    $kernel->call($cmd);
    echo "✓ php artisan $cmd\n" . trim($kernel->output()) . "\n\n";
}

echo "=== Done! ===\n";
echo '</pre>';
