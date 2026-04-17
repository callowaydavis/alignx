@extends('layouts.app')

@section('title', 'Edit Team')
@section('heading', 'Edit Team')

@section('content')
    <div class="max-w-lg">
        <form method="POST" action="{{ route('teams.update', $team) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $team->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ad_group" class="block text-sm font-medium text-gray-700 mb-1">AD Group</label>
                <input type="text" id="ad_group" name="ad_group" value="{{ old('ad_group', $team->ad_group) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ad_group') border-red-300 @enderror"
                       placeholder="e.g. GRP_PLATFORM_TEAM">
                <p class="mt-1 text-xs text-gray-400">The Active Directory group name used to sync membership.</p>
                @error('ad_group')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-300 @enderror">{{ old('description', $team->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('teams.show', $team) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
