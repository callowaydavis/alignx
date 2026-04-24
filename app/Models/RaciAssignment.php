<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaciAssignment extends Model
{
    protected $fillable = ['raci_row_id', 'raci_column_id', 'assigned_to_type', 'assigned_to_id'];

    public function row(): BelongsTo
    {
        return $this->belongsTo(RaciRow::class, 'raci_row_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(RaciColumn::class, 'raci_column_id');
    }

    public function assignedUser()
    {
        return $this->assigned_to_type === 'user' ? User::find($this->assigned_to_id) : null;
    }

    public function assignedTeam()
    {
        return $this->assigned_to_type === 'team' ? Team::find($this->assigned_to_id) : null;
    }

    public function getAssignedName(): ?string
    {
        if ($this->assigned_to_type === 'user') {
            return User::find($this->assigned_to_id)?->name;
        }

        return Team::find($this->assigned_to_id)?->name;
    }
}
