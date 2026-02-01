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
        Schema::create('marketing_tools', function (Blueprint $table) {
            $table->id();
            $table->string('tool_name')->unique(); // e.g., Facebook Pixel, Google Analytics, Google Tag Manager
            $table->text('script_code'); // Stores the script code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_tools');
    }
};
