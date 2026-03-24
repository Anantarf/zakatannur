<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('local');
$dbRows = Template::where('template_type', 'letterhead')->orderBy('version')->get();
$localFiles = $disk->files('templates/letterhead');

echo "--- RECONCILIATION START ---\n";

foreach ($dbRows as $row) {
    $expectedPath = $row->storage_path;
    $version = $row->version;

    if ($disk->exists($expectedPath)) {
        echo "OK: Version $version already matched to $expectedPath\n";
        continue;
    }

    // Attempt to find any local file that matches this version
    $found = false;
    foreach ($localFiles as $file) {
        if (preg_match("/letterhead_v{$version}_/", basename($file))) {
            echo "FIXING: Renaming local file " . basename($file) . " to " . basename($expectedPath) . "\n";
            rename($disk->path($file), $disk->path($expectedPath));
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "ERROR: No local file found for Version $version. Looking for a fallback...\n";
        // Fallback: if no match, maybe copy the highest version file?
        if (!empty($localFiles)) {
            $fallback = end($localFiles);
            echo "FALLBACK: Copying " . basename($fallback) . " to " . basename($expectedPath) . "\n";
            copy($disk->path($fallback), $disk->path($expectedPath));
        }
    }
}

echo "\n--- RECONCILIATION COMPLETE ---\n";
