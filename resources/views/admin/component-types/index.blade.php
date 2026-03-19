@extends('layouts.app')

@section('title', 'Component Types')
@section('heading', 'Component Types')

@section('header-actions')
    <a href="{{ route('admin.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
@endsection

@section('content')
    @php
        $colorOptions = [
            'blue' => 'Blue', 'purple' => 'Purple', 'green' => 'Green', 'orange' => 'Orange',
            'teal' => 'Teal', 'yellow' => 'Yellow', 'red' => 'Red', 'pink' => 'Pink',
            'indigo' => 'Indigo', 'gray' => 'Gray',
        ];
        $badgeColors = [
            'blue' => 'bg-blue-100 text-blue-700', 'purple' => 'bg-purple-100 text-purple-700',
            'green' => 'bg-green-100 text-green-700', 'orange' => 'bg-orange-100 text-orange-700',
            'teal' => 'bg-teal-100 text-teal-700', 'yellow' => 'bg-yellow-100 text-yellow-700',
            'red' => 'bg-red-100 text-red-700', 'pink' => 'bg-pink-100 text-pink-700',
            'indigo' => 'bg-indigo-100 text-indigo-700', 'gray' => 'bg-gray-100 text-gray-700',
        ];
    @endphp

    <div class="max-w-2xl space-y-6">
        {{-- Create form (custom types only) --}}
        @can('create', App\Models\ComponentType::class)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Add Custom Type</h2>
                <form method="POST" action="{{ route('admin.component-types.store') }}" class="flex gap-3 items-start">
                    @csrf
                    <div class="flex-1">
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Type name (e.g. Service Mesh)"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <select name="color"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach ($colorOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('color') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
                        Add
                    </button>
                </form>
                @error('type')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        @endcan

        {{-- Type list --}}
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @forelse ($types as $type)
                @php $badgeClass = $badgeColors[$type->color] ?? 'bg-gray-100 text-gray-700'; @endphp
                <div class="flex items-center gap-4 px-5 py-3 group">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }} shrink-0 w-36 justify-center">
                        {{ $type->name }}
                    </span>

                    <div class="flex-1 min-w-0">
                        @if (! $type->is_system)
                            @can('update', $type)
                                <form method="POST" action="{{ route('admin.component-types.update', $type) }}"
                                      class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <input type="text" name="name" value="{{ $type->name }}"
                                           class="text-sm text-gray-800 bg-transparent border-b border-transparent focus:border-gray-400 focus:outline-none w-40">
                                    <select name="color"
                                            class="text-xs border border-gray-200 rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        @foreach ($colorOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($type->color === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                            class="text-xs text-blue-600 hover:text-blue-800 opacity-0 group-hover:opacity-100 transition-opacity">
                                        Save
                                    </button>
                                </form>
                            @endcan
                        @else
                            <span class="text-xs text-gray-400 italic">System type</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <span class="text-xs text-gray-400">{{ $type->components_count }} component{{ $type->components_count !== 1 ? 's' : '' }}</span>

                        @if (! $type->is_system)
                            @can('delete', $type)
                                <form method="POST" action="{{ route('admin.component-types.destroy', $type) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Delete type \'{{ $type->name }}\'?')"
                                            class="text-xs text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-400">No component types found.</div>
            @endforelse
        </div>
    </div>
@endsection
