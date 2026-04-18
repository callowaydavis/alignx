@extends('layouts.app')

@section('title', 'New Component')
@section('heading', 'New Component')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('components.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf

            <div>
                <label for="owner_id" class="block text-sm font-medium text-gray-700 mb-1">Owning Team</label>
                <select id="owner_id" name="owner_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Unassigned</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected(old('owner_id') == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Component</label>
                <select id="parent_id" name="parent_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('parent_id') border-red-300 @enderror">
                    <option value="">None (root-level component)</option>
                    @foreach ($parentComponents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', request('parent')) == $parent->id)>
                            {{ $parent->name }} ({{ $parent->type }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select id="type" name="type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-300 @enderror">
                    <option value="">Select a type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->value }}</option>
                    @endforeach
                </select>
                @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="lifecycle_stage" class="block text-sm font-medium text-gray-700 mb-1">Lifecycle Stage</label>
                <select id="lifecycle_stage" name="lifecycle_stage"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('lifecycle_stage') border-red-300 @enderror">
                    <option value="">Not set</option>
                    @foreach ($lifecycleStages as $stage)
                        <option value="{{ $stage->value }}" @selected(old('lifecycle_stage') === $stage->value)>{{ $stage->value }}</option>
                    @endforeach
                </select>
                @error('lifecycle_stage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="lifecycle_start_date" class="block text-sm font-medium text-gray-700 mb-1">Lifecycle Start Date</label>
                    <input type="date" id="lifecycle_start_date" name="lifecycle_start_date" value="{{ old('lifecycle_start_date') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('lifecycle_start_date') border-red-300 @enderror">
                    @error('lifecycle_start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lifecycle_end_date" class="block text-sm font-medium text-gray-700 mb-1">Lifecycle End Date</label>
                    <input type="date" id="lifecycle_end_date" name="lifecycle_end_date" value="{{ old('lifecycle_end_date') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('lifecycle_end_date') border-red-300 @enderror">
                    @error('lifecycle_end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                <div class="flex flex-wrap gap-2 mb-2" id="selected-tags">
                    @foreach (old('tags', []) as $tag)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $tag }}
                            <button type="button" onclick="removeTag(this)" class="text-gray-400 hover:text-gray-600">×</button>
                            <input type="hidden" name="tags[]" value="{{ $tag }}">
                        </span>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <input type="text" id="tag-input" placeholder="Add a tag..."
                           list="existing-tags"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="existing-tags">
                        @foreach ($allTags as $tag)
                            <option value="{{ $tag->name }}">
                        @endforeach
                    </datalist>
                    <button type="button" onclick="addTag()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                        Add
                    </button>
                </div>
                @error('tags.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    Create Component
                </button>
                <a href="{{ route('components.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function addTag() {
            const input = document.getElementById('tag-input');
            const name = input.value.trim();
            if (!name) { return; }

            const existing = [...document.querySelectorAll('#selected-tags input[name="tags[]"]')]
                .map(i => i.value);
            if (existing.includes(name)) { input.value = ''; return; }

            const container = document.getElementById('selected-tags');
            const span = document.createElement('span');
            span.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700';
            span.innerHTML = `${name} <button type="button" onclick="removeTag(this)" class="text-gray-400 hover:text-gray-600">×</button><input type="hidden" name="tags[]" value="${name}">`;
            container.appendChild(span);
            input.value = '';
        }

        function removeTag(btn) {
            btn.closest('span').remove();
        }

        document.getElementById('tag-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addTag(); }
        });
    </script>
@endsection
