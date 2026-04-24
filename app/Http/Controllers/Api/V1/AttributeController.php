<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttributeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attribute::query();

        if ($request->has('component_type')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('component_types')
                    ->orWhereJsonContains('component_types', $request->string('component_type')->value());
            });
        }

        return AttributeResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreAttributeRequest $request): AttributeResource
    {
        $attribute = Attribute::query()->create($request->validated());

        return new AttributeResource($attribute);
    }

    public function show(Attribute $attribute): AttributeResource
    {
        return new AttributeResource($attribute);
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute): AttributeResource
    {
        $attribute->update($request->validated());

        return new AttributeResource($attribute);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return response()->json(null, 204);
    }
}
