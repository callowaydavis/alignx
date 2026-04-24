<?php

namespace App\Http\Controllers;

use App\Enums\FactFieldType;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Attribute::class);

        $attributes = Attribute::query()->orderBy('name')->paginate(20);

        return view('attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        $this->authorize('create', Attribute::class);

        $fieldTypes = FactFieldType::cases();

        return view('attributes.create', compact('fieldTypes'));
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $this->authorize('create', Attribute::class);

        Attribute::query()->create($request->validated());

        return redirect()->route('attributes.index')
            ->with('success', 'Fact definition created successfully.');
    }

    public function edit(Attribute $attribute): View
    {
        $this->authorize('update', $attribute);

        $fieldTypes = FactFieldType::cases();

        return view('attributes.edit', compact('attribute', 'fieldTypes'));
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $attribute->update($request->validated());

        return redirect()->route('attributes.index')
            ->with('success', 'Fact definition updated successfully.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return redirect()->route('attributes.index')
            ->with('success', 'Fact definition deleted successfully.');
    }
}
