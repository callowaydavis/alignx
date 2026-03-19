<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('gray');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        $systemTypes = [
            ['name' => 'Application', 'color' => 'blue'],
            ['name' => 'Interface', 'color' => 'purple'],
            ['name' => 'Data Object', 'color' => 'green'],
            ['name' => 'IT Component', 'color' => 'orange'],
            ['name' => 'Provider', 'color' => 'teal'],
            ['name' => 'Process', 'color' => 'yellow'],
            ['name' => 'Business Capability', 'color' => 'red'],
        ];

        foreach ($systemTypes as $type) {
            DB::table('component_types')->insert(array_merge($type, [
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('component_types');
    }
};
