<?php

namespace App\Http\Requests;

use App\Enums\AssigneeType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'assignee_type' => ['required', Rule::enum(AssigneeType::class)],
            'allow_multiple' => ['boolean'],
            'is_required' => ['boolean'],
            'component_type_ids' => ['nullable', 'array'],
            'component_type_ids.*' => ['integer', 'exists:component_types,id'],
        ];
    }
}
