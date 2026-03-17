@extends('layouts.app')

@section('title', 'New Fact Definition')
@section('heading', 'New Fact Definition')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('fact-definitions.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Operating System, RAM, Server Name"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field_type" class="block text-sm font-medium text-gray-700 mb-1">Field Type <span class="text-red-500">*</span></label>
                <select id="field_type" name="field_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('field_type') border-red-300 @enderror"
                        onchange="toggleSelectOptions(this.value)">
                    @foreach ($fieldTypes as $fieldType)
                        <option value="{{ $fieldType->value }}" @selected(old('field_type') === $fieldType->value)>{{ $fieldType->label() }}</option>
                    @endforeach
                </select>
                @error('field_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div id="select-options-container" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dropdown Options</label>
                <p class="text-xs text-gray-500 mb-2">Enter one option per line.</p>
                <textarea id="select-options-input" rows="5"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Option 1&#10;Option 2&#10;Option 3">{{ old('options_text') }}</textarea>
                <input type="hidden" name="options_json" id="options-json">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Applies to Component Types</label>
                <p class="text-xs text-gray-500 mb-3">Leave all unchecked to apply to all component types.</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($types as $type)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="component_types[]" value="{{ $type->value }}"
                                   @checked(in_array($type->value, old('component_types', [])))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $type->value }}
                        </label>
                    @endforeach
                </div>
                @error('component_types')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors"
                        onclick="prepareOptions()">
                    Create Fact Definition
                </button>
                <a href="{{ route('fact-definitions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function toggleSelectOptions(value) {
            const container = document.getElementById('select-options-container');
            container.classList.toggle('hidden', value !== 'select');
        }

        function prepareOptions() {
            const textarea = document.getElementById('select-options-input');
            const jsonInput = document.getElementById('options-json');
            if (textarea && jsonInput) {
                const options = textarea.value.split('\n').map(o => o.trim()).filter(o => o.length > 0);
                jsonInput.value = JSON.stringify(options);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('field_type');
            toggleSelectOptions(sel.value);

            document.querySelector('form').addEventListener('submit', function (e) {
                prepareOptions();
                const jsonInput = document.getElementById('options-json');
                if (jsonInput.value) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'options';
                    hidden.value = jsonInput.value;

                    // We pass as JSON array; parse by server
                    // Actually send as individual array items
                    const parsed = JSON.parse(jsonInput.value);
                    jsonInput.remove();
                    parsed.forEach(function (opt) {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'options[]';
                        inp.value = opt;
                        document.querySelector('form').appendChild(inp);
                    });
                }
            });
        });
    </script>
@endsection
