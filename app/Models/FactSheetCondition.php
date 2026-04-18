<?php

namespace App\Models;

use App\Enums\FactSheetConditionOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactSheetCondition extends Model
{
    protected $fillable = [
        'fact_sheet_id',
        'fact_definition_id',
        'operator',
        'value',
    ];

    public function casts(): array
    {
        return [
            'operator' => FactSheetConditionOperator::class,
        ];
    }

    public function factSheet(): BelongsTo
    {
        return $this->belongsTo(FactSheet::class);
    }

    public function factDefinition(): BelongsTo
    {
        return $this->belongsTo(FactDefinition::class);
    }

    /**
     * Evaluate this condition against a component's existing fact values.
     *
     * @param  array<int, string|null>  $factValuesByDefinitionId  map of fact_definition_id → value
     */
    public function evaluate(array $factValuesByDefinitionId): bool
    {
        $factValue = $factValuesByDefinitionId[$this->fact_definition_id] ?? null;
        $conditionValue = $this->value;

        return match ($this->operator) {
            FactSheetConditionOperator::Equals => $factValue !== null && strtolower((string) $factValue) === strtolower((string) $conditionValue),
            FactSheetConditionOperator::NotEquals => $factValue === null || strtolower((string) $factValue) !== strtolower((string) $conditionValue),
            FactSheetConditionOperator::Contains => $factValue !== null && str_contains(strtolower((string) $factValue), strtolower((string) $conditionValue)),
            FactSheetConditionOperator::IsEmpty => $factValue === null || trim((string) $factValue) === '',
            FactSheetConditionOperator::IsNotEmpty => $factValue !== null && trim((string) $factValue) !== '',
        };
    }
}
