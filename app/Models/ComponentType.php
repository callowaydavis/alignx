<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComponentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** Virtual accessor so views can use $type->value interchangeably with $type->name. */
    public function getValueAttribute(): string
    {
        return $this->name;
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class, 'type', 'name');
    }

    public function allowedTargetTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ComponentType::class,
            'component_type_relationship_rules',
            'source_type_id',
            'target_type_id'
        );
    }
}
