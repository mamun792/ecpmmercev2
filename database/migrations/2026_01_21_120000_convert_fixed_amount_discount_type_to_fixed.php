<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize stored values first
        DB::table('coupons')
            ->where('discount_type', 'fixed_amount')
            ->update(['discount_type' => 'fixed']);

        DB::table('order_items')
            ->where('discount_type', 'fixed_amount')
            ->update(['discount_type' => 'fixed']);

        // Alter enum definitions to replace 'fixed_amount' with 'fixed'
        // For MySQL - use MODIFY; adjust NOT NULL / NULL based on original schema
        DB::statement("ALTER TABLE `coupons` MODIFY `discount_type` ENUM('percentage','fixed') NOT NULL");
        DB::statement("ALTER TABLE `order_items` MODIFY `discount_type` ENUM('percentage','fixed') NULL");

        // Note: If you use a different DB engine that doesn't support direct enum changes,
        // you may need to adjust these statements accordingly.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert values
        DB::table('coupons')
            ->where('discount_type', 'fixed')
            ->update(['discount_type' => 'fixed_amount']);

        DB::table('order_items')
            ->where('discount_type', 'fixed')
            ->update(['discount_type' => 'fixed_amount']);

        // Revert enum definitions
        DB::statement("ALTER TABLE `coupons` MODIFY `discount_type` ENUM('percentage','fixed_amount') NOT NULL");
        DB::statement("ALTER TABLE `order_items` MODIFY `discount_type` ENUM('percentage','fixed_amount') NULL");
    }
};
