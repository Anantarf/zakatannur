<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::find(3);
if ($t) {
    echo "ID 3 Version: " . $t->version . "\n";
    echo "ID 3 Path: " . $t->storage_path . "\n";
    echo "File Exists: " . (Illuminate\Support\Facades\Storage::disk('local')->exists($t->storage_path) ? 'YES' : 'NO') . "\n";
} else {
    echo "ID 3 NOT FOUND\n";
    echo "All Template IDs: " . App\Models\Template::pluck('id')->implode(', ') . "\n";
}
