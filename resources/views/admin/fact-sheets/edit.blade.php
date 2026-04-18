@extends('layouts.app')

@section('title', 'Edit Fact Sheet')
@section('heading', 'Edit ' . $factSheet->name)

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.fact-sheets.update', $factSheet) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $factSheet->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $factSheet->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Applies to Component Types</label>
                <p class="text-xs text-gray-500 mb-3">Leave all unchecked to apply to every component type.</p>
                @php $currentTypeIds = $factSheet->componentTypes->pluck('id')->all(); @endphp
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($componentTypes as $type)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="component_type_ids[]" value="{{ $type->id }}"
                                   @checked(in_array($type->id, old('component_type_ids', $currentTypeIds)))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $type->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Who can fill this out?</label>
                <p class="text-xs text-gray-500 mb-3">Leave all unchecked to allow everyone.</p>

                <p class="text-xs font-medium text-gray-600 mb-2">By Role</p>
                <div class="flex flex-wrap gap-3 mb-4">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="allowed_roles[]" value="{{ $role->value }}"
                                   @checked(in_array($role->value, old('allowed_roles', $factSheet->allowed_roles ?? [])))
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            {{ ucfirst($role->value) }}
                        </label>
                    @endforeach
                </div>

                @if ($teams->isNotEmpty())
                    @php $currentTeamIds = $factSheet->teams->pluck('id')->all(); @endphp
                    <p class="text-xs font-medium text-gray-600 mb-2">By Team</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($teams as $team)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="team_ids[]" value="{{ $team->id }}"
                                       @checked(in_array($team->id, old('team_ids', $currentTeamIds)))
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                {{ $team->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('admin.fact-sheets.show', $factSheet) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
