<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeignIdFor(User::class, 'owner_id');
            $table->dropColumn('owner_id');
        });
    }
};
