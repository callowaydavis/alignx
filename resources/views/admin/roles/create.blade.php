@extends('layouts.app')

@section('title', 'New Role')
@section('heading', 'New Role')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror"
                       placeholder="e.g. Data Steward, Technical Lead, Product Owner">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Assignee Type <span class="text-red-500">*</span></label>
                <div class="flex flex-col gap-2">
                    @foreach ($assigneeTypes as $type)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="radio" name="assignee_type" value="{{ $type->value }}"
                                   @checked(old('assignee_type', 'either') === $type->value)
                                   class="border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $type->label() }}
                        </label>
                    @endforeach
                </div>
                @error('assignee_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="allow_multiple" value="1"
                           @checked(old('allow_multiple'))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="font-medium">Allow multiple assignees</span>
                </label>
                <p class="mt-1 text-xs text-gray-500 ml-6">If checked, more than one user or team can hold this role on a component.</p>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_required" value="1"
                           @checked(old('is_required'))
                           class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="font-medium">Required for health score</span>
                </label>
                <p class="mt-1 text-xs text-gray-500 ml-6">Components missing this role will be penalised in their health score.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Applies to Component Types</label>
                <p class="text-xs text-gray-500 mb-3">Leave all unchecked to apply to every component type.</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($componentTypes as $type)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="component_type_ids[]" value="{{ $type->id }}"
                                   @checked(in_array($type->id, old('component_type_ids', [])))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $type->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    Create Role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
