<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Default Global Attributes তৈরী করা
     * - Size, Color, Material যেন শুরুতেই library তে থাকে
     */
    public function up(): void
    {
        // Check if global attributes already exist
        $existingGlobalAttrs = DB::table('attributes')->where('is_global', true)->count();

        if ($existingGlobalAttrs > 0) {
            // Already seeded, skip
            return;
        }

        // Size Attribute
        $sizeId = DB::table('attributes')->insertGetId([
            'name' => 'Size',
            'is_global' => true,
            'display_order' => 1,
            'status' => 'active',
            'settings' => json_encode([
                'visible_on_product' => true,
                'used_for_variations' => true
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Size Values
        $sizeValues = ['S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizeValues as $index => $value) {
            DB::table('attribute_values')->insert([
                'attribute_id' => $sizeId,
                'value' => $value,
                'display_order' => $index + 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Color Attribute
        $colorId = DB::table('attributes')->insertGetId([
            'name' => 'Color',
            'is_global' => true,
            'display_order' => 2,
            'status' => 'active',
            'settings' => json_encode([
                'visible_on_product' => true,
                'used_for_variations' => true
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Color Values
        $colorValues = ['Red', 'Blue', 'Green', 'Black', 'White'];
        foreach ($colorValues as $index => $value) {
            DB::table('attribute_values')->insert([
                'attribute_id' => $colorId,
                'value' => $value,
                'display_order' => $index + 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Material Attribute
        $materialId = DB::table('attributes')->insertGetId([
            'name' => 'Material',
            'is_global' => true,
            'display_order' => 3,
            'status' => 'active',
            'settings' => json_encode([
                'visible_on_product' => true,
                'used_for_variations' => true
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Material Values
        $materialValues = ['Cotton', 'Polyester', 'Silk', 'Wool'];
        foreach ($materialValues as $index => $value) {
            DB::table('attribute_values')->insert([
                'attribute_id' => $materialId,
                'value' => $value,
                'display_order' => $index + 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove seeded global attributes and their values
        $globalAttrIds = DB::table('attributes')
            ->where('is_global', true)
            ->whereIn('name', ['Size', 'Color', 'Material'])
            ->pluck('id');

        if ($globalAttrIds->isNotEmpty()) {
            DB::table('attribute_values')
                ->whereIn('attribute_id', $globalAttrIds)
                ->delete();

            DB::table('attributes')
                ->whereIn('id', $globalAttrIds)
                ->delete();
        }
    }
};
