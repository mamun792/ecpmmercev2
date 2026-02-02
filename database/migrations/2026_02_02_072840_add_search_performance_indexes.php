<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes for search and filter performance
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Status index for filtering published products
            $table->index('status', 'idx_products_status');

            // Category and brand indexes for filtering
            $table->index('category_id', 'idx_products_category');
            $table->index('brand_id', 'idx_products_brand');

            // Price range filtering
            $table->index('price', 'idx_products_price');

            // Barcode and product code for quick lookup
            $table->index('barcode', 'idx_products_barcode');
            $table->index('product_code', 'idx_products_code');

            // Composite index for common filter combinations
            $table->index(['status', 'category_id'], 'idx_products_status_category');
            $table->index(['status', 'brand_id'], 'idx_products_status_brand');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            // Index for low stock queries
            $table->index(['product_id', 'available_quantity'], 'idx_inventory_product_quantity');
            $table->index('available_quantity', 'idx_inventory_quantity');
        });

        // Add full-text index for search (MySQL 5.7+)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, product_code, search_keywords)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status');
            $table->dropIndex('idx_products_category');
            $table->dropIndex('idx_products_brand');
            $table->dropIndex('idx_products_price');
            $table->dropIndex('idx_products_barcode');
            $table->dropIndex('idx_products_code');
            $table->dropIndex('idx_products_status_category');
            $table->dropIndex('idx_products_status_brand');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_product_quantity');
            $table->dropIndex('idx_inventory_quantity');
        });

        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE products DROP INDEX ft_products_search');
        }
    }
};
