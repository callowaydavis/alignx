@extends('layouts.app')

@section('title', 'Tags')
@section('heading', 'Tags')

@section('header-actions')
    <a href="{{ route('admin.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
@endsection

@section('content')
    <div class="max-w-2xl space-y-6">
        {{-- Create form --}}
        @can('create', App\Models\Tag::class)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Create Tag</h2>
                <form method="POST" action="{{ route('admin.tags.store') }}" class="flex gap-3">
                    @csrf
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Tag name"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Create
                    </button>
                </form>
                @error('name')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endcan

        {{-- Tag list --}}
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @forelse ($tags as $tag)
                <div class="flex items-center justify-between px-5 py-3 group">
                    @can('update', $tag)
                        <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="flex items-center gap-2 flex-1 min-w-0">
                            @csrf @method('PATCH')
                            <input type="text" name="name" value="{{ $tag->name }}"
                                   class="text-sm text-gray-800 bg-transparent border-b border-transparent focus:border-gray-400 focus:outline-none w-48">
                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 opacity-0 group-hover:opacity-100 transition-opacity">
                                Save
                            </button>
                        </form>
                    @else
                        <span class="text-sm text-gray-800 flex-1">{{ $tag->name }}</span>
                    @endcan

                    <div class="flex items-center gap-4">
                        <span class="text-xs text-gray-400">{{ $tag->components_count }} component{{ $tag->components_count !== 1 ? 's' : '' }}</span>

                        @can('delete', $tag)
                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Delete tag \'{{ $tag->name }}\'? It will be removed from all components.')"
                                        class="text-xs text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Delete
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-400">No tags yet.</div>
            @endforelse
        </div>
    </div>
@endsection
