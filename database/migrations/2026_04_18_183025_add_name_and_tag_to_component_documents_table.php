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
        Schema::table('component_documents', function (Blueprint $table) {
            $table->string('name')->nullable()->after('original_filename');
            $table->string('tag')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('component_documents', function (Blueprint $table) {
            $table->dropColumn(['name', 'tag']);
        });
    }
};
