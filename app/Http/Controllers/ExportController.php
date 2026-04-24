<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Component;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function components(Request $request): StreamedResponse
    {
        $attributes = Attribute::query()->orderBy('name')->get();

        $query = Component::query()->with(['owner', 'tags', 'facts.attribute']);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('lifecycle_stage')) {
            $query->where('lifecycle_stage', $request->string('lifecycle_stage'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $request->string('tag')));
        }

        $components = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="components-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($components, $attributes) {
            $handle = fopen('php://output', 'w');

            $columns = [
                'id', 'name', 'type', 'description', 'lifecycle_stage',
                'lifecycle_start_date', 'lifecycle_end_date', 'owner', 'tags',
            ];

            foreach ($attributes as $factDef) {
                $columns[] = $factDef->name;
            }

            fputcsv($handle, $columns);

            foreach ($components as $component) {
                $row = [
                    $component->id,
                    $component->name,
                    $component->type,
                    $component->description,
                    $component->lifecycle_stage?->value,
                    $component->lifecycle_start_date?->toDateString(),
                    $component->lifecycle_end_date?->toDateString(),
                    $component->owner?->name,
                    $component->tags->pluck('name')->join(','),
                ];

                foreach ($attributes as $factDef) {
                    $fact = $component->facts->firstWhere('attribute_id', $factDef->id);
                    $row[] = $fact?->value;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
