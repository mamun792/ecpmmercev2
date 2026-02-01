<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "🚀 CHECKING PRODUCT CREATION RESULTS...\n";
    echo "==========================================\n";

    // Check the created product
    $product = App\Models\Product::find(2);
    if ($product) {
        echo "✅ PRODUCT FOUND: {$product->name} (ID: {$product->id})\n";
        echo "SKU: {$product->product_code}\n";
        echo "Type: {$product->type}\n";
        echo "Feature Image: " . ($product->feature_image ?? 'None') . "\n";

        if ($product->gallery_images) {
            $galleryImages = json_decode($product->gallery_images, true);
            echo "Gallery Images: " . (is_array($galleryImages) ? count($galleryImages) . " images" : $product->gallery_images) . "\n";
        } else {
            echo "Gallery Images: None\n";
        }
        echo "\n";

        // Check variations
        $variations = App\Models\ProductVariation::where('product_id', $product->id)->get();
        echo "📦 VARIATIONS ({$variations->count()} total):\n";

        foreach($variations as $index => $var) {
            echo "Variation " . ($index + 1) . ":\n";
            echo "  - ID: {$var->id}\n";
            echo "  - Price: {$var->price}\n";
            echo "  - Previous Price: {$var->previous_price}\n";
            echo "  - Stock: {$var->stock}\n";

            // Check attributes for each variation
            $attributes = App\Models\VariationAttribute::where('product_variation_id', $var->id)
                         ->with('attributeValue.attribute')
                         ->get();
            echo "  - Attributes: ";
            foreach($attributes as $attr) {
                echo $attr->attributeValue->attribute->name . ':' . $attr->attributeValue->value . ' ';
            }
            echo "\n\n";
        }

        echo "🎯 RESULT: Product with variations created successfully!\n";

    } else {
        echo "❌ Product not found!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
