<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::where('is_active', true)->where('template_type', 'letterhead')->first();
if ($t) {
    echo "--- ACTIVE TEMPLATE ---\n";
    echo "ID: {$t->id}\n";
    echo "Version: {$t->version}\n";
    echo "Path: {$t->storage_path}\n";
    echo "File Exists: " . (Illuminate\Support\Facades\Storage::disk('local')->exists($t->storage_path) ? 'YES' : 'NO') . "\n";
} else {
    echo "CRITICAL: NO ACTIVE TEMPLATE FOUND IN DB!\n";
}

echo "\n--- ALL TEMPLATES ---\n";
foreach(App\Models\Template::where('template_type', 'letterhead')->orderBy('version')->get() as $row) {
    echo "ID: {$row->id} | Ver: {$row->version} | Active: " . ($row->is_active ? 'YES' : 'no') . " | " . basename($row->storage_path) . "\n";
}
