<?php

namespace App\Http\Controllers;

use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use App\Http\Requests\StoreComponentRelationshipRequest;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use App\Models\Component;
use App\Models\ComponentRelationship;
use App\Models\FactDefinition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $relations = $includeSubcomponents ? ['tags', 'parent', 'owner'] : ['tags', 'owner'];
        $query = Component::query()->with($relations);

        if (! $includeSubcomponents) {
            $query->rootLevel();
        }

        if ($showMine) {
            $query->where('owner_id', Auth::id());
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

        $components = $query->orderBy('name')->paginate(20)->withQueryString();
        $types = ComponentType::cases();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();

        return view('components.index', compact('components', 'types', 'lifecycleStages', 'allTags', 'includeSubcomponents', 'showMine'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Component::class);

        $types = ComponentType::cases();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();
        $parentComponents = Component::query()->rootLevel()->orderBy('name')->get();
        $activeUsers = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('components.create', compact('types', 'lifecycleStages', 'allTags', 'parentComponents', 'activeUsers'));
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
            'facts.factDefinition',
            'tags',
            'outgoingRelationships.targetComponent',
            'incomingRelationships.sourceComponent',
            'owner',
        ]);

        $availableFacts = FactDefinition::query()
            ->where(function ($q) use ($component) {
                $q->whereNull('component_types')
                    ->orWhereJsonContains('component_types', $component->type->value);
            })
            ->whereNotIn('id', $component->facts->pluck('fact_definition_id'))
            ->orderBy('name')
            ->get();

        $subcomponentIds = $component->subcomponents->pluck('id');

        $availableComponents = Component::query()
            ->where('id', '!=', $component->id)
            ->whereNotIn('id', $subcomponentIds)
            ->orderBy('name')
            ->get();

        $allTags = Tag::query()->orderBy('name')->get();

        $audits = $component->audits()->with('user')->latest()->limit(20)->get();

        ['graphData' => $graphData, 'graphNodes' => $graphNodes, 'graphEdges' => $graphEdges, 'landscapeGroups' => $landscapeGroups]
            = $this->buildDiagramGraph($component);

        return view('components.show', compact('component', 'availableFacts', 'availableComponents', 'allTags', 'audits', 'graphData', 'graphNodes', 'graphEdges', 'landscapeGroups'));
    }

    public function edit(Component $component): View
    {
        $this->authorize('update', $component);

        $component->load('tags');
        $types = ComponentType::cases();
        $lifecycleStages = LifecycleStage::cases();
        $allTags = Tag::query()->orderBy('name')->get();
        $parentComponents = Component::query()->rootLevel()->where('id', '!=', $component->id)->orderBy('name')->get();
        $activeUsers = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('components.edit', compact('component', 'types', 'lifecycleStages', 'allTags', 'parentComponents', 'activeUsers'));
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

        $component->outgoingRelationships()->create([
            'target_component_id' => $request->integer('target_component_id'),
            'relationship_type' => $request->string('relationship_type')->value() ?: null,
            'description' => $request->string('description')->value() ?: null,
        ]);

        return redirect()->route('components.show', $component)
            ->with('success', 'Relationship added successfully.');
    }

    public function destroyRelationship(Component $component, ComponentRelationship $relationship): RedirectResponse
    {
        $this->authorize('update', $component);

        $relationship->delete();

        return redirect()->route('components.show', $component)
            ->with('success', 'Relationship removed successfully.');
    }

    public function storeFact(Request $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'fact_definition_id' => ['required', 'integer', 'exists:fact_definitions,id'],
            'value' => ['nullable', 'string'],
        ]);

        $component->facts()->updateOrCreate(
            ['fact_definition_id' => $request->integer('fact_definition_id')],
            ['value' => $request->input('value')]
        );

        return redirect()->route('components.show', $component)
            ->with('success', 'Fact saved successfully.');
    }

    public function destroyFact(Component $component, int $factId): RedirectResponse
    {
        $this->authorize('update', $component);

        $component->facts()->where('id', $factId)->delete();

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
            'type' => $component->type->value,
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
                        'type' => $target->type->value,
                        'isRoot' => false,
                    ]);
                    $this->addToLandscapeGroups($landscapeGroups, $target, $rel->relationship_type ?? 'relates to', $direction);
                    $queue[] = [$target, $direction];
                }
            }

            foreach ($current->incomingRelationships as $rel) {
                $source = $rel->sourceComponent;
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
                        'type' => $source->type->value,
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
                        'type' => $sub->type->value,
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
        $type = $component->type->value;

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
