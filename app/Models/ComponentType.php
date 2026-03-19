<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
