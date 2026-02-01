<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing columns to order_items for data preservation:
     * - SoftDeletes support (deleted_at, deleted_by)
     * Only adds columns that don't already exist
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('order_items', 'deleted_at')) {
                $table->softDeletes();
            }

            if (!Schema::hasColumn('order_items', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }

            if (!Schema::hasColumn('order_items', 'deletion_reason')) {
                $table->string('deletion_reason')->nullable();
            }

            // Add variation_image if only variation_image_url exists
            if (!Schema::hasColumn('order_items', 'variation_image') && Schema::hasColumn('order_items', 'variation_image_url')) {
                // Use existing column, no need to add
            }

            // Add product_image if only product_image_url exists
            if (!Schema::hasColumn('order_items', 'product_image') && Schema::hasColumn('order_items', 'product_image_url')) {
                // Use existing column, no need to add
            }
        });

        // Update foreign keys to use nullOnDelete instead of cascadeOnDelete
        // Only if they exist with cascadeOnDelete
        try {
            Schema::table('order_items', function (Blueprint $table) {
                // Check if foreign key exists before dropping
                $foreignKeys = collect(DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = 'order_items'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    AND TABLE_SCHEMA = DATABASE()
                "));

                if ($foreignKeys->contains('CONSTRAINT_NAME', 'order_items_product_id_foreign')) {
                    $table->dropForeign(['product_id']);
                    $table->foreign('product_id')
                        ->references('id')
                        ->on('products')
                        ->nullOnDelete();
                }

                if ($foreignKeys->contains('CONSTRAINT_NAME', 'order_items_product_variation_id_foreign')) {
                    $table->dropForeign(['product_variation_id']);
                    $table->foreign('product_variation_id')
                        ->references('id')
                        ->on('product_variations')
                        ->nullOnDelete();
                }
            });
        } catch (\Exception $e) {
            // Foreign keys may already be updated or not exist
            \Log::info('Foreign key update skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('order_items', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }

            if (Schema::hasColumn('order_items', 'deletion_reason')) {
                $table->dropColumn('deletion_reason');
            }
        });
    }
};
