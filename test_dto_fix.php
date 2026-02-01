<?php

// Test Product Creation Fix
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "🚀 Testing Product Creation Fix...\n";

    // Test DTO creation and legacy mapping
    $requestData = [
        'name' => 'Test Product Fix',
        'product_code' => 'FIX-TEST-001',
        'category_id' => 1,
        'brand_id' => 1,
        'short_description' => 'Test product',
        'description' => 'Test description',
        'status' => 'Published',
        'type' => 'simple',
        'price' => 50.00,
        'stock' => 20,
    ];

    echo "✅ Creating DTO...\n";
    $dto = App\DTOs\ProductStoreDTO::fromRequest($requestData);

    echo "✅ Converting to legacy array...\n";
    $legacyArray = $dto->toLegacyArray();

    echo "📊 Legacy Array Fields:\n";
    foreach($legacyArray as $key => $value) {
        if ($value !== null && $value !== '' && $value !== []) {
            echo "  - $key: " . (is_scalar($value) ? $value : gettype($value)) . "\n";
        }
    }

    // Check critical fields
    if (isset($legacyArray['product_code']) && $legacyArray['product_code'] !== null) {
        echo "✅ product_code field: {$legacyArray['product_code']}\n";
    } else {
        echo "❌ product_code field is missing or null!\n";
    }

    if (isset($legacyArray['name']) && $legacyArray['name'] !== null) {
        echo "✅ name field: {$legacyArray['name']}\n";
    } else {
        echo "❌ name field is missing or null!\n";
    }

    echo "🎯 DTO Fix Test Complete!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Location: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
