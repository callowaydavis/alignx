<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->json('component_types')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_definitions');
    }
};
