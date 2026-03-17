<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Component extends Model
{
    /** @use HasFactory<\Database\Factories\ComponentFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'parent_id',
        'owner_id',
        'name',
        'type',
        'description',
        'lifecycle_stage',
        'lifecycle_start_date',
        'lifecycle_end_date',
    ];

    public function casts(): array
    {
        return [
            'type' => ComponentType::class,
            'lifecycle_stage' => LifecycleStage::class,
            'lifecycle_start_date' => 'date',
            'lifecycle_end_date' => 'date',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'parent_id');
    }

    public function subcomponents(): HasMany
    {
        return $this->hasMany(Component::class, 'parent_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeRootLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function isRootLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(ComponentRelationship::class, 'source_component_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(ComponentRelationship::class, 'target_component_id');
    }

    public function facts(): HasMany
    {
        return $this->hasMany(ComponentFact::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
}
