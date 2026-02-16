<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Global Attribute Library System
     * - Attributes একবার তৈরী করলে সব products এ reuse করা যাবে
     * - Library থেকে select করে দ্রুত attribute add করা যাবে
     */
    public function up(): void
    {
        // Add global library support to attributes table
        Schema::table('attributes', function (Blueprint $table) {
            if (!Schema::hasColumn('attributes', 'is_global')) {
                $table->boolean('is_global')
                      ->default(false)
                      ->after('name')
                      ->comment('Global library attribute for reuse across products');
            }

            if (!Schema::hasColumn('attributes', 'display_order')) {
                $table->integer('display_order')
                      ->default(0)
                      ->after('status')
                      ->comment('Sort order for attributes');
            }

            if (!Schema::hasColumn('attributes', 'settings')) {
                $table->json('settings')
                      ->nullable()
                      ->after('display_order')
                      ->comment('Settings: visible_on_product, used_for_variations');
            }

            // Add index for better performance
            $table->index(['is_global', 'status'], 'attr_global_status_idx');
        });

        // Add display order to attribute values
        Schema::table('attribute_values', function (Blueprint $table) {
            if (!Schema::hasColumn('attribute_values', 'display_order')) {
                $table->integer('display_order')
                      ->default(0)
                      ->after('value')
                      ->comment('Sort order for attribute values');
            }

            // Add index for sorting
            $table->index(['attribute_id', 'display_order'], 'attr_val_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropIndex('attr_global_status_idx');

            if (Schema::hasColumn('attributes', 'is_global')) {
                $table->dropColumn('is_global');
            }
            if (Schema::hasColumn('attributes', 'display_order')) {
                $table->dropColumn('display_order');
            }
            if (Schema::hasColumn('attributes', 'settings')) {
                $table->dropColumn('settings');
            }
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropIndex('attr_val_order_idx');

            if (Schema::hasColumn('attribute_values', 'display_order')) {
                $table->dropColumn('display_order');
            }
        });
    }
};
