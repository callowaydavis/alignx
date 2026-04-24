<?php

namespace App\Http\Controllers;

use App\Enums\LifecycleStage;
use App\Enums\TodoCategory;
use App\Enums\TodoStatus;
use App\Http\Requests\StoreComponentRelationshipRequest;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use App\Models\Component;
use App\Models\ComponentRelationship;
use App\Models\ComponentType;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Services\ComponentHealthScore;
use App\Services\FactSheetResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComponentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Component::class);

        $includeSubcomponents = $request->boolean('include_subcomponents');
        $showMine = $request->boolean('mine');
        $showInactive = $request->boolean('inactive');
        $relations = $includeSubcomponents ? ['tags', 'parent', 'owner', 'facts', 'todos'] : ['tags', 'owner', 'facts', 'todos'];

        $query = $showInactive
            ? Component::withoutGlobalScope('active')->with($relations)->where('is_active', false)
            : Component::query()->with($relations);

        if (! $includeSubcomponents) {
            $query->rootLevel();
        }

        if ($showMine) {
            $myTeamIds = Auth::user()->teams()->pluck('teams.id');
            $query->whereIn('owner_id', $myTeamIds);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('lifecycle_stage')) {
            $query->where('lifecycle_stage', $request->string('lifecycle_stage'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $request->string('tag')));
        }

        $types = ComponentType::query()->orderBy('name')->get();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();

        if ($request->filled('health')) {
            $healthRating = $request->string('health')->value();
            $allComponents = $query->orderBy('name')->get();

            $allScores = $allComponents->mapWithKeys(
                fn ($component) => [$component->id => ComponentHealthScore::for($component)]
            );

            $filtered = $allComponents->filter(
                fn ($component) => $allScores[$component->id]->rating() === $healthRating
            )->values();

            $page = $request->integer('page', 1);
            $sliced = $filtered->slice(($page - 1) * 20, 20)->values();

            $components = new LengthAwarePaginator(
                $sliced,
                $filtered->count(),
                20,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $healthScores = $sliced->mapWithKeys(
                fn ($component) => [$component->id => $allScores[$component->id]]
            );
        } else {
            $components = $query->orderBy('name')->paginate(20)->withQueryString();

            $healthScores = $components->getCollection()->mapWithKeys(
                fn ($component) => [$component->id => ComponentHealthScore::for($component)]
            );
        }

        return view('components.index', compact('components', 'types', 'lifecycleStages', 'allTags', 'includeSubcomponents', 'showMine', 'showInactive', 'healthScores'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Component::class);

        $types = ComponentType::query()->orderBy('name')->get();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();
        $parentComponents = Component::query()->rootLevel()->orderBy('name')->get();
        $teams = Team::query()->orderBy('name')->get();

        return view('components.create', compact('types', 'lifecycleStages', 'allTags', 'parentComponents', 'teams'));
    }

    public function store(StoreComponentRequest $request): RedirectResponse
    {
        $this->authorize('create', Component::class);

        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $component = Component::query()->create($validated);

        if ($tagNames) {
            $tagIds = collect($tagNames)->map(
                fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
            );
            $component->tags()->sync($tagIds);
        }

        return redirect()->route('components.show', $component)
            ->with('success', 'Component created successfully.');
    }

    public function show(Component $component): View
    {
        $this->authorize('view', $component);

        $component->load([
            'parent',
            'subcomponents',
            'facts.attribute',
            'tags',
            'outgoingRelationships.targetComponent',
            'incomingRelationships.sourceComponent',
            'owner',
            'todos.acceptedByUser',
            'todos.completedByUser',
            'documents.uploadedBy',
            'roleAssignments.role',
            'roleAssignments.user',
            'roleAssignments.team',
        ]);

        $applicableSheets = FactSheetResolver::forComponent($component, Auth::user());

        $applicableRoles = Role::query()
            ->with('componentTypes')
            ->get()
            ->filter(fn (Role $role) => $role->appliesToComponentType($component->type))
            ->values();

        $subcomponentIds = $component->subcomponents->pluck('id');

        $availableComponents = Component::query()
            ->where('id', '!=', $component->id)
            ->whereNotIn('id', $subcomponentIds)
            ->orderBy('name')
            ->get();

        $allTags = Tag::query()->orderBy('name')->get();

        $audits = $component->audits()->with('user')->latest()->limit(20)->get();

        $todoCategories = TodoCategory::cases();
        $todoStatuses = TodoStatus::cases();
        $activeUsers = User::query()->where('is_active', true)->orderBy('name')->get();
        $allTeams = Team::query()->orderBy('name')->get();

        ['graphData' => $graphData, 'graphNodes' => $graphNodes, 'graphEdges' => $graphEdges, 'landscapeGroups' => $landscapeGroups]
            = $this->buildDiagramGraph($component);

        $healthScore = ComponentHealthScore::for($component);

        $raciMatrix = $component->raciMatrix()->first();
        if ($raciMatrix) {
            $raciMatrix->load([
                'columns' => fn ($q) => $q->orderBy('sort_order'),
                'rows' => fn ($q) => $q->orderBy('sort_order')->with('assignments'),
            ]);
        }

        return view('components.show', compact(
            'component', 'applicableSheets', 'applicableRoles', 'availableComponents', 'allTags', 'audits',
            'todoCategories', 'todoStatuses', 'activeUsers', 'allTeams',
            'graphData', 'graphNodes', 'graphEdges', 'landscapeGroups', 'healthScore', 'raciMatrix'
        ));
    }

    public function edit(Component $component): View
    {
        $this->authorize('update', $component);

        $component->load('tags');
        $types = ComponentType::query()->orderBy('name')->get();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();
        $parentComponents = Component::query()->rootLevel()->where('id', '!=', $component->id)->orderBy('name')->get();
        $teams = Team::query()->orderBy('name')->get();

        return view('components.edit', compact('component', 'types', 'lifecycleStages', 'allTags', 'parentComponents', 'teams'));
    }

    public function update(UpdateComponentRequest $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $component->update($validated);

        $tagIds = collect($tagNames)->map(
            fn ($name) => Tag::query()->firstOrCreate(['name' => $name])->id
        );
        $component->tags()->sync($tagIds);

        return redirect()->route('components.show', $component)
            ->with('success', 'Component updated successfully.');
    }

    public function destroy(Component $component): RedirectResponse
    {
        $this->authorize('delete', $component);

        $component->delete();

        return redirect()->route('components.index')
            ->with('success', 'Component deleted successfully.');
    }

    public function storeRelationship(StoreComponentRelationshipRequest $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $rel = $component->outgoingRelationships()->create([
            'target_component_id' => $request->integer('target_component_id'),
            'relationship_type' => $request->string('relationship_type')->value() ?: null,
            'description' => $request->string('description')->value() ?: null,
        ]);

        $rel->load('targetComponent');
        $component->recordAudit('relationship_added', [], [
            'target' => $rel->targetComponent?->name,
            'type' => $rel->relationship_type ?? 'relates to',
        ]);

        return redirect()->route('components.show', $component)
            ->with('success', 'Relationship added successfully.');
    }

    public function destroyRelationship(Component $component, ComponentRelationship $relationship): RedirectResponse
    {
        $this->authorize('update', $component);

        $relationship->loadMissing('targetComponent');
        $component->recordAudit('relationship_removed', [
            'target' => $relationship->targetComponent?->name,
            'type' => $relationship->relationship_type ?? 'relates to',
        ], []);

        $relationship->delete();

        return redirect()->route('components.show', $component)
            ->with('success', 'Relationship removed successfully.');
    }

    public function storeFact(Request $request, Component $component): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'value' => ['nullable', 'string'],
        ]);

        $defId = $request->integer('attribute_id');
        $oldValue = $component->facts()->where('attribute_id', $defId)->value('value');

        $fact = $component->facts()->updateOrCreate(
            ['attribute_id' => $defId],
            ['value' => $request->input('value')]
        );

        $fact->load('attribute');
        $defName = $fact->attribute->name;
        $newValue = $fact->value;

        if ($fact->wasRecentlyCreated) {
            $component->recordAudit('fact_added', [], [$defName => $newValue]);
        } elseif ($oldValue !== $newValue) {
            $component->recordAudit('fact_updated', [$defName => $oldValue], [$defName => $newValue]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'fact' => [
                    'id' => $fact->id,
                    'value' => $fact->value,
                    'attribute' => [
                        'id' => $fact->attribute->id,
                        'name' => $fact->attribute->name,
                        'field_type' => $fact->attribute->field_type->value,
                        'options' => $fact->attribute->options,
                    ],
                ],
            ]);
        }

        return redirect()->route('components.show', $component)
            ->with('success', 'Fact saved successfully.');
    }

    public function destroyFact(Component $component, int $factId): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $component);

        $fact = $component->facts()->with('attribute')->find($factId);
        if ($fact) {
            $component->recordAudit('fact_removed', [$fact->attribute->name => $fact->value], []);
            $fact->delete();
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('components.show', $component)
            ->with('success', 'Fact removed successfully.');
    }

    /**
     * Build the full diagram graph via BFS, traversing all transitive relationships.
     *
     * @return array{graphData: array<string, mixed>, graphNodes: Collection, graphEdges: Collection, landscapeGroups: Collection}
     */
    private function buildDiagramGraph(Component $component): array
    {
        $nodes = collect();
        $edges = collect();
        $landscapeGroups = collect();

        $visited = [$component->id => true];

        // Each queue entry: [Component, direction ('outgoing'|'incoming'|'contains'|null)]
        $queue = [[$component, null]];

        $nodes->push([
            'id' => 'c'.$component->id,
            'name' => $component->name,
            'type' => $component->type,
            'isRoot' => true,
        ]);

        while (! empty($queue)) {
            [$current, $inheritedDirection] = array_shift($queue);

            $current->loadMissing([
                'outgoingRelationships.targetComponent',
                'incomingRelationships.sourceComponent',
                'subcomponents',
            ]);

            foreach ($current->outgoingRelationships as $rel) {
                $target = $rel->targetComponent;
                if (! $target) {
                    continue;
                }

                $edges->push([
                    'id' => 'r'.$rel->id,
                    'source' => 'c'.$current->id,
                    'target' => 'c'.$target->id,
                    'label' => $rel->relationship_type ?? '',
                ]);

                if (! isset($visited[$target->id])) {
                    $visited[$target->id] = true;
                    $direction = $inheritedDirection ?? 'outgoing';
                    $nodes->push([
                        'id' => 'c'.$target->id,
                        'name' => $target->name,
                        'type' => $target->type,
                        'isRoot' => false,
                    ]);
                    $this->addToLandscapeGroups($landscapeGroups, $target, $rel->relationship_type ?? 'relates to', $direction);
                    $queue[] = [$target, $direction];
                }
            }

            foreach ($current->incomingRelationships as $rel) {
                $source = $rel->sourceComponent;
                if (! $source) {
                    continue;
                }

                $edges->push([
                    'id' => 'r'.$rel->id,
                    'source' => 'c'.$source->id,
                    'target' => 'c'.$current->id,
                    'label' => $rel->relationship_type ?? '',
                ]);

                if (! isset($visited[$source->id])) {
                    $visited[$source->id] = true;
                    $direction = $inheritedDirection ?? 'incoming';
                    $nodes->push([
                        'id' => 'c'.$source->id,
                        'name' => $source->name,
                        'type' => $source->type,
                        'isRoot' => false,
                    ]);
                    $this->addToLandscapeGroups($landscapeGroups, $source, $rel->relationship_type ?? 'relates to', $direction);
                    $queue[] = [$source, $direction];
                }
            }

            foreach ($current->subcomponents as $sub) {
                $edges->push([
                    'id' => 'sub'.$sub->id,
                    'source' => 'c'.$current->id,
                    'target' => 'c'.$sub->id,
                    'label' => 'contains',
                ]);

                if (! isset($visited[$sub->id])) {
                    $visited[$sub->id] = true;
                    $direction = $inheritedDirection ?? 'contains';
                    $nodes->push([
                        'id' => 'c'.$sub->id,
                        'name' => $sub->name,
                        'type' => $sub->type,
                        'isRoot' => false,
                        'isSub' => true,
                    ]);
                    $this->addToLandscapeGroups($landscapeGroups, $sub, 'contains', $direction);
                    $queue[] = [$sub, $direction];
                }
            }
        }

        $edges = $edges->unique('id')->values();

        return [
            'graphData' => [
                'focalId' => 'c'.$component->id,
                'nodes' => $nodes->values()->toArray(),
                'edges' => $edges->toArray(),
            ],
            'graphNodes' => $nodes,
            'graphEdges' => $edges,
            'landscapeGroups' => $landscapeGroups,
        ];
    }

    private function addToLandscapeGroups(
        Collection $groups,
        Component $component,
        string $label,
        string $direction
    ): void {
        $type = $component->type;

        if (! isset($groups[$type])) {
            $groups[$type] = collect();
        }

        $groups[$type]->push([
            'component' => $component,
            'label' => $label,
            'direction' => $direction,
        ]);
    }
}
