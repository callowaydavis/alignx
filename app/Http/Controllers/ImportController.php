<?php

namespace App\Http\Controllers;

use App\Enums\ComponentType;
use App\Http\Requests\ImportComponentRequest;
use App\Models\Component;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
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
            $rows[] = $this->validateRow($row, $rowCount);
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
        $skipped = 0;

        foreach ($rows as $row) {
            if (! empty($row['errors'])) {
                $skipped++;

                continue;
            }

            $data = $row['data'];
            $tagNames = ! empty($data['tags'])
                ? array_filter(array_map('trim', explode(',', $data['tags'])))
                : [];

            unset($data['id'], $data['tags'], $data['owner']);

            $component = Component::query()->create($data);

            if ($tagNames) {
                $tagIds = collect($tagNames)->map(
                    fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
                );
                $component->tags()->sync($tagIds);
            }

            $created++;
        }

        return redirect()->route('components.index')
            ->with('success', "Import complete: {$created} created, {$skipped} skipped.");
    }

    /**
     * @param  array<string, string>  $row
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    private function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];
        $data = [];

        $data['id'] = $row['id'] ?? null;
        $data['name'] = trim($row['name'] ?? '');
        $data['type'] = trim($row['type'] ?? '');
        $data['description'] = trim($row['description'] ?? '') ?: null;
        $data['lifecycle_stage'] = trim($row['lifecycle_stage'] ?? '') ?: null;
        $data['lifecycle_start_date'] = trim($row['lifecycle_start_date'] ?? '') ?: null;
        $data['lifecycle_end_date'] = trim($row['lifecycle_end_date'] ?? '') ?: null;
        $data['tags'] = $row['tags'] ?? '';
        $data['owner'] = $row['owner'] ?? null;

        if (empty($data['name'])) {
            $errors[] = 'Name is required.';
        }

        $validTypes = array_column(ComponentType::cases(), 'value');

        if (empty($data['type']) || ! in_array($data['type'], $validTypes)) {
            $errors[] = "Invalid type: '{$data['type']}'. Must be one of: ".implode(', ', $validTypes).'.';
        }

        if (! empty($data['owner'])) {
            $owner = User::query()->where('name', $data['owner'])->first();

            if ($owner) {
                $data['owner_id'] = $owner->id;
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
