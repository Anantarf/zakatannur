<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('local');
$rows = Template::where('template_type', 'letterhead')->orderByDesc('version')->get();
$localFiles = $disk->files('templates/letterhead');

echo "--- HOSTING RECONCILIATION --- \n";

// Get latest local physical file (by mtime)
usort($localFiles, function($a, $b) use ($disk) {
    return $disk->lastModified($b) <=> $disk->lastModified($a);
});
$newestPhysicalFile = reset($localFiles);

echo "Newest physical local file: " . basename($newestPhysicalFile) . "\n";

foreach ($rows as $r) {
    if (!$disk->exists($r->storage_path)) {
        echo "FIXING: ID {$r->id} (Ver {$r->version}) wants " . basename($r->storage_path) . " but it's missing.\n";
        
        if ($r->is_active) {
            echo "MATCHING ACTIVE: Renaming local newest file to expected hosting path for ID {$r->id}\n";
            rename($disk->path($newestPhysicalFile), $disk->path($r->storage_path));
            $newestPhysicalFile = $r->storage_path; // Update reference
        } else {
            echo "MAPPING: No file for this version, but using newest as placeholder.\n";
            copy($disk->path($newestPhysicalFile), $disk->path($r->storage_path));
        }
    } else {
        echo "OK: ID {$r->id} matches existing file.\n";
    }
}

echo "\nSYNC COMPLETE.\n";
