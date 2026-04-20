<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentRoleAssignment extends Model
{
    protected $fillable = [
        'component_id',
        'role_id',
        'user_id',
        'team_id',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the display name of the assignee (user or team).
     */
    public function assigneeName(): string
    {
        return $this->user?->name ?? $this->team?->name ?? 'Unknown';
    }
}
