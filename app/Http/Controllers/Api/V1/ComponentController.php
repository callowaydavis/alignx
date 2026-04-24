<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use App\Http\Resources\ComponentResource;
use App\Models\Component;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComponentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Component::query()->with(['facts.attribute', 'tags']);

        if (! $request->boolean('include_subcomponents')) {
            $query->rootLevel();
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->has('lifecycle_stage')) {
            $query->where('lifecycle_stage', $request->string('lifecycle_stage'));
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $request->string('tag')));
        }

        return ComponentResource::collection($query->orderBy('name')->paginate(25));
    }

    public function store(StoreComponentRequest $request): ComponentResource
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $component = Component::query()->create($validated);

        if ($tagNames) {
            $tagIds = collect($tagNames)->map(
                fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
            );
            $component->tags()->sync($tagIds);
        }

        $component->load('tags');

        return new ComponentResource($component);
    }

    public function show(Component $component): ComponentResource
    {
        $component->load([
            'parent',
            'subcomponents',
            'facts.attribute',
            'tags',
            'outgoingRelationships.targetComponent',
            'incomingRelationships.sourceComponent',
        ]);

        return new ComponentResource($component);
    }

    public function update(UpdateComponentRequest $request, Component $component): ComponentResource
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? null;
        unset($validated['tags']);

        $component->update($validated);

        if ($tagNames !== null) {
            $tagIds = collect($tagNames)->map(
                fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
            );
            $component->tags()->sync($tagIds);
        }

        $component->load('tags');

        return new ComponentResource($component);
    }

    public function destroy(Component $component): JsonResponse
    {
        $component->delete();

        return response()->json(null, 204);
    }
}
