<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentRelationshipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_component_id' => $this->source_component_id,
            'target_component_id' => $this->target_component_id,
            'relationship_type' => $this->relationship_type,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'source_component' => new ComponentResource($this->whenLoaded('sourceComponent')),
            'target_component' => new ComponentResource($this->whenLoaded('targetComponent')),
        ];
    }
}
