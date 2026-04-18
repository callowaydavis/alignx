<?php

namespace App\Models;

use App\Enums\FactFieldType;
use Database\Factories\FactDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactDefinition extends Model
{
    /** @use HasFactory<FactDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'field_type',
        'options',
    ];

    public function casts(): array
    {
        return [
            'field_type' => FactFieldType::class,
            'options' => 'array',
        ];
    }

    public function componentFacts(): HasMany
    {
        return $this->hasMany(ComponentFact::class);
    }

    public function factSheets(): BelongsToMany
    {
        return $this->belongsToMany(FactSheet::class, 'fact_sheet_fact_definition')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(FactSheetCondition::class);
    }
}
