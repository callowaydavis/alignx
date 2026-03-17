<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComponentFactRequest;
use App\Http\Requests\UpdateComponentFactRequest;
use App\Http\Resources\ComponentFactResource;
use App\Models\Component;
use App\Models\ComponentFact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComponentFactController extends Controller
{
    public function index(Component $component): AnonymousResourceCollection
    {
        $facts = $component->facts()->with('factDefinition')->get();

        return ComponentFactResource::collection($facts);
    }

    public function store(StoreComponentFactRequest $request, Component $component): ComponentFactResource
    {
        $fact = $component->facts()->updateOrCreate(
            ['fact_definition_id' => $request->integer('fact_definition_id')],
            ['value' => $request->input('value')]
        );

        $fact->load('factDefinition');

        return new ComponentFactResource($fact);
    }

    public function show(Component $component, ComponentFact $fact): ComponentFactResource
    {
        $fact->load('factDefinition');

        return new ComponentFactResource($fact);
    }

    public function update(UpdateComponentFactRequest $request, Component $component, ComponentFact $fact): ComponentFactResource
    {
        $fact->update($request->validated());

        $fact->load('factDefinition');

        return new ComponentFactResource($fact);
    }

    public function destroy(Component $component, ComponentFact $fact): JsonResponse
    {
        $fact->delete();

        return response()->json(null, 204);
    }
}
