<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\Template::where('template_type', 'letterhead')->orderBy('version')->get();
echo "--- DB TEMPLATES ---\n";
foreach($rows as $r) {
    echo "ID: {$r->id} | Ver: {$r->version} | Path: {$r->storage_path}\n";
}
