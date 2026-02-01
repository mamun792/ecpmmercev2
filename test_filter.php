<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$product = App\Models\Product::with(['variations.attributes.value'])->find(3);

echo "Before filter: " . $product->variations->count() . " variations\n";

$filtered = $product->variations->filter(function ($variation) {
    return $variation->attributes->every(function ($attr) {
        return $attr->value && !$attr->value->trashed();
    });
});

echo "After filter: " . $filtered->count() . " variations\n";

foreach ($product->variations as $v) {
    echo "\nVariation #{$v->id}:\n";
    foreach ($v->attributes as $attr) {
        $trashed = $attr->value ? ($attr->value->trashed() ? 'DELETED' : 'Active') : 'NULL';
        echo "  - {$attr->value->value}: {$trashed}\n";
    }
}

echo "\nFiltered variations:\n";
foreach ($filtered as $v) {
    echo "Variation #{$v->id} - SHOWN\n";
}
