<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'lifecycle_stage' => $this->lifecycle_stage?->value,
            'lifecycle_start_date' => $this->lifecycle_start_date?->toDateString(),
            'lifecycle_end_date' => $this->lifecycle_end_date?->toDateString(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'parent' => new ComponentResource($this->whenLoaded('parent')),
            'subcomponents' => ComponentResource::collection($this->whenLoaded('subcomponents')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'facts' => ComponentFactResource::collection($this->whenLoaded('facts')),
            'outgoing_relationships' => ComponentRelationshipResource::collection($this->whenLoaded('outgoingRelationships')),
            'incoming_relationships' => ComponentRelationshipResource::collection($this->whenLoaded('incomingRelationships')),
        ];
    }
}
