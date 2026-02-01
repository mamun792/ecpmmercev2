<?php
/**
 * Report (and optionally fix) Image DB entries whose files are missing
 * Usage:
 *  php scripts/report_and_fix_missing_files.php        # report only
 *  php scripts/report_and_fix_missing_files.php --delete  # delete DB records for missing files
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Image;
use Illuminate\Support\Facades\File;

$delete = in_array('--delete', $argv, true);
$images = Image::all();
$missing = [];
foreach ($images as $img) {
    $path = public_path(ltrim($img->path, '/'));
    if (!File::exists($path)) {
        $missing[] = [
            'id' => $img->id,
            'path' => $img->path,
            'thumbnail' => $img->thumbnail_path,
        ];
    }
}

if (empty($missing)) {
    echo "No missing files found.\n";
    exit;
}

echo "Found " . count($missing) . " missing image files:\n";
foreach ($missing as $m) {
    echo "  - id: {$m['id']} path: {$m['path']} thumb: {$m['thumbnail']}\n";
}

if ($delete) {
    echo "\nDeleting DB records for missing files...\n";
    foreach ($missing as $m) {
        $img = Image::find($m['id']);
        if ($img) {
            $img->delete();
            echo "  Deleted DB record id {$m['id']}\n";
        }
    }
    echo "Done.\n";
} else {
    echo "\nRun with --delete to remove these DB records.\n";
}
