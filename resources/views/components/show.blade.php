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

    {{-- Missing required facts warning --}}
    @php
        $existingFactDefIds = $component->facts->pluck('fact_definition_id');
        $missingRequired = $applicableSheets
            ->flatMap(fn ($sheet) => $sheet->factDefinitions->filter(fn ($fd) => $fd->pivot->is_required))
            ->unique('id')
            ->whereNotIn('id', $existingFactDefIds)
            ->values();
    @endphp
    @if ($missingRequired->isNotEmpty())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 flex items-start gap-3">
            <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm">
                <p class="font-medium text-amber-800">Missing required facts</p>
                <p class="text-amber-700 mt-0.5">
                    {{ $missingRequired->pluck('name')->join(', ') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex border-b border-gray-200 mb-6 -mt-1">
        <button data-switch-tab="overview"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-blue-500 text-blue-600">
            Overview
        </button>
        @if ($component->isRootLevel())
            <button data-switch-tab="subcomponents"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
                Subcomponents
                @if ($component->subcomponents->isNotEmpty())
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                        {{ $component->subcomponents->count() }}
                    </span>
                @endif
            </button>
        @endif
        <button data-switch-tab="diagrams"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
            Diagrams
        </button>
        <button data-switch-tab="history"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
            History
        </button>
        <button data-switch-tab="todos"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-700">
            To Dos
            @if ($component->todos->isNotEmpty())
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                    {{ $component->todos->count() }}
                </span>
            @endif
        </button>
    </div>

    {{-- Overview tab --}}
    <div data-tab-content="overview">
        <div class="grid grid-cols-3 gap-6">
            {{-- Left: Component details --}}
            <div class="col-span-1 space-y-5">
                @include('components._health-score', ['score' => $healthScore, 'compact' => false])
                <div class="bg-white rounded-xl border {{ $component->is_active ? 'border-gray-200' : 'border-amber-300' }} p-5">
                    @if (! $component->is_active)
                        <div class="mb-3 flex items-center gap-2 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Inactive — this component is archived and hidden from normal views
                        </div>
                    @endif
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
                                <div>{{ $component->lifecycle_stage->value }}</div>
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
                        <p class="text-xs font-medium text-gray-500">Owning Team</p>
                        @if ($component->owner)
                            <a href="{{ route('teams.show', $component->owner) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ $component->owner->name }}</a>
                        @else
                            <p class="text-sm text-gray-700">Unassigned</p>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 space-y-1">
                        <div>Created: {{ $component->created_at->format('M j, Y') }}</div>
                        <div>Updated: {{ $component->updated_at->format('M j, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Right: Facts & Relationships --}}
            <div class="col-span-2 space-y-6">
                {{-- Fact Sheets --}}
                @php $factValuesByDefId = $component->facts->keyBy('fact_definition_id'); @endphp

                <div class="bg-white rounded-xl border border-gray-200">
                    @if ($applicableSheets->isNotEmpty())
                        {{-- Inner tab nav --}}
                        <div class="flex items-center border-b border-gray-100 px-2 overflow-x-auto">
                            @foreach ($applicableSheets as $sheet)
                                <button type="button" data-sheet-tab="{{ $sheet->id }}"
                                        class="px-3 py-3 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition-colors {{ $loop->first ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                                    {{ $sheet->name }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Sheet content panels --}}
                        @foreach ($applicableSheets as $sheet)
                            <div data-sheet-content="{{ $sheet->id }}" class="{{ $loop->first ? '' : 'hidden' }}">
                                @if ($sheet->description)
                                    <p class="px-5 pt-4 text-xs text-gray-400">{{ $sheet->description }}</p>
                                @endif

                                @if ($sheet->factDefinitions->isNotEmpty())
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-5 px-5 py-5">
                                        @foreach ($sheet->factDefinitions->sortBy('pivot.sort_order') as $def)
                                            @php
                                                $existingFact = $factValuesByDefId->get($def->id);
                                                $currentValue = $existingFact?->value;
                                                $isRequired = (bool) $def->pivot->is_required;
                                            @endphp
                                            <div>
                                                <p class="text-xs text-gray-400 mb-0.5">
                                                    {{ $def->name }}@if ($isRequired && ! $currentValue)<span class="text-red-400 ml-0.5">*</span>@endif
                                                </p>
                                                <p class="text-sm text-gray-800">
                                                    @if ($def->field_type->value === 'boolean')
                                                        @if ($currentValue === 'true' || $currentValue === '1')
                                                            <span class="inline-flex items-center gap-1 text-green-700 font-medium">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                Yes
                                                            </span>
                                                        @elseif ($currentValue !== null)
                                                            <span class="text-gray-400">No</span>
                                                        @else
                                                            <span class="text-gray-300">—</span>
                                                        @endif
                                                    @elseif ($def->field_type->value === 'url' && $currentValue)
                                                        <a href="{{ $currentValue }}" target="_blank" class="text-blue-600 hover:underline truncate block max-w-xs">{{ $currentValue }}</a>
                                                    @elseif ($def->field_type->value === 'date' && $currentValue)
                                                        {{ \Carbon\Carbon::parse($currentValue)->format('M j, Y') }}
                                                    @else
                                                        {{ $currentValue ?? '—' }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="px-5 py-8 text-center text-sm text-gray-400">
                                        No fields in this sheet yet.
                                        @can('create', \App\Models\FactSheet::class)
                                            <a href="{{ route('admin.fact-sheets.show', $sheet) }}" class="text-blue-600 hover:underline ml-1">Add fields</a>
                                        @endcan
                                    </div>
                                @endif

                                @can('update', $component)
                                    @if ($sheet->factDefinitions->isNotEmpty())
                                        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                            <button type="button" data-open-modal="fs-modal-{{ $sheet->id }}"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                                Edit
                                            </button>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                        @endforeach
                    @else
                        <div class="px-5 py-8 text-center">
                            <p class="text-sm text-gray-400">No fact sheets are configured for this component type.</p>
                        </div>
                    @endif
                </div>

                {{-- Relationships --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Relationships</h2>
                    </div>

                    @php
                        $activeOutgoing = $component->outgoingRelationships->filter(fn ($r) => $r->targetComponent !== null);
                        $activeIncoming = $component->incomingRelationships->filter(fn ($r) => $r->sourceComponent !== null);
                    @endphp

                    @if ($activeOutgoing->isNotEmpty() || $activeIncoming->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach ($activeOutgoing as $rel)
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

                            @foreach ($activeIncoming as $rel)
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
                                                {{ $target->name }} ({{ $target->type }})
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

        {{-- Fact Sheet Edit Modals --}}
        @can('update', $component)
            @foreach ($applicableSheets as $sheet)
                @if ($sheet->factDefinitions->isNotEmpty())
                    <div id="fs-modal-{{ $sheet->id }}"
                         class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50"
                         data-modal-backdrop="fs-modal-{{ $sheet->id }}">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                                <div>
                                    <h3 class="font-semibold text-gray-800">{{ $sheet->name }}</h3>
                                    @if ($sheet->description)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $sheet->description }}</p>
                                    @endif
                                </div>
                                <button type="button" data-close-modal="fs-modal-{{ $sheet->id }}"
                                        class="text-gray-400 hover:text-gray-600 transition-colors ml-4 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('components.fact-sheets.submit', [$component, $sheet]) }}"
                                  class="flex flex-col flex-1 min-h-0">
                                @csrf
                                <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                                    @foreach ($sheet->factDefinitions->sortBy('pivot.sort_order') as $def)
                                        @php
                                            $existingFact = $factValuesByDefId->get($def->id);
                                            $currentValue = $existingFact?->value;
                                            $fieldType = $def->field_type->value;
                                            $isRequired = (bool) $def->pivot->is_required;
                                            $inputName = "facts[{$def->id}]";
                                            $inputId = "modal-fact-{$sheet->id}-{$def->id}";
                                        @endphp
                                        <div class="px-5 py-4">
                                            <label for="{{ $inputId }}" class="block text-xs font-medium text-gray-600 mb-1.5">
                                                {{ $def->name }}
                                                @if ($isRequired)<span class="text-red-500">*</span>@endif
                                            </label>
                                            @if ($fieldType === 'boolean')
                                                <select id="{{ $inputId }}" name="{{ $inputName }}"
                                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Select...</option>
                                                    <option value="true" @selected($currentValue === 'true' || $currentValue === '1')>Yes</option>
                                                    <option value="false" @selected($currentValue === 'false' || $currentValue === '0')>No</option>
                                                </select>
                                            @elseif ($fieldType === 'select' && !empty($def->options))
                                                <select id="{{ $inputId }}" name="{{ $inputName }}"
                                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Select...</option>
                                                    @foreach ($def->options as $option)
                                                        <option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($fieldType === 'date')
                                                <input type="date" id="{{ $inputId }}" name="{{ $inputName }}"
                                                       value="{{ $currentValue }}"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @elseif ($fieldType === 'number')
                                                <input type="number" id="{{ $inputId }}" name="{{ $inputName }}"
                                                       value="{{ $currentValue }}"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @elseif ($fieldType === 'url')
                                                <input type="url" id="{{ $inputId }}" name="{{ $inputName }}"
                                                       value="{{ $currentValue }}"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                       placeholder="https://...">
                                            @else
                                                <input type="text" id="{{ $inputId }}" name="{{ $inputName }}"
                                                       value="{{ $currentValue }}"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl flex items-center gap-3 shrink-0">
                                    <button type="submit"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                        Save
                                    </button>
                                    <button type="button" data-close-modal="fs-modal-{{ $sheet->id }}"
                                            class="text-sm text-gray-500 hover:text-gray-700">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endforeach
        @endcan
    </div>

    {{-- Subcomponents tab --}}
    @if ($component->isRootLevel())
        <div data-tab-content="subcomponents" class="hidden">
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
        </div>
    @endif

    {{-- Diagrams tab --}}
    <div data-tab-content="diagrams" class="hidden">
        @include('components._diagrams')
    </div>

    {{-- To Dos tab --}}
    <div data-tab-content="todos" class="hidden" id="todos">
        @include('components._todos')
    </div>

    {{-- History tab --}}
    <div data-tab-content="history" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Event</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">User</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Details</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($audits as $audit)
                        @php
                            $eventMeta = [
                                'created'              => ['label' => 'Created',              'color' => 'bg-green-100 text-green-700'],
                                'updated'              => ['label' => 'Updated',              'color' => 'bg-blue-100 text-blue-700'],
                                'deleted'              => ['label' => 'Deleted',              'color' => 'bg-red-100 text-red-700'],
                                'relationship_added'   => ['label' => 'Relationship Added',   'color' => 'bg-purple-100 text-purple-700'],
                                'relationship_removed' => ['label' => 'Relationship Removed', 'color' => 'bg-purple-100 text-purple-700'],
                                'fact_added'           => ['label' => 'Fact Added',           'color' => 'bg-teal-100 text-teal-700'],
                                'fact_updated'         => ['label' => 'Fact Updated',         'color' => 'bg-teal-100 text-teal-700'],
                                'fact_removed'         => ['label' => 'Fact Removed',         'color' => 'bg-teal-100 text-teal-700'],
                            ];
                            $meta = $eventMeta[$audit->event] ?? ['label' => ucfirst($audit->event), 'color' => 'bg-gray-100 text-gray-700'];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $meta['color'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $audit->user?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-gray-500 text-xs space-y-0.5">
                                @if (in_array($audit->event, ['relationship_added', 'relationship_removed']))
                                    @php
                                        $vals = $audit->new_values ?: $audit->old_values;
                                    @endphp
                                    @if ($vals)
                                        <span class="block">
                                            <span class="font-medium text-gray-700">{{ $vals['target'] ?? '—' }}</span>
                                            <span class="text-gray-400 mx-1">·</span>
                                            {{ $vals['type'] ?? '—' }}
                                        </span>
                                    @endif
                                @elseif ($audit->event === 'updated' && $audit->new_values)
                                    @foreach ($audit->new_values as $field => $newVal)
                                        <span class="block">
                                            <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</span>
                                            @if (isset($audit->old_values[$field]))
                                                <span class="line-through text-gray-400">{{ $audit->old_values[$field] ?? '—' }}</span>
                                                <span class="text-gray-400 mx-0.5">→</span>
                                            @endif
                                            {{ $newVal ?? '—' }}
                                        </span>
                                    @endforeach
                                @elseif (in_array($audit->event, ['fact_added', 'fact_updated', 'fact_removed']))
                                    @php
                                        $old = $audit->old_values;
                                        $new = $audit->new_values;
                                        $fields = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
                                    @endphp
                                    @foreach ($fields as $field)
                                        <span class="block">
                                            <span class="font-medium text-gray-700">{{ $field }}:</span>
                                            @if (isset($old[$field]))
                                                <span class="line-through text-gray-400">{{ $old[$field] ?? '—' }}</span>
                                                @if (isset($new[$field]))<span class="text-gray-400 mx-0.5">→</span>@endif
                                            @endif
                                            @if (isset($new[$field])){{ $new[$field] ?? '—' }}@endif
                                        </span>
                                    @endforeach
                                @elseif ($audit->event === 'created' && $audit->new_values)
                                    @foreach ($audit->new_values as $field => $val)
                                        @if ($val !== null && $val !== '')
                                            <span class="block">
                                                <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::headline($field) }}:</span>
                                                {{ $val }}
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $audit->created_at->diffForHumans() }}</td>
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
        // ── Facts: add / delete without page reload ──────────────────────────
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const storeUrl  = '{{ route('components.facts.store', $component) }}';
            const factsList  = document.getElementById('facts-list');
            const addSection = document.getElementById('facts-add-section');
            const addForm    = document.getElementById('facts-add-form');
            const factSelect = document.getElementById('fact-select');

            if (!factsList) { return; }

            function renderValueHtml(value, fieldType) {
                if (fieldType === 'boolean') {
                    return (value === '1' || String(value).toLowerCase() === 'true') ? 'Yes' : 'No';
                }
                if (fieldType === 'url' && value) {
                    const safe = String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    return `<a href="${safe}" target="_blank" class="text-blue-600 hover:underline">${safe}</a>`;
                }
                return value || '—';
            }

            function buildFactRow(factId, defId, defName, defType, defOptions, value, deleteUrl) {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between px-5 py-3';
                row.dataset.factId      = factId;
                row.dataset.factDefId   = defId;
                row.dataset.factDefName = defName;
                row.dataset.factDefType = defType;
                row.dataset.factDefOptions = JSON.stringify(defOptions);
                row.innerHTML = `
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">${defName}</p>
                        <p class="text-sm text-gray-800">${renderValueHtml(value, defType)}</p>
                    </div>
                    <button type="button" class="fact-delete-btn text-gray-300 hover:text-red-500 transition-colors"
                            data-url="${deleteUrl}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>`;
                return row;
            }

            function syncAddSectionVisibility() {
                if (!addSection || !factSelect) { return; }
                const hasOptions = Array.from(factSelect.options).some(o => o.value !== '');
                addSection.classList.toggle('hidden', !hasOptions);
            }

            function addOptionToSelect(defId, defName, defType, defOptions) {
                if (!factSelect) { return; }
                const opt = document.createElement('option');
                opt.value = defId;
                opt.textContent = defName;
                opt.dataset.type    = defType;
                opt.dataset.options = JSON.stringify(defOptions);
                const insertBefore = Array.from(factSelect.options)
                    .filter(o => o.value !== '')
                    .find(o => o.textContent.localeCompare(defName) > 0);
                factSelect.insertBefore(opt, insertBefore ?? null);
                syncAddSectionVisibility();
            }

            function removeOptionFromSelect(defId) {
                if (!factSelect) { return; }
                factSelect.querySelector(`option[value="${defId}"]`)?.remove();
                syncAddSectionVisibility();
            }

            // Add fact
            if (addForm) {
                addForm.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const defId = factSelect?.value;
                    if (!defId) { return; }

                    const valueField = addForm.querySelector('[name="value"]');
                    const value = valueField?.value ?? '';
                    const submitBtn = addForm.querySelector('[type="submit"]');
                    submitBtn.disabled = true;

                    try {
                        const formData = new FormData();
                        formData.append('fact_definition_id', defId);
                        formData.append('value', value);

                        const res = await fetch(storeUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: formData,
                        });

                        if (!res.ok) { throw new Error('Request failed'); }

                        const { fact } = await res.json();
                        const def = fact.fact_definition;
                        const deleteUrl = storeUrl + '/' + fact.id;

                        factsList.appendChild(buildFactRow(fact.id, def.id, def.name, def.field_type, def.options, fact.value, deleteUrl));
                        removeOptionFromSelect(def.id);
                        factSelect.value = '';
                        updateFactInput(factSelect);
                    } catch {
                        alert('Failed to save fact. Please try again.');
                    } finally {
                        submitBtn.disabled = false;
                    }
                });
            }

            // Delete fact (event delegation)
            factsList.addEventListener('click', async function (e) {
                const btn = e.target.closest('.fact-delete-btn');
                if (!btn || !confirm('Remove this fact?')) { return; }

                const row = btn.closest('[data-fact-id]');
                btn.disabled = true;

                try {
                    const res = await fetch(btn.dataset.url, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    });

                    if (!res.ok) { throw new Error('Request failed'); }

                    addOptionToSelect(row.dataset.factDefId, row.dataset.factDefName, row.dataset.factDefType, JSON.parse(row.dataset.factDefOptions || 'null'));
                    row.remove();
                } catch {
                    alert('Failed to remove fact. Please try again.');
                    btn.disabled = false;
                }
            });
        })();

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

        // ── Fact sheet inner tabs ─────────────────────────────────────────────
        document.addEventListener('click', function (e) {
            const sheetTabBtn = e.target.closest('[data-sheet-tab]');
            if (sheetTabBtn) {
                const id = sheetTabBtn.dataset.sheetTab;
                document.querySelectorAll('[data-sheet-content]').forEach(el => {
                    el.classList.toggle('hidden', el.dataset.sheetContent !== id);
                });
                document.querySelectorAll('[data-sheet-tab]').forEach(btn => {
                    const active = btn.dataset.sheetTab === id;
                    btn.classList.toggle('border-blue-500', active);
                    btn.classList.toggle('text-blue-600', active);
                    btn.classList.toggle('border-transparent', !active);
                    btn.classList.toggle('text-gray-500', !active);
                    btn.classList.toggle('hover:text-gray-700', !active);
                });
            }

            // ── Modals ────────────────────────────────────────────────────────
            const openBtn = e.target.closest('[data-open-modal]');
            if (openBtn) {
                document.getElementById(openBtn.dataset.openModal)?.classList.remove('hidden');
            }

            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                document.getElementById(closeBtn.dataset.closeModal)?.classList.add('hidden');
            }

            const backdrop = e.target.closest('[data-modal-backdrop]');
            if (backdrop && e.target === backdrop) {
                backdrop.classList.add('hidden');
            }
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[data-modal-backdrop]:not(.hidden)').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });
    </script>
@endsection
