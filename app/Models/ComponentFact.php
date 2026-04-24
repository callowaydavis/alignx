<?php

namespace App\Models;

use Database\Factories\ComponentFactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentFact extends Model
{
    /** @use HasFactory<ComponentFactFactory> */
    use HasFactory;

    protected $fillable = [
        'component_id',
        'attribute_id',
        'value',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
