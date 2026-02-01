<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductVariation;

return new class extends Migration
{
    /**
     * Big Tech Style Data Migration
     * Migrates existing stock data to new inventory system
     * Maintains data integrity and provides rollback capability
     * NOTE: This migration should run BEFORE removing stock columns
     */
    public function up(): void
    {
        // Skip migration if stock columns don't exist (already migrated)
        if (!Schema::hasColumn('products', 'stock')) {
            return;
        }

        DB::transaction(function () {
            // Step 1: Migrate Product Stock Data
            $this->migrateProductStock();

            // Step 2: Migrate Product Variation Stock Data
            $this->migrateVariationStock();

            // Step 3: Create Initial Inventory Transactions
            $this->createInitialTransactions();

            // Step 4: Update Product Stock Status
            $this->updateProductStockStatus();
        });
    }

    private function migrateProductStock()
    {
        DB::statement("
            INSERT INTO inventory_stocks (
                product_id,
                product_variation_id,
                location_code,
                location_name,
                available_quantity,
                reserved_quantity,
                minimum_threshold,
                track_inventory,
                status,
                created_at,
                updated_at
            )
            SELECT
                id as product_id,
                NULL as product_variation_id,
                'MAIN' as location_code,
                'Main Warehouse' as location_name,
                COALESCE(stock, 0) as available_quantity,
                0 as reserved_quantity,
                0 as minimum_threshold,
                1 as track_inventory,
                'active' as status,
                NOW() as created_at,
                NOW() as updated_at
            FROM products
            WHERE type = 'simple'
            AND deleted_at IS NULL
        ");
    }

    private function migrateVariationStock()
    {
        DB::statement("
            INSERT INTO inventory_stocks (
                product_id,
                product_variation_id,
                location_code,
                location_name,
                available_quantity,
                reserved_quantity,
                minimum_threshold,
                track_inventory,
                status,
                created_at,
                updated_at
            )
            SELECT
                pv.product_id,
                pv.id as product_variation_id,
                'MAIN' as location_code,
                'Main Warehouse' as location_name,
                COALESCE(pv.stock, 0) as available_quantity,
                0 as reserved_quantity,
                0 as minimum_threshold,
                1 as track_inventory,
                'active' as status,
                NOW() as created_at,
                NOW() as updated_at
            FROM product_variations pv
            INNER JOIN products p ON p.id = pv.product_id
            WHERE p.type = 'variable'
            AND pv.deleted_at IS NULL
            AND p.deleted_at IS NULL
        ");
    }

    private function createInitialTransactions()
    {
        // Create initial transaction records for existing stock
        DB::statement("
            INSERT INTO inventory_transactions (
                inventory_stock_id,
                product_id,
                product_variation_id,
                transaction_type,
                quantity_change,
                quantity_before,
                quantity_after,
                reference_type,
                reference_number,
                reason,
                notes,
                location_code,
                created_by_type,
                source,
                created_at
            )
            SELECT
                ist.id as inventory_stock_id,
                ist.product_id,
                ist.product_variation_id,
                'initial' as transaction_type,
                ist.available_quantity as quantity_change,
                0 as quantity_before,
                ist.available_quantity as quantity_after,
                'migration' as reference_type,
                'INIT-' || ist.id as reference_number,
                'Initial stock migration from legacy system' as reason,
                'Migrated from products/product_variations table during system upgrade' as notes,
                'MAIN' as location_code,
                'system' as created_by_type,
                'migration' as source,
                NOW() as created_at
            FROM inventory_stocks ist
            WHERE ist.available_quantity > 0
        ");
    }

    private function updateProductStockStatus()
    {
        // Skip if stock_status column doesn't exist yet
        if (!Schema::hasColumn('products', 'stock_status')) {
            return;
        }

        // Update product stock status based on inventory
        DB::statement("
            UPDATE products p
            SET stock_status = CASE
                WHEN EXISTS (
                    SELECT 1 FROM inventory_stocks ist
                    WHERE ist.product_id = p.id
                    AND ist.available_quantity > 0
                ) THEN 'in_stock'
                ELSE 'out_of_stock'
            END
        ");

        // Update variation stock status
        if (Schema::hasColumn('product_variations', 'stock_status')) {
            DB::statement("
                UPDATE product_variations pv
                SET stock_status = CASE
                    WHEN EXISTS (
                        SELECT 1 FROM inventory_stocks ist
                        WHERE ist.product_variation_id = pv.id
                        AND ist.available_quantity > 0
                    ) THEN 'in_stock'
                    ELSE 'out_of_stock'
                END
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            // Restore stock data back to products/variations tables
            DB::statement("
                UPDATE products p
                SET stock = COALESCE((
                    SELECT ist.available_quantity
                    FROM inventory_stocks ist
                    WHERE ist.product_id = p.id
                    AND ist.product_variation_id IS NULL
                    LIMIT 1
                ), 0)
                WHERE p.type = 'simple'
            ");

            DB::statement("
                UPDATE product_variations pv
                SET stock = COALESCE((
                    SELECT ist.available_quantity
                    FROM inventory_stocks ist
                    WHERE ist.product_variation_id = pv.id
                    LIMIT 1
                ), 0)
            ");

            // Clear new tables
            DB::table('inventory_transactions')->truncate();
            DB::table('inventory_stocks')->truncate();
        });
    }
};
