<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Finding duplicate attribute values...\n";

$duplicates = DB::select("
    SELECT attribute_id, value, COUNT(*) as count
    FROM attribute_values
    GROUP BY attribute_id, value
    HAVING count > 1
");

if (empty($duplicates)) {
    echo "No duplicates found!\n";
    exit(0);
}

echo "Found " . count($duplicates) . " duplicate attribute value(s):\n";
foreach ($duplicates as $dup) {
    echo "  - Attribute ID: {$dup->attribute_id}, Value: '{$dup->value}' (count: {$dup->count})\n";
}

echo "\nRemoving duplicates (keeping only the first occurrence)...\n";

foreach ($duplicates as $dup) {
    // Get all IDs for this duplicate
    $ids = DB::table('attribute_values')
        ->where('attribute_id', $dup->attribute_id)
        ->where('value', $dup->value)
        ->orderBy('id', 'asc')
        ->pluck('id')
        ->toArray();

    // Keep the first ID, delete the rest
    $keepId = array_shift($ids);

    if (!empty($ids)) {
        $deleted = DB::table('attribute_values')
            ->whereIn('id', $ids)
            ->delete();

        echo "  - Kept ID {$keepId}, deleted " . count($ids) . " duplicate(s)\n";
    }
}

echo "\nDuplicates removed successfully!\n";
