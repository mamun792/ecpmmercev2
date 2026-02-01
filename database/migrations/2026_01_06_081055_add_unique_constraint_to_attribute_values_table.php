<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            // Add unique constraint for attribute_id + value combination
            // This prevents duplicate values for the same attribute
            $table->unique(['attribute_id', 'value'], 'unique_attribute_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('unique_attribute_value');
        });
    }
};
