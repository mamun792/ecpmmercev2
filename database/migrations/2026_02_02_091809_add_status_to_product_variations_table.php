<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add status column to product_variations table
     * Big Tech Style: Active/Inactive instead of hard delete
     */
    public function up(): void
    {
        // Check if status column doesn't exist before adding
        if (!Schema::hasColumn('product_variations', 'status')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('image_path');
                $table->index(['product_id', 'status']);
            });

            // Update existing variations to 'active' status
            DB::table('product_variations')->update(['status' => 'active']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
