<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'field_type' => $this->field_type->value,
            'options' => $this->options,
            'component_types' => $this->component_types,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
