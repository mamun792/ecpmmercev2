<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enhanced Soft Delete Implementation
     * Adds proper soft delete support with audit trail
     * Prevents data loss while maintaining referential integrity
     */
    public function up(): void
    {
        // Add soft delete audit columns to products
        Schema::table('products', function (Blueprint $table) {
            // Enhanced soft delete tracking
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable()->after('deleted_by');
            $table->json('deletion_context')->nullable()->after('deletion_reason');

            // Recovery tracking
            $table->timestamp('recovered_at')->nullable()->after('deletion_context');
            $table->foreignId('recovered_by')->nullable()->after('recovered_at')->constrained('users')->nullOnDelete();

            // Indexes for soft delete queries
            $table->index(['deleted_at', 'status'], 'products_soft_delete');
        });

        // Add soft delete audit columns to product_variations
        Schema::table('product_variations', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable()->after('deleted_by');
            $table->json('deletion_context')->nullable()->after('deletion_reason');

            $table->timestamp('recovered_at')->nullable()->after('deletion_context');
            $table->foreignId('recovered_by')->nullable()->after('recovered_at')->constrained('users')->nullOnDelete();

            $table->index(['deleted_at', 'status'], 'variations_soft_delete');
        });

        // Add soft delete audit to orders (already has soft delete column)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable()->after('deleted_by');
            $table->json('deletion_context')->nullable()->after('deletion_reason');

            $table->timestamp('recovered_at')->nullable()->after('deletion_context');
            $table->foreignId('recovered_by')->nullable()->after('recovered_at')->constrained('users')->nullOnDelete();

            $table->index(['deleted_at', 'status'], 'orders_soft_delete');
        });

        // Add soft delete to categories (enhance existing)
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable()->after('deleted_by');

            $table->timestamp('recovered_at')->nullable()->after('deletion_reason');
            $table->foreignId('recovered_by')->nullable()->after('recovered_at')->constrained('users')->nullOnDelete();
        });

        // Add soft delete to attribute_values (enhance existing)
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable()->after('deleted_by');

            $table->timestamp('recovered_at')->nullable()->after('deletion_reason');
            $table->foreignId('recovered_by')->nullable()->after('recovered_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'status']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['recovered_by']);
            $table->dropColumn([
                'deleted_by', 'deletion_reason', 'deletion_context',
                'recovered_at', 'recovered_by'
            ]);
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'status']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['recovered_by']);
            $table->dropColumn([
                'deleted_by', 'deletion_reason', 'deletion_context',
                'recovered_at', 'recovered_by'
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'status']);
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['recovered_by']);
            $table->dropColumn([
                'deleted_by', 'deletion_reason', 'deletion_context',
                'recovered_at', 'recovered_by'
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['recovered_by']);
            $table->dropColumn([
                'deleted_by', 'deletion_reason',
                'recovered_at', 'recovered_by'
            ]);
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropForeign(['recovered_by']);
            $table->dropColumn([
                'deleted_by', 'deletion_reason',
                'recovered_at', 'recovered_by'
            ]);
        });
    }
};
