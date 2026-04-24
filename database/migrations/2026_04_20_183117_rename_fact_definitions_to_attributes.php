<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename fact_definitions table to attributes
        DB::statement('ALTER TABLE fact_definitions RENAME TO attributes');

        // Rename fact_sheet_fact_definition to attribute_fact_sheet
        DB::statement('ALTER TABLE fact_sheet_fact_definition RENAME TO attribute_fact_sheet');

        // Update column names in component_facts
        DB::statement('ALTER TABLE component_facts RENAME COLUMN fact_definition_id TO attribute_id');

        // Update column names in fact_sheet_conditions
        DB::statement('ALTER TABLE fact_sheet_conditions RENAME COLUMN fact_definition_id TO attribute_id');

        // Update column name in attribute_fact_sheet
        DB::statement('ALTER TABLE attribute_fact_sheet RENAME COLUMN fact_definition_id TO attribute_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename attributes back to fact_definitions
        DB::statement('ALTER TABLE attributes RENAME TO fact_definitions');

        // Rename attribute_fact_sheet back to fact_sheet_fact_definition
        DB::statement('ALTER TABLE attribute_fact_sheet RENAME TO fact_sheet_fact_definition');

        // Revert column names in component_facts
        DB::statement('ALTER TABLE component_facts RENAME COLUMN attribute_id TO fact_definition_id');

        // Revert column names in fact_sheet_conditions
        DB::statement('ALTER TABLE fact_sheet_conditions RENAME COLUMN attribute_id TO fact_definition_id');

        // Revert column name in fact_sheet_fact_definition
        DB::statement('ALTER TABLE fact_sheet_fact_definition RENAME COLUMN attribute_id TO fact_definition_id');
    }
};
