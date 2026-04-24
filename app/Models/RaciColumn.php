<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaciColumn extends Model
{
    protected $fillable = ['raci_matrix_id', 'name', 'sort_order'];

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(RaciMatrix::class, 'raci_matrix_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RaciAssignment::class);
    }
}
