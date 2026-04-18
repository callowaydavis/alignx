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
        Schema::create('fact_sheet_component_type', function (Blueprint $table) {
            $table->foreignId('fact_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_type_id')->constrained('component_types')->cascadeOnDelete();
            $table->primary(['fact_sheet_id', 'component_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fact_sheet_component_type');
    }
};
