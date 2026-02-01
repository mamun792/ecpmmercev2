<?php

require_once __DIR__.'/vendor/autoload.php';

use App\DTOs\ProductStoreDTO;

// Test complete product creation DTO mapping
try {
    $requestData = [
        'name' => 'Amy Gardner',
        'product_code' => 'Aut officiis eligend',
        'category_id' => 1,
        'brand_id' => null,
        'short_description' => '<p>&nbsp;</p>',
        'description' => '<p>&nbsp;</p>',
        'status' => 'Published',
        'is_daily_product' => 0,
        'is_pre_order' => 0,
        'type' => 'variable',
        'price' => 0,
        'previous_price' => 0,
        'stock' => 200,
        'remarks' => null,
        'meta_title' => null,
        'meta_description' => null,
    ];

    $dto = ProductStoreDTO::fromRequest($requestData);
    $legacyArray = $dto->toLegacyArray();

    echo "🚀 PRODUCT DTO MAPPING TEST\n";
    echo "Fields mapped:\n";
    foreach($legacyArray as $key => $value) {
        if (!$value || $value === 0 || $value === false) continue;
        echo "  - $key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }

    echo "✅ DTO MAPPING SUCCESS!\n";

    // Check for product_code specifically
    if (isset($legacyArray['product_code'])) {
        echo "✅ product_code field is present: " . $legacyArray['product_code'] . "\n";
    } else {
        echo "❌ product_code field is MISSING!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
