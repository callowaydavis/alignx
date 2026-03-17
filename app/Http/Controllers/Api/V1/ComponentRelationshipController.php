<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComponentRelationshipRequest;
use App\Http\Resources\ComponentRelationshipResource;
use App\Models\Component;
use App\Models\ComponentRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComponentRelationshipController extends Controller
{
    public function index(Component $component): AnonymousResourceCollection
    {
        $relationships = ComponentRelationship::query()
            ->with(['sourceComponent', 'targetComponent'])
            ->where('source_component_id', $component->id)
            ->orWhere('target_component_id', $component->id)
            ->get();

        return ComponentRelationshipResource::collection($relationships);
    }

    public function store(StoreComponentRelationshipRequest $request, Component $component): ComponentRelationshipResource
    {
        $relationship = $component->outgoingRelationships()->create([
            'target_component_id' => $request->integer('target_component_id'),
            'relationship_type' => $request->string('relationship_type')->value() ?: null,
            'description' => $request->string('description')->value() ?: null,
        ]);

        $relationship->load(['sourceComponent', 'targetComponent']);

        return new ComponentRelationshipResource($relationship);
    }

    public function show(Component $component, ComponentRelationship $relationship): ComponentRelationshipResource
    {
        $relationship->load(['sourceComponent', 'targetComponent']);

        return new ComponentRelationshipResource($relationship);
    }

    public function destroy(Component $component, ComponentRelationship $relationship): JsonResponse
    {
        $relationship->delete();

        return response()->json(null, 204);
    }
}
