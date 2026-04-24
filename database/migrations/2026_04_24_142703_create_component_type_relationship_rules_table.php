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
        Schema::create('component_type_relationship_rules', function (Blueprint $table) {
            $table->foreignId('source_type_id')->constrained('component_types')->cascadeOnDelete();
            $table->foreignId('target_type_id')->constrained('component_types')->cascadeOnDelete();
            $table->primary(['source_type_id', 'target_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_type_relationship_rules');
    }
};
