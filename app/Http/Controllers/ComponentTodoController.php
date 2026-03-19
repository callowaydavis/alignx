<?php

namespace App\Http\Controllers;

use App\Enums\TodoCategory;
use App\Enums\TodoStatus;
use App\Models\Component;
use App\Models\ComponentTodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class ComponentTodoController extends Controller
{
    public function store(Request $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $validated = $request->validate([
            'condition' => ['required', 'string'],
            'category' => ['required', new Enum(TodoCategory::class)],
            'status' => ['required', new Enum(TodoStatus::class)],
            'accepted_by' => ['nullable', 'integer', 'exists:users,id'],
            'acceptance_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $this->applyCompletionStamp($validated, null);

        $component->todos()->create($validated);

        return redirect()->route('components.show', $component)
            ->with('success', 'To do added.')
            ->withFragment('todos');
    }

    public function update(Request $request, Component $component, ComponentTodo $todo): RedirectResponse
    {
        $this->authorize('update', $component);

        $validated = $request->validate([
            'condition' => ['required', 'string'],
            'category' => ['required', new Enum(TodoCategory::class)],
            'status' => ['required', new Enum(TodoStatus::class)],
            'accepted_by' => ['nullable', 'integer', 'exists:users,id'],
            'acceptance_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $this->applyCompletionStamp($validated, $todo);

        $todo->update($validated);

        return redirect()->route('components.show', $component)
            ->with('success', 'To do updated.')
            ->withFragment('todos');
    }

    public function destroy(Component $component, ComponentTodo $todo): RedirectResponse
    {
        $this->authorize('update', $component);

        $todo->delete();

        return redirect()->route('components.show', $component)
            ->with('success', 'To do deleted.')
            ->withFragment('todos');
    }

    /** @param array<string, mixed> $data */
    private function applyCompletionStamp(array &$data, ?ComponentTodo $existing): void
    {
        $isNowCompleted = ($data['status'] ?? null) === TodoStatus::Completed->value;
        $wasAlreadyCompleted = $existing && $existing->status === TodoStatus::Completed;

        if ($isNowCompleted && ! $wasAlreadyCompleted) {
            $data['completed_by'] = Auth::id();
            $data['completed_at'] = now();
        } elseif (! $isNowCompleted) {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
        }
    }
}
