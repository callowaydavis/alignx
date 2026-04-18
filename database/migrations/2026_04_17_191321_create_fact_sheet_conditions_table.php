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
        Schema::create('fact_sheet_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fact_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fact_definition_id')->constrained()->cascadeOnDelete();
            $table->string('operator');
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fact_sheet_conditions');
    }
};
