<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\RaciAssignment;
use App\Models\RaciColumn;
use App\Models\RaciMatrix;
use App\Models\RaciRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RaciController extends Controller
{
    public function initializeMatrix(Component $component): JsonResponse
    {
        $this->authorize('update', $component);

        // Create matrix if it doesn't exist
        $matrix = RaciMatrix::firstOrCreate(['component_id' => $component->id]);

        // Create default columns if none exist
        if ($matrix->columns()->count() === 0) {
            collect(['Responsible', 'Accountable', 'Consulted', 'Informed'])
                ->each(fn ($name, $order) => $matrix->columns()->create([
                    'name' => $name,
                    'sort_order' => $order,
                ]));
        }

        return response()->json(['success' => true, 'matrix_id' => $matrix->id]);
    }

    public function addRow(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate(['responsibility' => 'required|string|max:255']);

        $matrix = RaciMatrix::firstOrCreate(['component_id' => $component->id]);
        $maxSort = $matrix->rows()->max('sort_order') ?? -1;

        $row = $matrix->rows()->create([
            'responsibility' => $request->string('responsibility'),
            'notes' => $request->string('notes')->value() ?: null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['success' => true, 'row' => $row]);
    }

    public function updateRowNotes(RaciRow $row, Request $request): JsonResponse
    {
        $row->load('matrix');
        $this->authorize('update', $row->matrix->component);

        $request->validate(['notes' => 'nullable|string']);

        $row->update(['notes' => $request->string('notes')->value() ?: null]);

        return response()->json(['success' => true]);
    }

    public function deleteRow(RaciRow $row): JsonResponse
    {
        $row->load('matrix');
        $this->authorize('update', $row->matrix->component);

        $row->delete();

        return response()->json(['success' => true]);
    }

    public function updateAssignment(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'row_id' => 'required|integer|exists:raci_rows,id',
            'column_id' => 'required|integer|exists:raci_columns,id',
            'assigned_to_type' => 'nullable|in:user,team',
            'assigned_to_id' => 'nullable|integer',
        ]);

        $row = RaciRow::with('matrix')->findOrFail($request->integer('row_id'));
        $column = RaciColumn::with('matrix')->findOrFail($request->integer('column_id'));

        // Verify row and column belong to component's matrix
        if (! $row->matrix || ! $column->matrix || $row->matrix->component_id !== $component->id || $column->matrix->component_id !== $component->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->filled('assigned_to_type') && $request->filled('assigned_to_id')) {
            RaciAssignment::updateOrCreate(
                ['raci_row_id' => $row->id, 'raci_column_id' => $column->id],
                [
                    'assigned_to_type' => $request->string('assigned_to_type'),
                    'assigned_to_id' => $request->integer('assigned_to_id'),
                ]
            );
        } else {
            RaciAssignment::where('raci_row_id', $row->id)
                ->where('raci_column_id', $column->id)
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    public function updateColumnName(RaciColumn $column, Request $request): JsonResponse
    {
        $column->load('matrix');
        $this->authorize('update', $column->matrix->component);

        $request->validate(['name' => 'required|string|max:255']);

        $column->update(['name' => $request->string('name')]);

        return response()->json(['success' => true]);
    }

    public function addColumn(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate(['name' => 'required|string|max:255']);

        $matrix = RaciMatrix::where('component_id', $component->id)->firstOrFail();
        $maxSort = $matrix->columns()->max('sort_order') ?? -1;

        $column = $matrix->columns()->create([
            'name' => $request->string('name'),
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['success' => true, 'column' => $column]);
    }

    public function deleteColumn(RaciColumn $column): JsonResponse
    {
        $column->load('matrix');
        $this->authorize('update', $column->matrix->component);

        $column->delete();

        return response()->json(['success' => true]);
    }
}
