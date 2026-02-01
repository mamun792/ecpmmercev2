<?php
/**
 * Cleanup duplicates script
 * Usage:
 *  php scripts/cleanup_duplicate_thumbnails.php         # dry-run (report only)
 *  php scripts/cleanup_duplicate_thumbnails.php --apply # perform changes
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Image;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\File;

$apply = in_array('--apply', $argv, true);
$base = getcwd() . '/public';
$extensions = ['jpg','jpeg','png','gif','webp'];

// Collect files
$files = [];
foreach ($extensions as $ext) {
    $pattern = $base . '/**/*.' . $ext;
}

// We'll use find to be efficient
$cmd = "find public -type f -regextype posix-extended -iregex '.*\\.(" . implode('|', $extensions) . ")$' -print";
exec($cmd, $out);
foreach ($out as $f) {
    $files[] = $f;
}

if (empty($files)) {
    echo "No image files found in public/\n";
    exit;
}

// Map checksums
$map = [];
foreach ($files as $file) {
    if (!is_file($file)) continue;
    $hash = hash_file('sha256', $file);
    if (!$hash) continue;
    $map[$hash][] = $file;
}

$duplicateGroups = array_filter($map, function($g){ return count($g) > 1; });

if (empty($duplicateGroups)) {
    echo "No identical-content duplicates found.\n";
} else {
    echo "Found " . count($duplicateGroups) . " duplicate groups:\n";
    $actions = [];

    foreach ($duplicateGroups as $hash => $group) {
        echo "\nGroup checksum: $hash\n";
        foreach ($group as $i => $path) {
            echo sprintf("  [%d] %s\n", $i+1, $path);
        }

        // choose keeper: prefer filename that looks like deterministic thumbnail (thumb_{hash}.*)
        $keeper = null;
        foreach ($group as $p) {
            if (strpos(basename($p), 'thumb_' . $hash) !== false) {
                $keeper = $p; break;
            }
        }
        if (!$keeper) {
            // prefer file that is not a thumb (heuristic: basename doesn't start with thumb_)
            foreach ($group as $p) {
                if (strpos(basename($p), 'thumb_') === false) {
                    $keeper = $p; break;
                }
            }
        }
        if (!$keeper) $keeper = $group[0];

        echo "  Keep: $keeper\n";

        foreach ($group as $p) {
            if ($p === $keeper) continue;
            $actions[] = ['keep' => $keeper, 'remove' => $p, 'hash' => $hash];
        }
    }

    echo "\nPlanned actions: " . count($actions) . " files to remove.\n";

    if (!$apply) {
        echo "Dry-run (no files will be removed). Run with --apply to perform changes.\n";
        foreach ($actions as $a) {
            echo "  Would remove: {$a['remove']} (keep {$a['keep']})\n";
        }

        // Also report DB updates needed: any Image records pointing to removed thumbs should be updated to keeper path
        echo "\nDB updates planned: \n";
        foreach ($actions as $a) {
            $relRemove = ltrim(str_replace(getcwd() . '/public/', '', $a['remove']), '/');
            $relKeep = ltrim(str_replace(getcwd() . '/public/', '', $a['keep']), '/');
            $refs = Image::where('thumbnail_path', $relRemove)->orWhere('path', $relRemove)->get();
            if ($refs->count()) {
                foreach ($refs as $r) {
                    echo "  Record id {$r->id}: will update thumbnail/path from {$relRemove} to {$relKeep}\n";
                }
            }
        }

        exit;
    }

    // Apply changes
    echo "Applying changes...\n";
    foreach ($actions as $a) {
        $keepRel = ltrim(str_replace(getcwd() . '/public/', '', $a['keep']), '/');
        $removeRel = ltrim(str_replace(getcwd() . '/public/', '', $a['remove']), '/');

        // Update DB records referencing removeRel
        $refs = Image::where('thumbnail_path', $removeRel)->orWhere('path', $removeRel)->get();
        foreach ($refs as $r) {
            // If the record path is being removed (rare), update path to keeper too
            if ($r->path === $removeRel) {
                $r->path = $keepRel;
            }
            if ($r->thumbnail_path === $removeRel) {
                $r->thumbnail_path = $keepRel;
            }
            $r->save();
            echo "  Updated DB record id {$r->id} to use {$keepRel}\n";
        }

        // Delete duplicate file
        if (File::exists($a['remove'])) {
            File::delete($a['remove']);
            echo "  Deleted file {$a['remove']}\n";
        }
    }

    echo "Cleanup applied.\n";
}

// Finally, ensure all Image records have deterministic thumbnails (create if missing)
$images = Image::all();
foreach ($images as $img) {
    $thumb = ImageHelper::ensureDeterministicThumbnailForImage($img);
    if ($thumb) echo "Ensured thumb for image id {$img->id}: {$thumb}\n";
}

echo "Done.\n";
