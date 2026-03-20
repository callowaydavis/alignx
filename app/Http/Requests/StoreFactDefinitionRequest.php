<?php

namespace App\Http\Requests;

use App\Enums\FactFieldType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreFactDefinitionRequest extends FormRequest
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
            'field_type' => ['required', new Enum(FactFieldType::class)],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
            'component_types' => ['nullable', 'array'],
            'component_types.*' => [Rule::exists('component_types', 'name')],
            'required_for_types' => ['nullable', 'array'],
            'required_for_types.*' => [Rule::exists('component_types', 'name')],
        ];
    }
}
