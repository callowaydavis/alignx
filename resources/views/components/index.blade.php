@extends('layouts.app')

@section('title', 'Components')
@section('heading', 'Components')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('components.export', request()->query()) }}"
           class="inline-flex items-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium px-3 py-2 rounded-lg transition-colors">
            Export CSV
        </a>
        @can('create', \App\Models\Component::class)
            <a href="{{ route('components.import.create') }}"
               class="inline-flex items-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                Import
            </a>
            <a href="{{ route('components.create') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Component
            </a>
        @endcan
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <form method="GET" action="{{ route('components.index') }}" class="mb-6 flex gap-3 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">

        <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->value }}</option>
            @endforeach
        </select>

        <select name="lifecycle_stage" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Stages</option>
            @foreach ($lifecycleStages as $stage)
                <option value="{{ $stage->value }}" @selected(request('lifecycle_stage') === $stage->value)>{{ $stage->value }}</option>
            @endforeach
        </select>

        <select name="tag" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Tags</option>
            @foreach ($allTags as $tag)
                <option value="{{ $tag->name }}" @selected(request('tag') === $tag->name)>{{ $tag->name }}</option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
            <input type="checkbox" name="include_subcomponents" value="1"
                   @checked(request('include_subcomponents'))
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Show subcomponents
        </label>

        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
            <input type="checkbox" name="mine" value="1"
                   @checked(request('mine'))
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Show mine
        </label>

        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Filter
        </button>

        @if (request('search') || request('type') || request('lifecycle_stage') || request('tag') || request('include_subcomponents'))
            <a href="{{ route('components.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center px-2">
                Clear
            </a>
        @endif
    </form>

    @if ($components->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="text-gray-500 text-sm">No components found.</p>
            <a href="{{ route('components.create') }}" class="mt-4 inline-block text-blue-600 text-sm font-medium hover:underline">Create your first component</a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Name</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Lifecycle</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Tags</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Description</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($components as $component)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('components.show', $component) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ $component->name }}
                                </a>
                                @if ($includeSubcomponents && $component->parent_id)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <a href="{{ route('components.show', $component->parent_id) }}" class="hover:text-gray-600">
                                            {{ $component->parent?->name }}
                                        </a>
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @include('components._type-badge', ['type' => $component->type])
                            </td>
                            <td class="px-6 py-4">
                                @if ($component->lifecycle_stage)
                                    @include('components._lifecycle-badge', ['stage' => $component->lifecycle_stage])
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($component->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($component->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 max-w-xs truncate">
                                {{ $component->description ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('components.edit', $component) }}" class="text-gray-400 hover:text-gray-600 mr-3">Edit</a>
                                <form method="POST" action="{{ route('components.destroy', $component) }}" class="inline"
                                      onsubmit="return confirm('Delete this component?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $components->links() }}
        </div>
    @endif
@endsection
