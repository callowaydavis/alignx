<?php

namespace App\Models;

use App\Enums\AssigneeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'assignee_type',
        'allow_multiple',
        'is_required',
    ];

    public function casts(): array
    {
        return [
            'assignee_type' => AssigneeType::class,
            'allow_multiple' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function componentTypes(): BelongsToMany
    {
        return $this->belongsToMany(ComponentType::class, 'role_component_type');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ComponentRoleAssignment::class);
    }

    /**
     * Determine whether this role applies to the given component type name.
     * A role with no component types assigned applies to all types.
     */
    public function appliesToComponentType(string $typeName): bool
    {
        $types = $this->relationLoaded('componentTypes')
            ? $this->componentTypes
            : $this->componentTypes()->get();

        if ($types->isEmpty()) {
            return true;
        }

        return $types->pluck('name')->contains($typeName);
    }
}
