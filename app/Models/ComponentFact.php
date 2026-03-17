<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentFact extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactFactory> */
    use HasFactory;

    protected $fillable = [
        'component_id',
        'fact_definition_id',
        'value',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function factDefinition(): BelongsTo
    {
        return $this->belongsTo(FactDefinition::class);
    }
}
