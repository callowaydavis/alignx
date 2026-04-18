@extends('layouts.app')

@section('title', 'Fact Sheets')
@section('heading', 'Fact Sheets')

@section('header-actions')
    @can('create', \App\Models\FactSheet::class)
        <a href="{{ route('admin.fact-sheets.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Fact Sheet
        </a>
    @endcan
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Component Types</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Fields</th>
                    <th class="px-6 py-3 text-left font-medium text-gray-500">Permissions</th>
                    <th class="px-6 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($factSheets as $sheet)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.fact-sheets.show', $sheet) }}" class="font-medium text-gray-900 hover:text-blue-600">{{ $sheet->name }}</a>
                            @if ($sheet->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($sheet->description, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($sheet->componentTypes->isEmpty())
                                <span class="text-xs text-gray-400">All types</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($sheet->componentTypes as $type)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">{{ $type->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $sheet->fact_definitions_count }}</td>
                        <td class="px-6 py-4">
                            @if (empty($sheet->allowed_roles) && $sheet->teams->isEmpty())
                                <span class="text-xs text-gray-400">Everyone</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($sheet->allowed_roles ?? [] as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">{{ ucfirst($role) }}</span>
                                    @endforeach
                                    @foreach ($sheet->teams as $team)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">{{ $team->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.fact-sheets.show', $sheet) }}" class="text-blue-600 hover:text-blue-800 font-medium">Manage</a>
                                @can('delete', $sheet)
                                    <form method="POST" action="{{ route('admin.fact-sheets.destroy', $sheet) }}" onsubmit="return confirm('Delete this fact sheet?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">No fact sheets yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($factSheets->hasPages())
        <div class="mt-4">{{ $factSheets->links() }}</div>
    @endif
@endsection
