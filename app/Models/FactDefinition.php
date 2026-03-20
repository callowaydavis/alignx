<?php

namespace App\Models;

use App\Enums\FactFieldType;
use Database\Factories\FactDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactDefinition extends Model
{
    /** @use HasFactory<FactDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'field_type',
        'options',
        'component_types',
        'required_for_types',
    ];

    public function casts(): array
    {
        return [
            'field_type' => FactFieldType::class,
            'options' => 'array',
            'component_types' => 'array',
            'required_for_types' => 'array',
        ];
    }

    public function componentFacts(): HasMany
    {
        return $this->hasMany(ComponentFact::class);
    }

    public function appliesToType(string $type): bool
    {
        if (empty($this->component_types)) {
            return true;
        }

        return in_array($type, $this->component_types);
    }

    public function isRequiredForType(string $type): bool
    {
        return ! empty($this->required_for_types) && in_array($type, $this->required_for_types);
    }
}
