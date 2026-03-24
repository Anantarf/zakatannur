<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$disk = Storage::disk('local');
$filesInStorage = $disk->files('templates/letterhead');
$superAdmin = User::where('role', 'super_admin')->first() ?? User::first();

echo "Scanning storage/app/templates/letterhead/...\n";

// Map storage files by version
$storageMap = [];
foreach ($filesInStorage as $filePath) {
    if (!Str::endsWith($filePath, '.pdf')) continue;
    if (preg_match('/letterhead_v(\d+)_/', basename($filePath), $matches)) {
        $storageMap[(int)$matches[1]] = $filePath;
    }
}

$dbTemplates = Template::where('template_type', Template::TYPE_LETTERHEAD)->get();

foreach ($dbTemplates as $template) {
    if (isset($storageMap[$template->version])) {
        $actualPath = $storageMap[$template->version];
        if ($template->storage_path !== $actualPath) {
            echo "FIXING DB: Version {$template->version} (ID {$template->id}) path updated to $actualPath\n";
            $template->update(['storage_path' => $actualPath]);
        } else {
            echo "OK: Version {$template->version} matches.\n";
        }
        unset($storageMap[$template->version]); // Mark as handled
    } else {
        echo "WARNING: DB Record Version {$template->version} (ID {$template->id}) has NO FILE in storage. Deleting record.\n";
        $template->delete();
    }
}

// Any remaining in storageMap are files without DB records
foreach ($storageMap as $version => $filePath) {
    echo "CREATING DB: File for Version $version found but no record exists. Adding record.\n";
    Template::create([
        'template_type' => Template::TYPE_LETTERHEAD,
        'version' => $version,
        'is_active' => false,
        'storage_path' => $filePath,
        'original_filename' => basename($filePath),
        'mime_type' => 'application/pdf',
        'file_size_bytes' => $disk->size($filePath),
        'uploaded_by' => $superAdmin->id,
    ]);
}

echo "\nSynchronization Complete.\n";
