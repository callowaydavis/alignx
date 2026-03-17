<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentRelationship extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentRelationshipFactory> */
    use HasFactory;

    protected $fillable = [
        'source_component_id',
        'target_component_id',
        'relationship_type',
        'description',
    ];

    public function sourceComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'source_component_id');
    }

    public function targetComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'target_component_id');
    }
}
