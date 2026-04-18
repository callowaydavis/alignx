<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\FactSheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactSheet extends Model
{
    /** @use HasFactory<FactSheetFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'allowed_roles',
    ];

    public function casts(): array
    {
        return [
            'allowed_roles' => 'array',
        ];
    }

    public function factDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(FactDefinition::class, 'fact_sheet_fact_definition')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function componentTypes(): BelongsToMany
    {
        return $this->belongsToMany(ComponentType::class, 'fact_sheet_component_type');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(FactSheetCondition::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        $hasRoleRestriction = ! empty($this->allowed_roles);
        $hasTeamRestriction = $this->relationLoaded('teams')
            ? $this->teams->isNotEmpty()
            : $this->teams()->exists();

        if (! $hasRoleRestriction && ! $hasTeamRestriction) {
            return true;
        }

        if ($hasRoleRestriction && in_array($user->role->value, $this->allowed_roles)) {
            return true;
        }

        $userTeamIds = $user->relationLoaded('teams')
            ? $user->teams->pluck('id')
            : $user->teams()->pluck('teams.id');

        $sheetTeamIds = $this->relationLoaded('teams')
            ? $this->teams->pluck('id')
            : $this->teams()->pluck('teams.id');

        return $userTeamIds->intersect($sheetTeamIds)->isNotEmpty();
    }

    /**
     * Determine whether this sheet applies to the given component type name.
     * A sheet with no component types assigned applies to all types.
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

    /**
     * @return array<string>
     */
    public static function allRoleValues(): array
    {
        return array_column(UserRole::cases(), 'value');
    }
}
