<?php

namespace App\Http\Requests;

use App\Enums\LifecycleStage;
use App\Models\Component;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => [
                'nullable', 'integer', 'exists:components,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value && Component::where('id', $value)->whereNotNull('parent_id')->exists()) {
                        $fail('A subcomponent cannot be a parent of another subcomponent.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::exists('component_types', 'name')],
            'description' => ['nullable', 'string'],
            'lifecycle_stage' => ['nullable', new Enum(LifecycleStage::class)],
            'lifecycle_start_date' => ['nullable', 'date'],
            'lifecycle_end_date' => ['nullable', 'date', 'after_or_equal:lifecycle_start_date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
