@extends('layouts.app')

@section('title', $componentType->name)
@section('heading', $componentType->name)

@section('header-actions')
    <a href="{{ route('admin.component-types.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Component Types</a>
@endsection

@section('content')
    @php
        $colorOptions = [
            'blue' => 'Blue', 'purple' => 'Purple', 'green' => 'Green', 'orange' => 'Orange',
            'teal' => 'Teal', 'yellow' => 'Yellow', 'red' => 'Red', 'pink' => 'Pink',
            'indigo' => 'Indigo', 'gray' => 'Gray', 'sky' => 'Sky', 'cyan' => 'Cyan',
            'lime' => 'Lime', 'emerald' => 'Emerald', 'rose' => 'Rose', 'fuchsia' => 'Fuchsia',
            'violet' => 'Violet', 'amber' => 'Amber', 'slate' => 'Slate',
        ];
        $badgeColors = [
            'blue' => 'bg-blue-100 text-blue-700', 'purple' => 'bg-purple-100 text-purple-700',
            'green' => 'bg-green-100 text-green-700', 'orange' => 'bg-orange-100 text-orange-700',
            'teal' => 'bg-teal-100 text-teal-700', 'yellow' => 'bg-yellow-100 text-yellow-700',
            'red' => 'bg-red-100 text-red-700', 'pink' => 'bg-pink-100 text-pink-700',
            'indigo' => 'bg-indigo-100 text-indigo-700', 'gray' => 'bg-gray-100 text-gray-700',
            'sky' => 'bg-sky-100 text-sky-700', 'cyan' => 'bg-cyan-100 text-cyan-700',
            'lime' => 'bg-lime-100 text-lime-700', 'emerald' => 'bg-emerald-100 text-emerald-700',
            'rose' => 'bg-rose-100 text-rose-700', 'fuchsia' => 'bg-fuchsia-100 text-fuchsia-700',
            'violet' => 'bg-violet-100 text-violet-700', 'amber' => 'bg-amber-100 text-amber-700',
            'slate' => 'bg-slate-100 text-slate-700',
        ];
    @endphp

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-2xl space-y-6">
        {{-- Edit name/color card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Edit Type</h2>
            <form method="POST" action="{{ route('admin.component-types.update', $componentType) }}" class="flex gap-3 items-start">
                @csrf @method('PATCH')
                <div class="flex-1">
                    <input type="text" name="name" value="{{ old('name', $componentType->name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <select name="color" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach ($colorOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('color', $componentType->color) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
                    Save
                </button>
            </form>
        </div>

        {{-- Relationship rules card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-1">Allowed Relationship Targets</h2>
            <p class="text-xs text-gray-500 mb-4">
                If none are selected, this type can relate to any other type.
            </p>

            <form method="POST" action="{{ route('admin.component-types.relationship-rules.update', $componentType) }}">
                @csrf @method('PATCH')

                <div class="space-y-2">
                    @foreach ($allTypes->where('id', '!=', $componentType->id) as $type)
                        @php $badgeClass = $badgeColors[$type->color] ?? 'bg-gray-100 text-gray-700'; @endphp
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="allowed_type_ids[]" value="{{ $type->id }}"
                                   @checked($componentType->allowedTargetTypes->contains($type->id))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                {{ $type->name }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Save Rules
                </button>
            </form>
        </div>
    </div>
@endsection
