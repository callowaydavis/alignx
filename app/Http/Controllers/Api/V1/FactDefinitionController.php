<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFactDefinitionRequest;
use App\Http\Requests\UpdateFactDefinitionRequest;
use App\Http\Resources\FactDefinitionResource;
use App\Models\FactDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FactDefinitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FactDefinition::query();

        if ($request->has('component_type')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('component_types')
                    ->orWhereJsonContains('component_types', $request->string('component_type')->value());
            });
        }

        return FactDefinitionResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreFactDefinitionRequest $request): FactDefinitionResource
    {
        $factDefinition = FactDefinition::query()->create($request->validated());

        return new FactDefinitionResource($factDefinition);
    }

    public function show(FactDefinition $factDefinition): FactDefinitionResource
    {
        return new FactDefinitionResource($factDefinition);
    }

    public function update(UpdateFactDefinitionRequest $request, FactDefinition $factDefinition): FactDefinitionResource
    {
        $factDefinition->update($request->validated());

        return new FactDefinitionResource($factDefinition);
    }

    public function destroy(FactDefinition $factDefinition): JsonResponse
    {
        $factDefinition->delete();

        return response()->json(null, 204);
    }
}
