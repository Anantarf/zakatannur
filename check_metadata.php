<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Storage;

echo "--- DB STATE ---\n";
$rows = Template::where('template_type', 'letterhead')->orderByDesc('version')->get();
foreach ($rows as $r) {
    $exists = Storage::disk('local')->exists($r->storage_path);
    echo "ID: {$r->id} | Ver: {$r->version} | Active: " . ($r->is_active ? 'YES' : 'no') . " | Orig: {$r->original_filename} | Size: {$r->file_size_bytes} | File Exists: " . ($exists ? 'YES' : 'NO') . "\n";
}

echo "\n--- PHYSICAL FILES ---\n";
foreach (glob('storage/app/templates/letterhead/*.pdf') as $f) {
    echo basename($f) . " | Size: " . filesize($f) . " | MTime: " . date('Y-m-d H:i:s', filemtime($f)) . "\n";
}
