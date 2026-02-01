<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Big Tech Style: Add status column for attributes and attribute values
     * Status: active/inactive instead of hard delete
     */
    public function up(): void
    {
        // Add status to attributes table
        Schema::table('attributes', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('name');
            $table->index('status');
        });

        // Add status to attribute_values table
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('value');
            $table->index(['attribute_id', 'status'], 'attr_value_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropIndex('attr_value_status');
            $table->dropColumn('status');
        });
    }
};
