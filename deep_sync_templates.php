<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

$disk = Storage::disk('local');
$files = $disk->files('templates/letterhead');
$superAdmin = User::where('role', 'super_admin')->first() ?? User::first();

echo "--- STARTING DEEP SYNC ---\n";

// 1. Scan and Sort
$fileDetails = [];
foreach ($files as $f) {
    if (str_ends_with($f, '.pdf')) {
        $fileDetails[] = [
            'path' => $f,
            'mtime' => $disk->lastModified($f),
            'size' => $disk->size($f),
            'name' => basename($f)
        ];
    }
}

// Sort by timestamp (Oldest first)
usort($fileDetails, function($a, $b) {
    return $a['mtime'] <=> $b['mtime'];
});

echo "Found " . count($fileDetails) . " files in storage.\n";

DB::transaction(function () use ($fileDetails, $superAdmin) {
    // 2. Clear out all old letterhead records
    Template::where('template_type', Template::TYPE_LETTERHEAD)->delete();

    // 3. Re-insert with clean versioning
    foreach ($fileDetails as $idx => $data) {
        $v = $idx + 1;
        echo "[v{$v}] Re-mapping record to {$data['name']} (Uploaded: " . date('Y-m-d H:i:s', $data['mtime']) . ")\n";
        
        $template = Template::create([
            'template_type' => Template::TYPE_LETTERHEAD,
            'version' => $v,
            'is_active' => false,
            'storage_path' => $data['path'],
            'original_filename' => $data['name'],
            'mime_type' => 'application/pdf',
            'file_size_bytes' => $data['size'],
            'uploaded_by' => $superAdmin->id,
            'created_at' => date('Y-m-d H:i:s', $data['mtime']),
        ]);
        
        // 4. Default Activate the absolute newest (highest index)
        if ($idx === count($fileDetails) - 1) {
            $template->update(['is_active' => true]);
            echo "SUCCESS: Version {$v} marked as ACTIVE (Latest).\n";
        }
    }
});

echo "\nDEEP SYNC COMPLETE.\n";
