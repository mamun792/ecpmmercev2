<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enhanced Product Variations with Better Management
     * Removes stock fields and adds status controls
     * Supports complex variation management scenarios
     */
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            // Remove stock fields (moved to inventory_stocks)
            $table->dropColumn([
                'stock',           // Moved to inventory_stocks
                'sold_stock'       // Will be calculated
            ]);

            // Add Status and Control Fields
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active')->after('price');
            $table->boolean('is_default')->default(false)->after('status');
            $table->integer('sort_order')->default(0)->after('is_default');

            // Enhanced Variation Management
            $table->string('variation_code')->nullable()->after('sort_order'); // Internal variation code
            $table->string('barcode')->nullable()->after('variation_code');
            $table->string('sku')->nullable()->after('barcode'); // Unique SKU for this variation

            // Inventory Control
            $table->boolean('track_inventory')->default(true)->after('sku');
            $table->string('stock_status', 50)->default('in_stock')->after('track_inventory');
            $table->boolean('allow_backorders')->default(false)->after('stock_status');

            // Physical Properties
            $table->decimal('weight', 8, 2)->nullable()->after('allow_backorders');
            $table->json('dimensions')->nullable()->after('weight');
            $table->decimal('shipping_weight', 8, 2)->nullable()->after('dimensions');

            // Cost Management
            $table->decimal('cost_price', 10, 2)->nullable()->after('shipping_weight');
            $table->decimal('compare_at_price', 10, 2)->nullable()->after('cost_price'); // Strikethrough price

            // Availability
            $table->timestamp('available_from')->nullable()->after('compare_at_price');
            $table->timestamp('available_until')->nullable()->after('available_from');
            $table->boolean('requires_shipping')->default(true)->after('available_until');

            // SEO for variations
            $table->string('meta_title')->nullable()->after('requires_shipping');
            $table->text('meta_description')->nullable()->after('meta_title');

            // Add indexes
            $table->index(['status', 'is_default'], 'variation_status');
            $table->index(['product_id', 'status', 'sort_order'], 'product_variations_list');
            $table->index(['sku'], 'variation_sku');
            $table->index(['barcode'], 'variation_barcode');
            $table->index(['track_inventory', 'stock_status'], 'variation_inventory');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['status', 'is_default']);
            $table->dropIndex(['product_id', 'status', 'sort_order']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['barcode']);
            $table->dropIndex(['track_inventory', 'stock_status']);
            $table->dropIndex(['sort_order']);

            // Remove new columns
            $table->dropColumn([
                'status',
                'is_default',
                'sort_order',
                'variation_code',
                'barcode',
                'sku',
                'track_inventory',
                'stock_status',
                'allow_backorders',
                'weight',
                'dimensions',
                'shipping_weight',
                'cost_price',
                'compare_at_price',
                'available_from',
                'available_until',
                'requires_shipping',
                'meta_title',
                'meta_description'
            ]);

            // Restore old stock columns
            $table->integer('stock')->nullable()->after('price');
            $table->integer('sold_stock')->nullable()->default(0)->after('stock');
        });
    }
};
