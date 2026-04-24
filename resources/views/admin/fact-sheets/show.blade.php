@extends('layouts.app')

@section('title', $factSheet->name)
@section('heading', $factSheet->name)

@section('header-actions')
    @can('update', $factSheet)
        <a href="{{ route('admin.fact-sheets.edit', $factSheet) }}"
           class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Edit Settings
        </a>
    @endcan
    @can('delete', $factSheet)
        <form method="POST" action="{{ route('admin.fact-sheets.destroy', $factSheet) }}"
              onsubmit="return confirm('Delete this fact sheet? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center bg-white border border-red-200 hover:bg-red-50 text-red-600 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Delete
            </button>
        </form>
    @endcan
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column: metadata --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Details</h2>

                @if ($factSheet->description)
                    <p class="text-sm text-gray-600">{{ $factSheet->description }}</p>
                @endif

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Applies To</p>
                    @if ($factSheet->componentTypes->isEmpty())
                        <p class="text-sm text-gray-500">All component types</p>
                    @else
                        <div class="flex flex-wrap gap-1">
                            @foreach ($factSheet->componentTypes as $type)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">{{ $type->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Who Can Fill It Out</p>
                    @if (empty($factSheet->allowed_roles) && $factSheet->teams->isEmpty())
                        <p class="text-sm text-gray-500">Everyone</p>
                    @else
                        <div class="flex flex-wrap gap-1">
                            @foreach ($factSheet->allowed_roles ?? [] as $role)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">{{ ucfirst($role) }} role</span>
                            @endforeach
                            @foreach ($factSheet->teams as $team)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">{{ $team->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Conditions --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">Conditions</h2>
                    <p class="text-xs text-gray-400 mt-0.5">This sheet only appears when ALL conditions pass.</p>
                </div>

                @can('update', $factSheet)
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 space-y-3">
                        <form method="POST" action="{{ route('admin.fact-sheets.conditions.add', $factSheet) }}" class="space-y-2">
                            @csrf
                            <select name="attribute_id" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a fact field...</option>
                                @foreach ($allDefinitions as $def)
                                    <option value="{{ $def->id }}">{{ $def->name }} ({{ $def->field_type->label() }})</option>
                                @endforeach
                            </select>
                            <div class="flex gap-2">
                                <select name="operator" id="condition-operator" required
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        onchange="toggleConditionValue(this.value)">
                                    @foreach ($operators as $op)
                                        <option value="{{ $op->value }}">{{ $op->label() }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="value" id="condition-value"
                                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Value...">
                            </div>
                            <button type="submit" class="w-full bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                                Add Condition
                            </button>
                        </form>
                    </div>
                @endcan

                <div class="divide-y divide-gray-50">
                    @forelse ($factSheet->conditions as $condition)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">{{ $condition->attribute->name }}</span>
                                <span class="text-gray-400"> {{ $condition->operator->label() }}</span>
                                @if ($condition->operator->requiresValue())
                                    <span class="font-medium"> "{{ $condition->value }}"</span>
                                @endif
                            </div>
                            @can('update', $factSheet)
                                <form method="POST" action="{{ route('admin.fact-sheets.conditions.remove', [$factSheet, $condition]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="px-5 py-4 text-center text-xs text-gray-400">No conditions — sheet always appears.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right column: fields --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">Fields</h2>
                    <p class="text-xs text-gray-400 mt-0.5">These are the questions displayed in the fact sheet form.</p>
                </div>

                @can('update', $factSheet)
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('admin.fact-sheets.definitions.add', $factSheet) }}" class="flex items-center gap-3">
                            @csrf
                            <select name="attribute_id" required
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Add a fact field...</option>
                                @foreach ($availableDefinitions as $def)
                                    <option value="{{ $def->id }}">{{ $def->name }} ({{ $def->field_type->label() }})</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap cursor-pointer">
                                <input type="checkbox" name="is_required" value="1"
                                       class="rounded border-gray-300 text-red-500 focus:ring-red-500">
                                Required
                            </label>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                                Add Field
                            </button>
                        </form>
                    </div>
                @endcan

                <div class="divide-y divide-gray-100">
                    @forelse ($factSheet->attributes->sortBy('pivot.sort_order') as $def)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-800">{{ $def->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $def->field_type->label() }}
                                </span>
                                @if ($def->pivot->is_required)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">
                                        Required
                                    </span>
                                @endif
                            </div>
                            @can('update', $factSheet)
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('admin.fact-sheets.definitions.update', [$factSheet, $def]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_required" value="{{ $def->pivot->is_required ? '0' : '1' }}">
                                        <button type="submit" class="text-xs text-gray-400 hover:text-gray-700 transition-colors">
                                            {{ $def->pivot->is_required ? 'Make optional' : 'Make required' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.fact-sheets.definitions.remove', [$factSheet, $def]) }}"
                                          onsubmit="return confirm('Remove this field from the sheet?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-gray-400 text-sm">
                            No fields yet.
                            <a href="{{ route('attributes.create') }}" class="text-blue-600 hover:underline">Create attributes</a> to add them here.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleConditionValue(operator) {
            const valueInput = document.getElementById('condition-value');
            const noValueOps = ['is_empty', 'is_not_empty'];
            if (noValueOps.includes(operator)) {
                valueInput.disabled = true;
                valueInput.value = '';
                valueInput.classList.add('bg-gray-100');
            } else {
                valueInput.disabled = false;
                valueInput.classList.remove('bg-gray-100');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const op = document.getElementById('condition-operator');
            if (op) { toggleConditionValue(op.value); }
        });
    </script>
@endsection
