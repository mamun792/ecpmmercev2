<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove Legacy Stock Management from Products Table
     * Stock is now managed centrally in inventory_stocks table
     * Maintains data integrity during migration
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove unnecessary stock-related columns
            $table->dropColumn([
                'stock',           // Moved to inventory_stocks.available_quantity
                'sold_stock'       // Will be calculated from order_items
            ]);

            // Add new status and control fields
            $table->boolean('track_inventory')->default(true)->after('is_pre_order');
            $table->string('stock_status', 50)->default('in_stock')->after('track_inventory'); // in_stock, out_of_stock, low_stock
            $table->boolean('allow_backorders')->default(false)->after('stock_status');
            $table->integer('sort_order')->default(0)->after('allow_backorders');

            // Enhanced product management
            $table->string('barcode')->nullable()->after('product_code');
            $table->string('isbn')->nullable()->after('barcode');
            $table->decimal('weight', 8, 2)->nullable()->after('isbn');
            $table->json('dimensions')->nullable()->after('weight'); // {"length": 10, "width": 5, "height": 3}

            // SEO and Marketing
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->text('search_keywords')->nullable()->after('meta_keywords');

            // Timestamps for better tracking
            $table->timestamp('published_at')->nullable()->after('updated_at');
            $table->timestamp('featured_at')->nullable()->after('published_at');

            // Add indexes for performance
            $table->index(['status', 'stock_status'], 'product_availability');
            $table->index(['track_inventory', 'stock_status'], 'inventory_tracking');
            $table->index('sort_order');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['status', 'stock_status']);
            $table->dropIndex(['track_inventory', 'stock_status']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['published_at']);

            // Remove new columns
            $table->dropColumn([
                'track_inventory',
                'stock_status',
                'allow_backorders',
                'sort_order',
                'barcode',
                'isbn',
                'weight',
                'dimensions',
                'meta_keywords',
                'search_keywords',
                'published_at',
                'featured_at'
            ]);

            // Restore old stock columns
            $table->integer('stock')->default(0)->after('specification');
            $table->integer('sold_stock')->nullable()->default(0)->after('stock');
        });
    }
};
