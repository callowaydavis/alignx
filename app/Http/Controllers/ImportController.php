<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportComponentRequest;
use App\Models\Attribute;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ImportController extends Controller
{
    private const MAX_ROWS = 200;

    public function create(): View
    {
        $this->authorize('create', Component::class);

        return view('import.create');
    }

    public function preview(ImportComponentRequest $request): View|RedirectResponse
    {
        $this->authorize('create', Component::class);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return back()->withErrors(['file' => 'The CSV file is empty or invalid.']);
        }

        $headers = array_map('trim', $headers);

        // Load lookups once before the loop
        $attributes = Attribute::query()
            ->orderBy('name')
            ->get()
            ->keyBy(fn ($fd) => strtolower($fd->name));
        $validTypes = ComponentType::query()->pluck('name')->all();

        $rows = [];
        $rowCount = 0;
        $exceeded = false;

        while (($data = fgetcsv($handle)) !== false) {
            $rowCount++;

            if ($rowCount > self::MAX_ROWS) {
                $exceeded = true;
                break;
            }

            $data = array_pad($data, count($headers), '');
            $row = array_combine($headers, array_slice($data, 0, count($headers)));
            $rows[] = $this->validateRow($row, $rowCount, $attributes, $validTypes);
        }

        fclose($handle);

        $request->session()->put('import_rows', $rows);
        $request->session()->put('import_headers', $headers);

        return view('import.preview', compact('rows', 'exceeded'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Component::class);

        $rows = $request->session()->pull('import_rows', []);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (! empty($row['errors'])) {
                $skipped++;

                continue;
            }

            $data = $row['data'];
            $action = $data['_action'] ?? 'create';
            $facts = $data['_facts'] ?? [];

            // Extract tags before cleanup
            $tagNames = ! empty($data['tags'])
                ? array_filter(array_map('trim', explode(',', $data['tags'])))
                : [];

            // Remove internal/non-fillable keys
            unset($data['id'], $data['_action'], $data['_component_id'], $data['_facts'],
                $data['tags'], $data['owner']);

            if ($action === 'update') {
                $component = Component::withoutGlobalScope('active')->find($row['data']['_component_id']);
                $component->update($data);
                $updated++;
            } else {
                $component = Component::query()->create($data);
                $created++;
            }

            // Sync tags
            if ($tagNames) {
                $tagIds = collect($tagNames)->map(
                    fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
                );
                $component->tags()->sync($tagIds);
            }

            // Sync facts
            foreach ($facts as $attributeId => $value) {
                $component->facts()->updateOrCreate(
                    ['attribute_id' => $attributeId],
                    ['value' => $value]
                );
            }
        }

        return redirect()->route('components.index')
            ->with('success', "Import complete: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    /**
     * @param  array<string, string>  $row
     * @param  Collection<string, Attribute>  $attributes  keyed by lowercase name
     * @param  array<string>  $validTypes
     * @return array{data: array<string, mixed>, errors: list<string>, action: string}
     */
    private function validateRow(array $row, int $rowNumber, Collection $attributes, array $validTypes): array
    {
        $errors = [];
        $data = [];

        // Extract standard component fields
        $data['id'] = $row['id'] ?? null;
        $data['name'] = trim($row['name'] ?? '');
        $data['type'] = trim($row['type'] ?? '');
        $data['description'] = trim($row['description'] ?? '') ?: null;
        $data['lifecycle_stage'] = trim($row['lifecycle_stage'] ?? '') ?: null;
        $data['lifecycle_start_date'] = trim($row['lifecycle_start_date'] ?? '') ?: null;
        $data['lifecycle_end_date'] = trim($row['lifecycle_end_date'] ?? '') ?: null;
        $data['tags'] = $row['tags'] ?? '';
        $data['owner'] = $row['owner'] ?? null;

        // Determine if this is a create or update
        $rawId = trim($row['id'] ?? '');

        if ($rawId !== '') {
            // Update path: look up the component (bypass active scope)
            $existing = Component::withoutGlobalScope('active')->find((int) $rawId);
            if (! $existing) {
                $errors[] = "No component found with ID {$rawId}.";
                $data['_action'] = 'update';
            } else {
                $data['_action'] = 'update';
                $data['_component_id'] = $existing->id;
                // Allow name/type to fall back to existing values if blank in CSV
                if (empty($data['name'])) {
                    $data['name'] = $existing->name;
                }
                if (empty($data['type'])) {
                    $data['type'] = $existing->type;
                }
            }
        } else {
            $data['_action'] = 'create';
        }

        // Validate required fields only for creates, or updates with blank values
        if ($data['_action'] === 'create' || ($data['_action'] === 'update' && empty($data['name']))) {
            if (empty($data['name'])) {
                $errors[] = 'Name is required.';
            }
        }

        if (empty($data['type']) || ! in_array($data['type'], $validTypes)) {
            $errors[] = "Invalid type: '{$data['type']}'. Must be one of: ".implode(', ', $validTypes).'.';
        }

        // Owner: resolve by Team name (fix from original which used User)
        if (! empty($data['owner'])) {
            $team = Team::query()->where('name', $data['owner'])->first();
            if ($team) {
                $data['owner_id'] = $team->id;
            }
        }

        // Extract fact definitions from row
        $knownFields = ['id', 'name', 'type', 'description', 'lifecycle_stage',
            'lifecycle_start_date', 'lifecycle_end_date', 'tags', 'owner'];
        $facts = [];

        foreach ($row as $header => $value) {
            if (in_array(strtolower($header), array_map('strtolower', $knownFields))) {
                continue;
            }

            $fd = $attributes->get(strtolower($header));
            if ($fd && $value !== '') {
                $facts[$fd->id] = $value;
            }
        }

        $data['_facts'] = $facts;

        return ['data' => $data, 'errors' => $errors, 'action' => $data['_action']];
    }
}
