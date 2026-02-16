<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Big Tech Style Dynamic Pricing System
     * - Base Price থাকবে products table এ
     * - Variant এ শুধু price_type এবং price_value থাকবে (adjustment/percentage/override)
     * - Final Price = Calculated dynamically based on type
     */
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            // Check if columns don't already exist before adding
            if (!Schema::hasColumn('product_variations', 'price_type')) {
                $table->enum('price_type', ['adjustment', 'percentage', 'override'])
                      ->default('adjustment')
                      ->after('price')
                      ->comment('Price calculation: adjustment(±), percentage(%), override(fixed)');
            }

            if (!Schema::hasColumn('product_variations', 'price_value')) {
                $table->decimal('price_value', 10, 2)
                      ->default(0)
                      ->after('price_type')
                      ->comment('Value for price calculation based on type');
            }
        });

        // Update existing variations to use new pricing structure
        // All existing variations will default to 'adjustment' with value 0
        // This means: Final Price = Base Price + 0 = Current price (backward compatible!)
        \DB::table('product_variations')->update([
            'price_type' => 'adjustment',
            'price_value' => 0
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            if (Schema::hasColumn('product_variations', 'price_type')) {
                $table->dropColumn('price_type');
            }

            if (Schema::hasColumn('product_variations', 'price_value')) {
                $table->dropColumn('price_value');
            }
        });
    }
};
