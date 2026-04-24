<?php

namespace App\Models;

use App\Enums\FactSheetConditionOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactSheetCondition extends Model
{
    protected $fillable = [
        'fact_sheet_id',
        'attribute_id',
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

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Evaluate this condition against a component's existing fact values.
     *
     * @param  array<int, string|null>  $factValuesByAttributeId  map of attribute_id → value
     */
    public function evaluate(array $factValuesByAttributeId): bool
    {
        $factValue = $factValuesByAttributeId[$this->attribute_id] ?? null;
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
