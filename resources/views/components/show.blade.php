@extends('layouts.app')

@section('title', $component->name)
@section('heading', $component->name)

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('components.edit', $component) }}"
           class="inline-flex items-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Edit
        </a>
        <form method="POST" action="{{ route('components.destroy', $component) }}"
              onsubmit="return confirm('Delete this component and all its data?')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-2 border border-red-200 hover:border-red-300 text-red-600 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Delete
            </button>
        </form>
    </div>
@endsection

@section('content')
    {{-- Parent breadcrumb --}}
    @if ($component->parent)
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4 -mt-2">
            <a href="{{ route('components.show', $component->parent) }}" class="text-blue-600 hover:underline flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $component->parent->name }}
            </a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-700 font-medium">{{ $component->name }}</span>
        </div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex border-b border-gray-200 mb-6 -mt-1">
        <button data-switch-tab="overview"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-blue-500 text-blue-600">
            Overview
        </button>
        <button data-switch-tab="diagrams"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
            Diagrams
        </button>
        <button data-switch-tab="history"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
            History
        </button>
    </div>

    {{-- Overview tab --}}
    <div data-tab-content="overview">
        <div class="grid grid-cols-3 gap-6">
            {{-- Left: Component details --}}
            <div class="col-span-1 space-y-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center gap-3 mb-4 flex-wrap">
                        @include('components._type-badge', ['type' => $component->type])
                        @if ($component->lifecycle_stage)
                            @include('components._lifecycle-badge', ['stage' => $component->lifecycle_stage])
                        @endif
                    </div>

                    @if ($component->description)
                        <p class="text-sm text-gray-600">{{ $component->description }}</p>
                    @else
                        <p class="text-sm text-gray-400 italic">No description provided.</p>
                    @endif

                    @if ($component->lifecycle_stage)
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-1">
                            <p class="text-xs font-medium text-gray-500">Lifecycle</p>
                            <div class="text-xs text-gray-600 space-y-0.5">
                                @if ($component->lifecycle_start_date)
                                    <div>Start: {{ $component->lifecycle_start_date->format('M j, Y') }}</div>
                                @endif
                                @if ($component->lifecycle_end_date)
                                    <div>End: {{ $component->lifecycle_end_date->format('M j, Y') }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($component->tags->isNotEmpty())
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs font-medium text-gray-500 mb-2">Tags</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($component->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 pt-4 border-t border-gray-100 space-y-1">
                        <p class="text-xs font-medium text-gray-500">Owner</p>
                        <p class="text-sm text-gray-700">{{ $component->owner?->name ?? 'Unassigned' }}</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 space-y-1">
                        <div>Created: {{ $component->created_at->format('M j, Y') }}</div>
                        <div>Updated: {{ $component->updated_at->format('M j, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Right: Subcomponents, Facts & Relationships --}}
            <div class="col-span-2 space-y-6">
                {{-- Subcomponents (only shown on root-level components) --}}
                @if ($component->isRootLevel())
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-800">Subcomponents</h2>
                            <a href="{{ route('components.create', ['parent' => $component->id]) }}"
                               class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Subcomponent
                            </a>
                        </div>
                        @if ($component->subcomponents->isNotEmpty())
                            <div class="divide-y divide-gray-50">
                                @foreach ($component->subcomponents as $sub)
                                    <div class="flex items-center justify-between px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <a href="{{ route('components.show', $sub) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline">
                                                {{ $sub->name }}
                                            </a>
                                            @include('components._type-badge', ['type' => $sub->type])
                                            @if ($sub->lifecycle_stage)
                                                @include('components._lifecycle-badge', ['stage' => $sub->lifecycle_stage])
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-5 py-6 text-center">
                                <p class="text-sm text-gray-400">No subcomponents yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Facts --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Facts</h2>
                    </div>

                    @if ($component->facts->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach ($component->facts as $fact)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <div>
                                        <p class="text-xs text-gray-400 mb-0.5">{{ $fact->factDefinition->name }}</p>
                                        <p class="text-sm text-gray-800">
                                            @if ($fact->factDefinition->field_type->value === 'boolean')
                                                {{ $fact->value === '1' || strtolower($fact->value) === 'true' ? 'Yes' : 'No' }}
                                            @elseif ($fact->factDefinition->field_type->value === 'url')
                                                <a href="{{ $fact->value }}" target="_blank"
                                                   class="text-blue-600 hover:underline">{{ $fact->value }}</a>
                                            @else
                                                {{ $fact->value ?? '—' }}
                                            @endif
                                        </p>
                                    </div>
                                    <form method="POST"
                                          action="{{ route('components.facts.destroy', [$component, $fact->id]) }}"
                                          onsubmit="return confirm('Remove this fact?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($availableFacts->isNotEmpty())
                        <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                            <form method="POST" action="{{ route('components.facts.store', $component) }}"
                                  class="flex gap-3 flex-wrap items-end">
                                @csrf
                                <div class="flex-1 min-w-48">
                                    <label class="block text-xs text-gray-500 mb-1">Add Fact</label>
                                    <select name="fact_definition_id" id="fact-select"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            onchange="updateFactInput(this)">
                                        <option value="">Select a fact...</option>
                                        @foreach ($availableFacts as $factDef)
                                            <option value="{{ $factDef->id }}"
                                                    data-type="{{ $factDef->field_type->value }}"
                                                    data-options="{{ json_encode($factDef->options) }}">
                                                {{ $factDef->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 min-w-48" id="fact-value-container">
                                    <label class="block text-xs text-gray-500 mb-1">Value</label>
                                    <input type="text" name="value" id="fact-value"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                    Add
                                </button>
                            </form>
                        </div>
                    @elseif ($component->facts->isEmpty())
                        <div class="px-5 py-6 text-center">
                            <p class="text-sm text-gray-400">No fact definitions available for this component type.</p>
                            <a href="{{ route('fact-definitions.create') }}"
                               class="text-sm text-blue-600 hover:underline mt-1 inline-block">Create fact definitions</a>
                        </div>
                    @endif
                </div>

                {{-- Relationships --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Relationships</h2>
                    </div>

                    @php
                        $allRelationships = $component->outgoingRelationships->merge($component->incomingRelationships);
                    @endphp

                    @if ($allRelationships->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach ($component->outgoingRelationships as $rel)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-gray-400 w-16 text-right shrink-0">
                                            {{ $component->name }}
                                        </span>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                            {{ $rel->relationship_type ?? 'relates to' }}
                                        </span>
                                        <a href="{{ route('components.show', $rel->targetComponent) }}"
                                           class="text-sm text-blue-600 hover:underline">
                                            {{ $rel->targetComponent->name }}
                                        </a>
                                        @include('components._type-badge', ['type' => $rel->targetComponent->type])
                                    </div>
                                    <form method="POST"
                                          action="{{ route('components.relationships.destroy', [$component, $rel]) }}"
                                          onsubmit="return confirm('Remove this relationship?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach

                            @foreach ($component->incomingRelationships as $rel)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('components.show', $rel->sourceComponent) }}"
                                           class="text-sm text-blue-600 hover:underline">
                                            {{ $rel->sourceComponent->name }}
                                        </a>
                                        @include('components._type-badge', ['type' => $rel->sourceComponent->type])
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                            {{ $rel->relationship_type ?? 'relates to' }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ $component->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                        @if ($availableComponents->isNotEmpty())
                            <form method="POST" action="{{ route('components.relationships.store', $component) }}"
                                  class="flex gap-3 flex-wrap items-end">
                                @csrf
                                <div class="flex-1 min-w-48">
                                    <label class="block text-xs text-gray-500 mb-1">Add Relationship</label>
                                    <select name="target_component_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select component...</option>
                                        @foreach ($availableComponents as $target)
                                            <option value="{{ $target->id }}">
                                                {{ $target->name }} ({{ $target->type->value }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 min-w-36">
                                    <label class="block text-xs text-gray-500 mb-1">Relationship Type</label>
                                    <input type="text" name="relationship_type"
                                           placeholder="e.g. Uses, Owns, Provides"
                                           list="relationship-types"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <datalist id="relationship-types">
                                        <option value="Uses">
                                        <option value="Owns">
                                        <option value="Provides">
                                        <option value="Connects">
                                        <option value="Depends On">
                                        <option value="Contains">
                                        <option value="Realizes">
                                    </datalist>
                                </div>
                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                    Add
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-gray-400 text-center">No other components available to relate.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Diagrams tab --}}
    <div data-tab-content="diagrams" class="hidden">
        @include('components._diagrams')
    </div>

    {{-- History tab --}}
    <div data-tab-content="history" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Event</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">User</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Changed Fields</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($audits as $audit)
                        @php
                            $eventColors = [
                                'created' => 'bg-green-100 text-green-700',
                                'updated' => 'bg-blue-100 text-blue-700',
                                'deleted' => 'bg-red-100 text-red-700',
                            ];
                            $color = $eventColors[$audit->event] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                    {{ ucfirst($audit->event) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $audit->user?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                @if ($audit->event === 'updated' && $audit->new_values)
                                    @foreach (array_keys($audit->new_values) as $field)
                                        <span class="block">{{ $field }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $audit->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">No history recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateFactInput(select) {
            const container = document.getElementById('fact-value-container');
            const option = select.options[select.selectedIndex];
            const fieldType = option.dataset.type;
            const options = JSON.parse(option.dataset.options || 'null');

            container.innerHTML = '';

            const lbl = document.createElement('label');
            lbl.className = 'block text-xs text-gray-500 mb-1';
            lbl.textContent = 'Value';
            container.appendChild(lbl);

            if (!fieldType || fieldType === 'text' || fieldType === 'url' || fieldType === 'number') {
                const input = document.createElement('input');
                input.type = fieldType === 'number' ? 'number' : (fieldType === 'url' ? 'url' : 'text');
                input.name = 'value';
                input.className = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
                container.appendChild(input);
            } else if (fieldType === 'date') {
                const input = document.createElement('input');
                input.type = 'date';
                input.name = 'value';
                input.className = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
                container.appendChild(input);
            } else if (fieldType === 'boolean') {
                const sel = document.createElement('select');
                sel.name = 'value';
                sel.className = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
                sel.innerHTML = '<option value="true">Yes</option><option value="false">No</option>';
                container.appendChild(sel);
            } else if (fieldType === 'select' && options) {
                const sel = document.createElement('select');
                sel.name = 'value';
                sel.className = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
                sel.innerHTML = '<option value="">Select...</option>' + options.map(o => `<option value="${o}">${o}</option>`).join('');
                container.appendChild(sel);
            }
        }
    </script>
@endsection
