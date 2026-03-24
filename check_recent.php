<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob('storage/app/templates/letterhead/*.pdf');
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo "--- FILES BY RECENT MODIFICATION ---\n";
foreach($files as $f) {
    echo date('Y-m-d H:i:s', filemtime($f)) . " | " . basename($f) . " | " . filesize($f) . " bytes\n";
}

echo "\n--- DB RECORDS ---\n";
$ts = App\Models\Template::where('template_type', 'letterhead')->orderBy('version')->get();
foreach($ts as $t) {
    echo "ID: {$t->id} | Ver: {$t->version} | Path: {$t->storage_path}\n";
}
