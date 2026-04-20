@extends('layouts.app')

@section('title', 'Edit ' . $factDefinition->name)
@section('heading', 'Edit Fact Definition')

@section('content')
    <div class="max-w-lg">
        <form name="factDefinitionForm" method="POST" action="{{ route('fact-definitions.update', $factDefinition) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $factDefinition->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field_type" class="block text-sm font-medium text-gray-700 mb-1">Field Type <span class="text-red-500">*</span></label>
                <select id="field_type" name="field_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('field_type') border-red-300 @enderror"
                        onchange="toggleSelectOptions(this.value)">
                    @foreach ($fieldTypes as $fieldType)
                        <option value="{{ $fieldType->value }}" @selected(old('field_type', $factDefinition->field_type->value) === $fieldType->value)>{{ $fieldType->label() }}</option>
                    @endforeach
                </select>
                @error('field_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div id="select-options-container" class="{{ old('field_type', $factDefinition->field_type->value) !== 'select' ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dropdown Options</label>
                <p class="text-xs text-gray-500 mb-2">Enter one option per line.</p>
                <textarea id="select-options-input" rows="5"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Option 1&#10;Option 2&#10;Option 3">{{ implode("\n", old('options', $factDefinition->options ?? [])) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('fact-definitions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function toggleSelectOptions(value) {
            document.getElementById('select-options-container').classList.toggle('hidden', value !== 'select');
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('form[name="factDefinitionForm"]').addEventListener('submit', function () {
                const textarea = document.getElementById('select-options-input');
                const fieldType = document.getElementById('field_type').value;
                if (fieldType === 'select' && textarea) {
                    const options = textarea.value.split('\n').map(o => o.trim()).filter(o => o.length > 0);
                    options.forEach(function (opt) {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'options[]';
                        inp.value = opt;
                        document.querySelector('form[name="factDefinitionForm"]').appendChild(inp);
                    });
                }
            });
        });
    </script>
@endsection
