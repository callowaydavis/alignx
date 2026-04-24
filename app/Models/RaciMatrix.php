<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaciMatrix extends Model
{
    protected $fillable = ['component_id'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(RaciColumn::class)->orderBy('sort_order');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(RaciRow::class)->orderBy('sort_order');
    }
}
