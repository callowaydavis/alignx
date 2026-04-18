<?php

namespace App\Services;

use App\Models\Component;
use App\Models\FactSheet;
use App\Models\User;
use Illuminate\Support\Collection;

class FactSheetResolver
{
    /**
     * Return all fact sheets applicable to this component for a given user,
     * filtered by component type, conditions, and permissions.
     *
     * @return Collection<int, FactSheet>
     */
    public static function forComponent(Component $component, User $user): Collection
    {
        $factValuesByDefId = $component->facts
            ->keyBy('fact_definition_id')
            ->map(fn ($f) => $f->value)
            ->all();

        $sheets = FactSheet::query()
            ->with(['factDefinitions', 'componentTypes', 'teams', 'conditions'])
            ->get();

        return $sheets->filter(function (FactSheet $sheet) use ($component, $user, $factValuesByDefId) {
            if (! $sheet->appliesToComponentType($component->type)) {
                return false;
            }

            if (! $sheet->isAccessibleBy($user)) {
                return false;
            }

            foreach ($sheet->conditions as $condition) {
                if (! $condition->evaluate($factValuesByDefId)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Return all fact sheets applicable to a component type (ignoring user permissions and conditions).
     * Used for health score calculation.
     *
     * @return Collection<int, FactSheet>
     */
    public static function forComponentType(string $typeName): Collection
    {
        return FactSheet::query()
            ->with(['factDefinitions', 'componentTypes'])
            ->get()
            ->filter(fn (FactSheet $sheet) => $sheet->appliesToComponentType($typeName))
            ->values();
    }
}
