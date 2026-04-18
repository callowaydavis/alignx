<?php

namespace App\Http\Controllers;

use App\Enums\FactFieldType;
use App\Http\Requests\StoreFactDefinitionRequest;
use App\Http\Requests\UpdateFactDefinitionRequest;
use App\Models\FactDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FactDefinitionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', FactDefinition::class);

        $factDefinitions = FactDefinition::query()->orderBy('name')->paginate(20);

        return view('fact-definitions.index', compact('factDefinitions'));
    }

    public function create(): View
    {
        $this->authorize('create', FactDefinition::class);

        $fieldTypes = FactFieldType::cases();

        return view('fact-definitions.create', compact('fieldTypes'));
    }

    public function store(StoreFactDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', FactDefinition::class);

        FactDefinition::query()->create($request->validated());

        return redirect()->route('fact-definitions.index')
            ->with('success', 'Fact definition created successfully.');
    }

    public function edit(FactDefinition $factDefinition): View
    {
        $this->authorize('update', $factDefinition);

        $fieldTypes = FactFieldType::cases();

        return view('fact-definitions.edit', compact('factDefinition', 'fieldTypes'));
    }

    public function update(UpdateFactDefinitionRequest $request, FactDefinition $factDefinition): RedirectResponse
    {
        $this->authorize('update', $factDefinition);

        $factDefinition->update($request->validated());

        return redirect()->route('fact-definitions.index')
            ->with('success', 'Fact definition updated successfully.');
    }

    public function destroy(FactDefinition $factDefinition): RedirectResponse
    {
        $this->authorize('delete', $factDefinition);

        $factDefinition->delete();

        return redirect()->route('fact-definitions.index')
            ->with('success', 'Fact definition deleted successfully.');
    }
}
