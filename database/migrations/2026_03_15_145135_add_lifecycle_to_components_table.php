<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('lifecycle_stage')->nullable()->after('description');
            $table->date('lifecycle_start_date')->nullable()->after('lifecycle_stage');
            $table->date('lifecycle_end_date')->nullable()->after('lifecycle_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['lifecycle_stage', 'lifecycle_start_date', 'lifecycle_end_date']);
        });
    }
};
