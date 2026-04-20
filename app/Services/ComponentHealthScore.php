<?php

namespace App\Services;

use App\Enums\TodoStatus;
use App\Models\Component;
use App\Models\Role;
use Illuminate\Support\Collection;

class ComponentHealthScore
{
    private int $score;

    /** @var array<int, array{label: string, delta: int, status: string}> */
    private array $breakdown;

    private function __construct(
        private readonly Component $component,
        private readonly Collection $requiredFactDefs,
        private readonly Collection $requiredRoles
    ) {
        $this->calculate();
    }

    /**
     * Create a score for a single component, resolving required facts and roles from configured sheets/roles.
     */
    public static function for(Component $component): self
    {
        $component->loadMissing(['owner', 'facts', 'todos', 'roleAssignments']);

        $requiredFactDefs = FactSheetResolver::forComponentType($component->type)
            ->flatMap(fn ($sheet) => $sheet->factDefinitions->filter(fn ($def) => $def->pivot->is_required))
            ->unique('id');

        $requiredRoles = Role::query()
            ->with('componentTypes')
            ->where('is_required', true)
            ->get()
            ->filter(fn ($role) => $role->appliesToComponentType($component->type));

        return new self($component, $requiredFactDefs, $requiredRoles);
    }

    /**
     * Create a score using pre-loaded required fact definitions and roles (for batch use — no extra queries per component).
     */
    public static function withRequiredFacts(Component $component, Collection $requiredFactDefs, ?Collection $requiredRoles = null): self
    {
        $component->loadMissing(['roleAssignments']);

        return new self($component, $requiredFactDefs, $requiredRoles ?? collect());
    }

    public function score(): int
    {
        return $this->score;
    }

    public function rating(): string
    {
        return match (true) {
            $this->score >= 80 => 'healthy',
            $this->score >= 50 => 'at_risk',
            default => 'critical',
        };
    }

    /**
     * @return array<int, array{label: string, delta: int, status: string}>
     */
    public function breakdown(): array
    {
        return $this->breakdown;
    }

    private function calculate(): void
    {
        $score = 100;
        $breakdown = [];

        // No description: −10
        if (empty($this->component->description)) {
            $score -= 10;
            $breakdown[] = ['label' => 'No description', 'delta' => -10, 'status' => 'bad'];
        } else {
            $breakdown[] = ['label' => 'Description', 'delta' => 0, 'status' => 'ok'];
        }

        // No lifecycle stage: −20
        if ($this->component->lifecycle_stage === null) {
            $score -= 20;
            $breakdown[] = ['label' => 'No lifecycle stage', 'delta' => -20, 'status' => 'bad'];
        } else {
            $breakdown[] = ['label' => 'Lifecycle stage', 'delta' => 0, 'status' => 'ok'];
        }

        // No owner: −15
        if ($this->component->owner_id === null) {
            $score -= 15;
            $breakdown[] = ['label' => 'No owner assigned', 'delta' => -15, 'status' => 'bad'];
        } else {
            $breakdown[] = ['label' => 'Owner assigned', 'delta' => 0, 'status' => 'ok'];
        }

        // Missing required facts: −10 each, max −20
        $existingFactDefIds = $this->component->facts->pluck('fact_definition_id');
        $missingCount = $this->requiredFactDefs->filter(
            fn ($def) => ! $existingFactDefIds->contains($def->id)
        )->count();

        if ($missingCount > 0) {
            $deduction = min($missingCount * 10, 20);
            $score -= $deduction;
            $breakdown[] = ['label' => "Missing required facts ({$missingCount})", 'delta' => -$deduction, 'status' => 'bad'];
        } else {
            $breakdown[] = ['label' => 'Required facts complete', 'delta' => 0, 'status' => 'ok'];
        }

        // Missing required roles: −10 each, max −20
        if ($this->requiredRoles->isNotEmpty()) {
            $assignedRoleIds = $this->component->roleAssignments->pluck('role_id')->unique();
            $missingRoleCount = $this->requiredRoles->filter(
                fn ($role) => ! $assignedRoleIds->contains($role->id)
            )->count();

            if ($missingRoleCount > 0) {
                $deduction = min($missingRoleCount * 10, 20);
                $score -= $deduction;
                $breakdown[] = ['label' => "Missing required roles ({$missingRoleCount})", 'delta' => -$deduction, 'status' => 'bad'];
            } else {
                $breakdown[] = ['label' => 'Required roles filled', 'delta' => 0, 'status' => 'ok'];
            }
        }

        // Open to-dos: −5 each, max −15
        $openTodos = $this->component->todos->filter(
            fn ($todo) => $todo->status !== TodoStatus::Completed
        );

        if ($openTodos->isNotEmpty()) {
            $deduction = min($openTodos->count() * 5, 15);
            $score -= $deduction;
            $breakdown[] = ['label' => "Open to-dos ({$openTodos->count()})", 'delta' => -$deduction, 'status' => 'warn'];
        } else {
            $breakdown[] = ['label' => 'No open to-dos', 'delta' => 0, 'status' => 'ok'];
        }

        // Overdue to-dos: −5 each additional, max −10
        $overdueTodos = $openTodos->filter(
            fn ($todo) => $todo->due_date && $todo->due_date->lt(today())
        );

        if ($overdueTodos->isNotEmpty()) {
            $deduction = min($overdueTodos->count() * 5, 10);
            $score -= $deduction;
            $breakdown[] = ['label' => "Overdue to-dos ({$overdueTodos->count()})", 'delta' => -$deduction, 'status' => 'bad'];
        }

        $this->score = max(0, $score);
        $this->breakdown = $breakdown;
    }
}
