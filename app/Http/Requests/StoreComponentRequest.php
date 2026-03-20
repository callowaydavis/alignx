<?php

namespace App\Http\Requests;

use App\Enums\LifecycleStage;
use App\Models\Component;
use App\Models\FactDefinition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = $this->string('type')->value();

            if (! $type) {
                return;
            }

            $required = FactDefinition::query()
                ->whereJsonContains('required_for_types', $type)
                ->get();

            foreach ($required as $def) {
                $value = $this->input("required_facts.{$def->id}");
                if ($value === null || trim((string) $value) === '') {
                    $v->errors()->add(
                        "required_facts.{$def->id}",
                        "The {$def->name} field is required for {$type} components."
                    );
                }
            }
        });
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
            'required_facts' => ['nullable', 'array'],
            'required_facts.*' => ['nullable', 'string'],
        ];
    }

    /** @return array<int, array{fact_definition_id: int, value: string}> */
    public function requiredFactValues(): array
    {
        $type = $this->string('type')->value();

        if (! $type) {
            return [];
        }

        return FactDefinition::query()
            ->whereJsonContains('required_for_types', $type)
            ->get()
            ->map(fn (FactDefinition $def) => [
                'fact_definition_id' => $def->id,
                'value' => $this->input("required_facts.{$def->id}"),
            ])
            ->all();
    }
}
