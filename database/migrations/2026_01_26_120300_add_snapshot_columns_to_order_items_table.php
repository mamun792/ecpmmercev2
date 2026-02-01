<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shopify/Amazon Style Order Item Snapshots
     * Preserves complete product/variation data at time of order
     * Prevents data loss when products are updated/deleted
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Product Snapshot Data (Critical for Order History)
            $table->string('product_name')->after('product_variation_id');
            $table->string('product_code')->after('product_name');
            $table->string('product_sku')->nullable()->after('product_code');
            $table->string('product_image_url', 500)->nullable()->after('product_sku');
            $table->text('product_short_description')->nullable()->after('product_image_url');

            // Variation Snapshot Data (if applicable)
            $table->string('variation_name')->nullable()->after('product_short_description');
            $table->string('variation_sku')->nullable()->after('variation_name');
            $table->string('variation_image_url', 500)->nullable()->after('variation_sku');
            $table->json('variation_attributes')->nullable()->after('variation_image_url'); // {"Color": "Red", "Size": "XL"}

            // Enhanced Pricing Breakdown
            $table->decimal('base_product_price', 10, 2)->after('variation_attributes');
            $table->decimal('variation_price_addition', 10, 2)->default(0.00)->after('base_product_price');
            $table->decimal('original_price', 10, 2)->after('variation_price_addition'); // Before any discounts
            $table->decimal('cost_price', 10, 2)->nullable()->after('original_price');

            // Tax & Fee Breakdown
            $table->decimal('tax_rate', 5, 2)->default(0.00)->after('cost_price');
            $table->decimal('tax_amount', 10, 2)->default(0.00)->after('tax_rate');
            $table->decimal('handling_fee', 10, 2)->default(0.00)->after('tax_amount');

            // Campaign & Discount Details
            $table->string('campaign_name')->nullable()->after('handling_fee');
            $table->string('campaign_code')->nullable()->after('campaign_name');

            // Product State Snapshot
            $table->string('product_status', 50)->default('active')->after('campaign_code'); // Published, Unpublished at time of order
            $table->string('product_type', 50)->default('simple')->after('product_status');  // simple, variable
            $table->boolean('was_pre_order')->default(false)->after('product_type');

            // Inventory Tracking
            $table->integer('stock_at_order_time')->nullable()->after('was_pre_order'); // Stock available when ordered
            $table->string('inventory_location', 50)->default('MAIN')->after('stock_at_order_time');

            // Complete Product Data Backup (Big Tech Style)
            $table->json('product_data_snapshot')->nullable()->after('inventory_location'); // Full product JSON backup
            $table->json('variation_data_snapshot')->nullable()->after('product_data_snapshot'); // Full variation JSON backup

            // Snapshot Metadata
            $table->timestamp('snapshot_created_at')->useCurrent()->after('variation_data_snapshot');
            $table->string('snapshot_version', 20)->default('1.0')->after('snapshot_created_at');

            // Indexes for Performance
            $table->index(['product_code', 'created_at'], 'product_order_history');
            $table->index(['variation_sku', 'created_at'], 'variation_order_history');
            $table->index('snapshot_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['product_code', 'created_at']);
            $table->dropIndex(['variation_sku', 'created_at']);
            $table->dropIndex(['snapshot_created_at']);

            $table->dropColumn([
                'product_name',
                'product_code',
                'product_sku',
                'product_image_url',
                'product_short_description',
                'variation_name',
                'variation_sku',
                'variation_image_url',
                'variation_attributes',
                'base_product_price',
                'variation_price_addition',
                'original_price',
                'cost_price',
                'tax_rate',
                'tax_amount',
                'handling_fee',
                'campaign_name',
                'campaign_code',
                'product_status',
                'product_type',
                'was_pre_order',
                'stock_at_order_time',
                'inventory_location',
                'product_data_snapshot',
                'variation_data_snapshot',
                'snapshot_created_at',
                'snapshot_version'
            ]);
        });
    }
};
