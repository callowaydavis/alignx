@extends('layouts.app')

@section('title', 'Fact Definitions')
@section('heading', 'Fact Definitions')

@section('header-actions')
    <a href="{{ route('fact-definitions.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Fact Definition
    </a>
@endsection

@section('content')
    @if ($factDefinitions->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 text-sm">No fact definitions yet.</p>
            <a href="{{ route('fact-definitions.create') }}" class="mt-4 inline-block text-blue-600 text-sm font-medium hover:underline">Create your first fact definition</a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Name</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Field Type</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($factDefinitions as $factDefinition)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $factDefinition->name }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    {{ $factDefinition->field_type->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('fact-definitions.edit', $factDefinition) }}" class="text-gray-400 hover:text-gray-600 mr-3">Edit</a>
                                <form method="POST" action="{{ route('fact-definitions.destroy', $factDefinition) }}" class="inline"
                                      onsubmit="return confirm('Delete this fact definition? This will remove all associated fact values.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $factDefinitions->links() }}</div>
    @endif
@endsection
