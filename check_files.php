<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- FILES IN STORAGE ---\n";
foreach(glob('storage/app/templates/letterhead/*.pdf') as $f) {
    echo basename($f) . " | " . date('Y-m-d H:i:s', filemtime($f)) . " | " . filesize($f) . " bytes\n";
}

echo "\n--- RECORDS IN DB ---\n";
$ts = App\Models\Template::where('template_type', 'letterhead')->orderBy('version')->get();
foreach($ts as $t) {
    echo "ID: {$t->id} | Ver: {$t->version} | Path: {$t->storage_path} | Active: " . ($t->is_active ? 'YES' : 'no') . "\n";
}
