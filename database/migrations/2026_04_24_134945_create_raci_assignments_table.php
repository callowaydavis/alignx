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
        Schema::create('raci_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raci_row_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raci_column_id')->constrained()->cascadeOnDelete();
            $table->string('assigned_to_type'); // 'user' or 'team'
            $table->unsignedBigInteger('assigned_to_id');
            $table->timestamps();

            $table->unique(['raci_row_id', 'raci_column_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raci_assignments');
    }
};
