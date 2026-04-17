<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->foreignId('owner_id')->nullable()->change();
            $table->foreign('owner_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
