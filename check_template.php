<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Storage;

$id = 3;
$template = Template::find($id);

if (!$template) {
    echo "ERROR: Template ID $id not found in database.\n";
    $all = Template::all();
    echo "Available IDs: " . $all->pluck('id')->implode(', ') . "\n";
} else {
    echo "Template ID $id found.\n";
    echo "Type: " . $template->template_type . "\n";
    echo "Path: " . $template->storage_path . "\n";
    
    if (Storage::disk('local')->exists($template->storage_path)) {
        echo "FILE EXISTS on local disk.\n";
        echo "Full Path: " . Storage::disk('local')->path($template->storage_path) . "\n";
    } else {
        echo "ERROR: FILE DOES NOT EXIST on local disk at path: " . $template->storage_path . "\n";
    }
}
