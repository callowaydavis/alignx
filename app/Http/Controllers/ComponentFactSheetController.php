<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\FactSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComponentFactSheetController extends Controller
{
    public function submit(Request $request, Component $component, FactSheet $factSheet): RedirectResponse
    {
        $this->authorize('update', $component);

        if (! $factSheet->isAccessibleBy(Auth::user())) {
            abort(403, 'You do not have permission to fill out this fact sheet.');
        }

        $factSheet->load('attributes');

        // Build validation rules dynamically from the sheet's fields
        $rules = [];
        foreach ($factSheet->attributes as $def) {
            $isRequired = (bool) $def->pivot->is_required;
            $rules["facts.{$def->id}"] = $isRequired
                ? ['required', 'string', 'max:10000']
                : ['nullable', 'string', 'max:10000'];
        }

        $validated = $request->validate($rules);
        $factValues = $validated['facts'] ?? [];

        $defIds = $factSheet->attributes->pluck('id');
        $existingFacts = $component->facts()
            ->whereIn('attribute_id', $defIds)
            ->get()
            ->keyBy('attribute_id');

        foreach ($factSheet->attributes as $def) {
            $value = $factValues[$def->id] ?? null;

            if ($value !== null && trim($value) !== '') {
                $oldValue = $existingFacts->get($def->id)?->value;

                $fact = $component->facts()->updateOrCreate(
                    ['attribute_id' => $def->id],
                    ['value' => $value]
                );

                if ($fact->wasRecentlyCreated) {
                    $component->recordAudit('fact_added', [], [$def->name => $value]);
                } elseif ($oldValue !== $value) {
                    $component->recordAudit('fact_updated', [$def->name => $oldValue], [$def->name => $value]);
                }
            }
        }

        return redirect()->route('components.show', $component)
            ->with('success', "'{$factSheet->name}' saved successfully.");
    }
}
