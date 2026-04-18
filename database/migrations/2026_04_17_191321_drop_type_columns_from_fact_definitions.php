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
        Schema::table('fact_definitions', function (Blueprint $table) {
            $table->dropColumn(['component_types', 'required_for_types']);
        });
    }

    public function down(): void
    {
        Schema::table('fact_definitions', function (Blueprint $table) {
            $table->json('component_types')->nullable()->after('options');
            $table->json('required_for_types')->nullable()->after('component_types');
        });
    }
};
