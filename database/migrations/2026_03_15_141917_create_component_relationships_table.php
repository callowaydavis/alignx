<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('target_component_id')->constrained('components')->cascadeOnDelete();
            $table->string('relationship_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_relationships');
    }
};
